<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file defines the manager class
 *
 * @package   tool_userautodelete
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Manages the user notification and deletion workflow
 */
class manager {

    /** @var \stdClass Moodle config object for this plugin */
    protected \stdClass $config;

    /** @var string[] User fields required to retrieve from DB */
    const USER_RECORD_FIELDS = [
        'id',
        'deleted',
        'username',
        'email',
        'timecreated',
        'lastaccess',
        'firstname',
        'middlename',
        'lastname',
        'firstnamephonetic',
        'lastnamephonetic',
        'alternatename',
    ];

    /**
     * Creates a new manager instance
     *
     * @return void
     * @throws \dml_exception
     */
    public function __construct() {
        $this->config = get_config('tool_userautodelete');
    }

    /**
     * Main entry point for the user autodelete process.
     *
     * This method is called regularly by the check_and_delete_users scheduled task.
     *
     * @return bool True, if executed successfully
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function execute(): bool {
        // Validate config and check if the plugin is enabled.
        if (!$this->validate_config()) {
            logger::error(get_string('error_invalid_config_aborting', 'tool_userautodelete'));
            return false;
        }

        if (!$this->config->enable) {
            logger::info(get_string('plugin_disabled_skipping_execution', 'tool_userautodelete'));
            return false;
        }

        // Execute the main workflow.
        $this->find_and_notify_inactive_users();
        $this->delete_inactive_users();
        $this->cleanup();

        return true;
    }

    /**
     * Validates the current plugin config, logs errors and returns false if any
     * errors are found.
     *
     * @return bool True if the config is valid, false otherwise.
     * @throws \coding_exception
     */
    public function validate_config(): bool {
        $isvalid = true;

        // User deletion.
        if ($this->config->delete_threshold_days <= 0) {
            $isvalid = false;
            logger::error(get_string('error_delete_threshold_days_negative', 'tool_userautodelete'));
        }

        // Warning mails.
        if ($this->config->warning_email_enable) {
            if ($this->config->warning_threshold_days <= 0) {
                $isvalid = false;
                logger::error(get_string('error_warning_threshold_days_negative', 'tool_userautodelete'));
            }

            if ($this->config->warning_threshold_days >= $this->config->delete_threshold_days) {
                $isvalid = false;
                logger::error(get_string('error_warning_threshold_days_geq_delete', 'tool_userautodelete'));
            }

            if ($this->config->warning_email_subject == '') {
                $isvalid = false;
                logger::error(get_string('error_warning_email_subject_empty', 'tool_userautodelete'));
            }

            if ($this->config->warning_email_body == '') {
                logger::warning(get_string('error_warning_email_body_empty', 'tool_userautodelete'));
            }
        }

        // Deletion mails.
        if ($this->config->delete_email_enable) {
            if ($this->config->delete_email_subject == '') {
                $isvalid = false;
                logger::error(get_string('error_delete_email_subject_empty', 'tool_userautodelete'));
            }

            if ($this->config->delete_email_body == '') {
                logger::warning(get_string('error_delete_email_body_empty', 'tool_userautodelete'));
            }
        }

        // Ignored role IDs.
        if ($this->config->ignore_roles) {
            $ignoredroleids = explode(',', $this->config->ignore_roles);
            foreach ($ignoredroleids as $roleid) {
                if (!is_numeric($roleid)) {
                    $isvalid = false;
                    logger::error(get_string('error_invalid_role_id', 'tool_userautodelete', $roleid));
                }
            }
        }

        return $isvalid;
    }

    /**
     * Returns a list of user IDs that are always ignored
     *
     * @return array List of user IDs to ignore
     * @throws \dml_exception
     */
    public function get_ignored_user_ids(): array {
        global $CFG, $DB;

        // Always ignore site admins.
        $ignoreduserids = explode(',', $CFG->siteadmins);

        // Ignore the guest user.
        $guestuserid = $DB->get_field('user', 'id', ['username' => 'guest']);
        if ($guestuserid) {
            $ignoreduserids[] = $guestuserid;
        }

        return $ignoreduserids;
    }

    /**
     * Returns a list of role IDs that are always ignored
     *
     * @return array List of role IDs to ignore
     * @throws \dml_exception
     */
    public function get_ignored_role_ids(): array {
        $ignoredroleids = [];
        if ($ignoredroleidsraw = get_config('tool_userautodelete', 'ignore_roles')) {
            foreach (explode(',', $ignoredroleidsraw) as $roleid) {
                $ignoredroleids[] = (int) trim($roleid);
            }
        }

        return $ignoredroleids;
    }

