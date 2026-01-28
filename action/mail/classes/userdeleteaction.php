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

use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\process;

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
    public function get_plugin_name(): string {
        return 'mail';
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
        // Fetch and validate user and message data.
        $user = \core_user::get_user($process->userid);
        $subject = $this->get_instance_setting('subject');
        $message = $this->get_instance_setting('message');

        if (!$user || !$user->email) {
            return false;
        }
        if (empty($subject) || empty($message)) {
            throw new \moodle_exception('subject_or_message_empty', 'userdeleteaction_mail');
        }

        // Send the email.
        return email_to_user(
            user: $user,
            from: get_admin(),
            subject: $subject,
            messagetext: html_to_text(nl2br($message)),
            messagehtml: $message
        );
    }

    /**
     * Returns an array of descriptors for every setting this filter sub-plugin
     * defines and exposes.
     *
     * @return instance_setting_descriptor[] An array of setting descriptors
     */
    public function instance_setting_descriptors(): array {
        return [
            new instance_setting_descriptor('subject', PARAM_TEXT, true),
            new instance_setting_descriptor('message', PARAM_TEXT, true),
        ];
    }
}
