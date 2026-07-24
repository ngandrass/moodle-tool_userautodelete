<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * User action that sends a mail to a user.
 *
 * @package     userdeleteaction_mail
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_mail;

use core\lang_string;
use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\local\variable_resolver;
use tool_userautodelete\process;
use userdeleteaction_mail\local\type\recipient;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * User action that sends a mail to a user.
 */
class userdeleteaction extends \tool_userautodelete\userdeleteaction {
    /**
     * Returns the name of this filter sub-plugin, e.g., 'suspend' for 'userdeleteaction_suspend'
     *
     * @return string The name of this filter sub-plugin
     */
    public static function get_plugin_name(): string {
        return 'mail';
    }

    /**
     * Returns a font-awesome icon CSS class string that is shown in the UI for
     * this action sub-plugin type.
     *
     * @return string A font-awesome icon CSS class string combination
     */
    public static function get_icon_class(): string {
        return 'fa-solid fa-envelope';
    }

    /**
     * Returns an URL to additional documentation for this sub-plugin, if
     * available. When this URL is set, an additional button to open the linked
     * documentation will be shown in the sub-plugin instance settings form.
     *
     * @return \moodle_url|null URL to the sub-plugin specific documentation, or
     * null if no additional documentation is available
     */
    public static function get_help_url(): ?\moodle_url {
        return new \moodle_url("https://moodleuserlifecycle.gandrass.de/actions/mail/");
    }

    /**
     * Returns a descriptive string of this action instance's settings to be shown in the UI
     *
     * This should be a human-readable string that describes the actual settings
     * of this action instance, e.g., 'Mail subject' for an action instance that
     * sends users an email with a subject defined in the instance settings.
     *
     * If no settings are defined, this function can simply return an empty string.
     *
     * @return string A descriptive string of this action instance's settings to be shown in the UI
     */
    public function get_instance_details(): string {
        return $this->get_instance_setting('subject') ?? '';
    }

    /**
     * Validates the given instance settings and returns per-key error messages.
     *
     * Checks subject and message for unknown variable references and returns a
     * localized error message for each field that contains invalid variables.
     *
     * @param array $settings Associative array of setting key-value pairs to validate
     * @return string[] Associative array of setting key => localized error message
     * @throws \coding_exception
     */
    public function validate_instance_settings_data(array $settings): array {
        $errors = [];
        $ctx = static::get_variable_context();

        foreach (['subject', 'message'] as $key) {
            $value = $settings[$key] ?? null;

            // Handle editor-type values that arrive as arrays with a 'text' key.
            if (is_array($value)) {
                $value = $value['text'] ?? '';
            }

            if (!empty($value)) {
                $resolvedvalue = variable_resolver::resolve($value, $ctx);
                if (variable_resolver::has_unresolved_variables($resolvedvalue)) {
                    $errors[$key] = get_string(
                        'error_unknown_variables',
                        'userdeleteaction_mail',
                        implode(', ', variable_resolver::get_unresolved_variables($resolvedvalue))
                    );
                }
            }
        }

        // Conditionally require customrecipient when recipient is 'custom'.
        $recipienttype = recipient::tryFrom($settings['recipient'] ?? '');
        if ($recipienttype === recipient::CUSTOM_MAIL) {
            if (empty($settings['customrecipient'])) {
                $errors['customrecipient'] = get_string('error_customrecipient_required', 'userdeleteaction_mail');
            } else if (!validate_email($settings['customrecipient'])) {
                $errors['customrecipient'] = get_string('error_customrecipient_invalid', 'userdeleteaction_mail');
            }
        }

        return $errors;
    }