    /**
     * Searches for users that have been inactive long enough that they passed
     * the warning threshold, have not yet been notified, but haven't been
     * inactive long enough to be deleted right away.
     *
     * @return \stdClass[] List of user records to warn
     * @throws \dml_exception
     */
    public function get_users_to_warn(): array {
        global $DB;

        // Calculate unix timestamp thresholds.
        $deletetime = time() - ($this->config->delete_threshold_days * DAYSECS);
        $notifytime = $deletetime + ($this->config->warning_threshold_days * DAYSECS);

        // Prepare SQL segments.
        $userfieldssql = join(', ', array_map(fn($name) => "u.{$name}", self::USER_RECORD_FIELDS));
        $ignoreduseridssql = join(',', self::get_ignored_user_ids() ?: [-1]);
        $ignoredroleidssql = join(',', self::get_ignored_role_ids() ?: [-1]);

        // Execute SQL query.
        return $DB->get_records_sql("
            SELECT {$userfieldssql}
            FROM {user} u
                LEFT JOIN {tool_userautodelete_mail} m ON u.id = m.userid
            WHERE
                u.deleted = 0 AND                       -- < User is not deleted.
                m.userid IS NULL AND                    -- < User has not been warned yet.
                u.id NOT IN ({$ignoreduseridssql}) AND  -- < User ID is not ignored.
                -- v User has not been assigned an ignored role.
                NOT EXISTS (
                    SELECT 1
                    FROM {role_assignments} ra
                    WHERE
                        ra.userid = u.id AND
                        ra.roleid IN ({$ignoredroleidssql})
                ) AND (
                    -- v Users that have never logged in (compare timecreated).
                    (u.lastaccess = 0 AND u.timecreated < :notifytime1 AND u.timecreated > :deletetime1)
                    OR
                    -- v Users that have logged in at least one time (compare lastaccess).
                    (u.lastaccess > 0 AND u.lastaccess < :notifytime2 AND u.lastaccess > :deletetime2)
                )
        ORDER BY u.lastaccess ASC
        ", [
            'deletetime1' => $deletetime,
            'deletetime2' => $deletetime,
            'notifytime1' => $notifytime,
            'notifytime2' => $notifytime,
        ]);
    }

    /**
     * Searches for users that have been inactive long enough that they should
     * be deleted right away.
     *
     * @return \stdClass[] List of user records to delete
     * @throws \dml_exception
     */
    public function get_users_to_delete(): array {
        global $DB;

        // Calculate unix timestamp threshold.
        $deletetime = time() - ($this->config->delete_threshold_days * DAYSECS);

        // Prepare SQL segments.
        $userfieldssql = join(', ', array_map(fn($name) => "u.{$name}", self::USER_RECORD_FIELDS));
        $ignoreduseridssql = join(',', self::get_ignored_user_ids() ?: [-1]);
        $ignoredroleidssql = join(',', self::get_ignored_role_ids() ?: [-1]);

        // Execute SQL query.
        return $DB->get_records_sql("
            SELECT {$userfieldssql}
            FROM {user} u
            WHERE
                u.deleted = 0 AND                       -- < User is not deleted.
                u.id NOT IN ({$ignoreduseridssql}) AND  -- < User ID is not ignored.
                -- v User has not been assigned an ignored role.
                NOT EXISTS (
                    SELECT 1
                    FROM {role_assignments} ra
                    WHERE
                        ra.userid = u.id AND
                        ra.roleid IN ({$ignoredroleidssql})
                ) AND (
                    -- v Users that have never logged in (compare timecreated).
                    (u.lastaccess = 0 AND u.timecreated < :deletetime1)
                    OR
                    -- v Users that have logged in at least one time (compare lastaccess).
                    (u.lastaccess > 0 AND u.lastaccess < :deletetime2)
                )
            ORDER BY u.lastaccess ASC
        ", [
            'deletetime1' => $deletetime,
            'deletetime2' => $deletetime,
        ]);
    }

    /**
     * Searches for users that have been inactive and fall into the warning /
     * notification time period. Sends a warning mail and keeps track of mails
     * sent.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function find_and_notify_inactive_users(): void {
        global $DB;

        if (!$this->config->warning_email_enable) {
            logger::info(get_string('warning_email_disabled_skipping', 'tool_userautodelete'));
            return;
        }

        // Get users that have been inactive for the configured time but have not been notified yet.
        $userstonotify = $this->get_users_to_warn();
        if (empty($userstonotify)) {
            logger::info(get_string('no_users_to_warn', 'tool_userautodelete'));
            return;
        } else {
            logger::info(get_string('users_to_warn_a', 'tool_userautodelete', count($userstonotify)));
        }

        // Notify users.
        foreach ($userstonotify as $user) {
            if (!email_to_user(
                $user,
                get_admin(),
                $this->config->warning_email_subject,
                html_to_text(nl2br($this->config->warning_email_body)),
                $this->config->warning_email_body
            )) {
                logger::error(get_string('error_sending_warning_mail_to_user', 'tool_userautodelete', $user->id));
                continue;
            }

            // Log the sent mail.
            $DB->insert_record('tool_userautodelete_mail', [
                'userid' => $user->id,
                'timesent' => time(),
            ]);
            logger::info(get_string('warning_email_sent_to_user', 'tool_userautodelete', $user->id));
        }
    }

    /**
     * Identifies users that were inactive for at least the configured threshold
     * and deletes them.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function delete_inactive_users(): void {
        // Get users that have been inactive long enough that they should be deleted.
        $userstodelete = $this->get_users_to_delete();
        if (empty($userstodelete)) {
            logger::info(get_string('no_users_to_delete', 'tool_userautodelete'));
            return;
        } else {
            logger::info(get_string('users_to_delete_a', 'tool_userautodelete', count($userstodelete)));
        }

        // Delete users.
        foreach ($userstodelete as $user) {
            // Send deletion mail if enabled.
            if ($this->config->delete_email_enable) {
                if (!email_to_user(
                    $user,
                    get_admin(),
                    $this->config->delete_email_subject,
                    html_to_text(nl2br($this->config->delete_email_body)),
                    $this->config->delete_email_body
                )) {
                    logger::error(get_string('error_sending_delete_mail_to_user', 'tool_userautodelete', $user->id));
                } else {
                    logger::info(get_string('delete_email_sent_to_user', 'tool_userautodelete', $user->id));
                }
            }

            // Perform deletion.
            if (!delete_user($user)) {
                logger::error(get_string('error_deleting_user', 'tool_userautodelete', $user->id));
                continue;
            } else {
                logger::info(get_string('user_deleted', 'tool_userautodelete', $user->id));
            }

            // Anonymize user record if enabled.
            if ($this->config->anonymize_user_data) {
                $this->anonymize_user_record($user->id);
            }
        }
    }

    /**
     * Anonymizes an user record fully.
     *
     * @param int $userid ID of the user record to anonymize
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function anonymize_user_record(int $userid): void {
        global $DB;

        if ($DB->update_record('user', [
            'id' => $userid,
            'username' => "DELETED-USER-{$userid}",
            'password' => '',
            'idnumber' => '',
            'firstname' => 'DELETED',
            'lastname' => 'DELETED',
            'email' => "DELETED-USER-{$userid}@localhost",
            'phone1' => '',
            'phone2' => '',
            'institution' => '',
            'department' => '',
            'address' => '',
            'city' => '',
            'country' => '',
            'lastip' => '',
            'secret' => '',
            'picture' => 0,
            'description' => '',
            'imagealt' => '',
            'lastnamephonetic' => '',
            'firstnamephonetic' => '',
            'middlename' => '',
            'alternatename' => '',
            'moodlenetprofile' => '',
        ])) {
            logger::info(get_string('user_anonymized', 'tool_userautodelete', $userid));
        } else {
            logger::error(get_string('error_anonymizing_user', 'tool_userautodelete', $userid));
        }
    }

    /**
     * Performs all necessary cleanup tasks
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function cleanup(): void {
        global $DB;

        // Remove all users from the mail table that have been deleted or have signed in in the meantime.
        $deletetime = time() - ($this->config->delete_threshold_days * DAYSECS);
        $notifytime = $deletetime + ($this->config->warning_threshold_days * DAYSECS);
        $recoveredusers = $DB->get_records_sql("
            SELECT u.id, u.deleted
            FROM {tool_userautodelete_mail} m
                INNER JOIN {user} u ON u.id = m.userid
            WHERE
                u.deleted = 1 OR
                u.lastaccess > :notifytime
        ", [
            'notifytime' => $notifytime,
        ]);

        // Drop recovered users from the internal state table and log.
        foreach ($recoveredusers as $user) {
            $DB->delete_records('tool_userautodelete_mail', ['userid' => $user->id]);
            if (!$user->deleted) {
                logger::info(get_string('user_recovered', 'tool_userautodelete', $user->id));
            }
        }
    }

}
