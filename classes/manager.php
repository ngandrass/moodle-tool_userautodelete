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
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function execute(): void {
        // Validate config and check if the plugin is enabled.
        if (!$this->validate_config()) {
            logger::error(get_string('error_invalid_config', 'tool_userautodelete'));
            return;
        }

        if (!$this->config->enable) {
            logger::info(get_string('plugin_disabled_skipping_execution', 'tool_userautodelete'));
            return;
        }

        // Execute the main workflow.
        $this->find_and_notify_inactive_users();
        $this->delete_inactive_users();
        $this->cleanup();
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

        return $isvalid;
    }

    /**
     * Searches for users that have been inactive and fall into the warning /
     * notification time period. Sends a warning mail and keeps track of mails
     * sent.
     *
     * @return void
     * @throws \coding_exception
     */
    protected function find_and_notify_inactive_users(): void {
        global $DB;

        if (!$this->config->warning_email_enable) {
            logger::info(get_string('log_warning_email_disabled', 'tool_userautodelete'));
            return;
        }

        // Get users that have been inactive for the configured time but have not been notified yet.
        $deletetime = time() - ($this->config->delete_threshold_days * DAYSECS);
        $notifytime = $deletetime + ($this->config->warning_threshold_days * DAYSECS);

        $userstonotify = $DB->get_records_sql("
            SELECT u.*
            FROM {user} u
                LEFT JOIN {tool_autouserdelete_mail} m ON u.id = m.userid
            WHERE
                u.deleted = 0 AND
                m.userid IS NULL AND
                u.lastaccess < :notifytime AND
                u.lastaccess > :deletetime
        ", [
            'deletetime' => $deletetime,
            'notifytime' => $notifytime,
        ]);

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
            $DB->insert_record('tool_autouserdelete_mail', [
                'userid' => $user->id,
                'timesent' => time(),
            ]);
            logger::info(get_string('log_warning_email_sent', 'tool_userautodelete', $user->id));
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
        global $DB;

        // Identify users to delete.
        $deletetime = time() - ($this->config->delete_threshold_days * DAYSECS);
        $userstodelete = $DB->get_records_sql("
            SELECT *
            FROM {user}
            WHERE
                u.deleted = 0 AND
                u.lastaccess < :deletetime
        ", [
            'deletetime' => $deletetime,
        ]);

        // Delete users.
        foreach ($userstodelete as $user) {
            // Send deletion mail if enabled.
            if ($this->config->delete_mail_enable) {
                if (!email_to_user(
                    $user,
                    get_admin(),
                    $this->config->delete_email_subject,
                    html_to_text(nl2br($this->config->delete_email_body)),
                    $this->config->delete_email_body
                )) {
                    logger::error(get_string('error_sending_delete_mail_to_user', 'tool_userautodelete', $user->id));
                } else {
                    logger::info(get_string('log_delete_email_sent', 'tool_userautodelete', $user->id));
                }
            }

            // Perform deletion.
            if (!delete_user($user)) {
                logger::error(get_string('error_deleting_user', 'tool_userautodelete', $user->id));
                continue;
            }

            logger::info(get_string('log_user_deleted', 'tool_userautodelete', $user->id));
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
            FROM {tool_autouserdelete_mail} m
                INNER JOIN {user} u ON u.id = m.userid
            WHERE
                u.deleted = 1 OR
                u.lastaccess > :notifytime
        ", [
            'notifytime' => $notifytime,
        ]);

        // Drop recovered users from the internal state table and log.
        foreach ($recoveredusers as $user) {
            $DB->delete_records('tool_autouserdelete_mail', ['userid' => $user->id]);
            logger::info(get_string('log_user_recovered', 'tool_userautodelete', $user->id));
        }
    }

}