    /**
     * Validates this action instance for runtime readiness.
     *
     * In addition to the base required-setting check, enforces that a non-empty,
     * syntactically valid email address is configured when the recipient type is
     * set to 'custom'.
     *
     * @return string|null Null if valid; a localized error string otherwise.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function validate(): string|null {
        if ($error = parent::validate()) {
            return $error;
        }

        $recipienttype = recipient::from($this->get_instance_setting('recipient'));
        $customrecipient = $this->get_instance_setting('customrecipient');

        if ($recipienttype === recipient::CUSTOM_MAIL) {
            if (empty($customrecipient)) {
                return get_string('error_customrecipient_required', 'userdeleteaction_mail');
            }
            if (!validate_email($customrecipient)) {
                return get_string('error_customrecipient_invalid', 'userdeleteaction_mail');
            }
        }

        return null;
    }

    /**
     * Executes this action for a given user deletion process
     *
     * @param process $process The user deletion process to execute this action for
     * @return bool True if the action was executed successfully, false otherwise
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function execute(process $process): bool {
        $user = \core_user::get_user($process->userid);
        $subject = $this->get_instance_setting('subject');
        $message = $this->get_instance_setting('message');

        if (!$user) {
            return false;
        }
        if (empty($subject) || empty($message)) {
            throw new \moodle_exception('subject_or_message_empty', 'userdeleteaction_mail');
        }

        // Build the variable context and resolve references in subject and message.
        $ctx = static::get_variable_context($user);
        $subject = variable_resolver::resolve($subject, $ctx);
        $message = variable_resolver::resolve($message, $ctx);

        $recipienttype = recipient::from($this->get_instance_setting('recipient'));

        if ($recipienttype === recipient::ADMINS) {
            foreach (get_admins() as $admin) {
                $sent = email_to_user(
                    user: $admin,
                    from: get_admin(),
                    subject: $subject,
                    messagetext: html_to_text(nl2br($message)),
                    messagehtml: $message
                );

                if (!$sent) {
                    return false;
                }
            }

            return true;
        }

        if ($recipienttype === recipient::CUSTOM_MAIL) {
            // Build dummy receiving user.
            $customuser = clone \core_user::get_noreply_user();
            $customuser->email = $this->get_instance_setting('customrecipient');

            return email_to_user(
                user: $customuser,
                from: get_admin(),
                subject: $subject,
                messagetext: html_to_text(nl2br($message)),
                messagehtml: $message
            );
        }

        if ($recipienttype === recipient::USER) {
            if (!validate_email($user->email)) {
                return false;
            }

            return email_to_user(
                user: $user,
                from: get_admin(),
                subject: $subject,
                messagetext: html_to_text(nl2br($message)),
                messagehtml: $message
            );
        }

        // Safeguard.
        return false;
    }

    /**
     * Returns an array of descriptors for every setting this filter sub-plugin
     * defines and exposes.
     *
     * @return instance_setting_descriptor[] An array of setting descriptors
     * @throws \coding_exception
     */
    public static function instance_setting_descriptors(): array {
        return [
            new instance_setting_descriptor(
                key: 'recipient',
                title: new lang_string('setting_recipient', 'userdeleteaction_mail'),
                type: PARAM_ALPHA,
                required: true,
                default: recipient::USER->value,
                choices: [
                    recipient::USER->value        => new lang_string('setting_recipient_user', 'userdeleteaction_mail'),
                    recipient::ADMINS->value      => new lang_string('setting_recipient_admins', 'userdeleteaction_mail'),
                    recipient::CUSTOM_MAIL->value => new lang_string('setting_recipient_custom', 'userdeleteaction_mail'),
                ],
                mformtype: 'select',
            ),
            new instance_setting_descriptor(
                key: 'subject',
                title: new lang_string('setting_subject', 'userdeleteaction_mail'),
                type: PARAM_TEXT,
                required: true,
                mformtype: 'text'
            ),
            new instance_setting_descriptor(
                key: 'message',
                title: new lang_string('setting_message', 'userdeleteaction_mail'),
                type: PARAM_RAW,
                required: true,
                mformtype: 'editor',
            ),
            new instance_setting_descriptor(
                key: 'customrecipient',
                title: new lang_string('setting_customrecipient', 'userdeleteaction_mail'),
                type: PARAM_EMAIL,
                required: false,
                mformtype: 'text',
            ),
        ];
    }

    /**
     * Builds the variable context used for validation and runtime email rendering.
     *
     * Pass a user to build a runtime context with real values. Pass null to
     * build a validation context with empty placeholders for all supported
     * variables.
     *
     * @param \stdClass|null $user The target user record for runtime context, or null for validation context
     * @return array Context that maps all supported variables to placeholder or runtime values
     * @throws \coding_exception
     */
    protected static function get_variable_context(?\stdClass $user = null): array {
        global $CFG, $SITE;

        // Prepare creation and access times for user. Fallback to timecreated if user never accessed the site.
        $timecreated = 0;
        $lastaccess = 0;

        if ($user !== null) {
            $timecreated = $user->timecreated;
            $lastaccess = $user->lastaccess ?: $timecreated;
        }

        return [
            'user' => [
                'id' => $user->id ?? '',
                'idnumber' => $user->idnumber ?? '',
                'username' => $user->username ?? '',
                'firstname' => $user->firstname ?? '',
                'lastname' => $user->lastname ?? '',
                'email' => $user->email ?? '',
                'institution' => $user->institution ?? '',
                'timecreated' => userdate($timecreated, get_string('strftimedatetime', 'langconfig')),
                'lastaccess' => userdate($lastaccess, get_string('strftimedatetime', 'langconfig')),
                'lastaccessrelative' => format_time(time() - $lastaccess),
                'lastip' => $user->lastip ?? '',
                'city' => $user->city ?? '',
                'country' => $user->country ?? '',
            ],
            'site' => [
                'name' => $SITE->fullname,
                'shortname' => $SITE->shortname,
                'supportemail' => $CFG->supportemail ?? '',
            ],
            'urls' => [
                'home' => (new \moodle_url('/'))->out(false),
                'login' => (new \moodle_url('/login/index.php'))->out(false),
                'profile' => (new \moodle_url("/user/profile.php?id={$user?->id}"))->out(false),
                'support' => (new \moodle_url('/user/contactsitesupport.php'))->out(false),
            ],
        ];
    }
}
