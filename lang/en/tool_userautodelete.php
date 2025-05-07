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
 * Plugin strings are defined here.
 *
 * @package     tool_userautodelete
 * @category    string
 * @copyright   2025 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

// General.
$string['pluginname'] = 'Automatic User Deletion';
$string['privacy:metadata'] = 'TODO';
$string['task_check_and_delete_users'] = 'Check and delete users';

// Admin settings.
$string['setting_enable'] = 'Enable plugin';
$string['setting_enable_desc'] = 'Enables or disables the plugin globally. If this is disabled, no action will be performed.';
$string['setting_ignore_siteadmins'] = 'Ignore site admins';
$string['setting_ignore_siteadmins_desc'] = 'If enabled, all users that are global site admins will never be deleted.';
$string['setting_ignore_roles'] = 'Ignored roles';
$string['setting_ignore_roles_desc'] = 'If enabled, all users that have one of the selected roles will never be deleted.';

$string['setting_header_user_deletion'] = 'User Deletion';
$string['setting_header_user_deletion_desc'] = 'Configuration of the automatic user deletion.';
$string['setting_delete_threshold_days'] = 'Deletion threshold';
$string['setting_delete_threshold_days_desc'] = 'The number of days of inactivity after which a user will be deleted. The deletion will only be performed if the user has not logged in for this number of days.';
$string['setting_delete_email_enable'] = 'Deletion notifications';
$string['setting_delete_email_enable_desc'] = 'If enabled, users will receive a final mail and are immediately deleted afterwards. This can be used to send confirmations of user deletion.';
$string['setting_delete_email_subject'] = 'Mail subject';
$string['setting_delete_email_subject_desc'] = 'The subject of the deletion notification email.';
$string['setting_delete_email_subject_default'] = 'Your account was deleted';
$string['setting_delete_email_body'] = 'Mail body';
$string['setting_delete_email_body_desc'] = 'The body of the deletion notification email.';
$string['setting_delete_email_body_default'] = '<h1>TODO TODO TODO!</h1>Your account was deleted :(';

$string['setting_header_deletion_warning'] = 'Deletion Warnings';
$string['setting_header_deletion_warning_desc'] = 'Configuration of the deletion warning emails. Warning emails are sent a number of days before the user is deleted. This informs users that their account will be deleted soon and gives them time to perform a login to keep their accounts active.';
$string['setting_warning_email_enable'] = 'Deletion warnings';
$string['setting_warning_email_enable_desc'] = 'If enabled, users will receive a warning email a configurable number of days before they are deleted.';
$string['setting_warning_threshold_days'] = 'Warning threshold';
$string['setting_warning_threshold_days_desc'] = 'The number of days before the user is deleted that the warning email will be sent. This is relative to the deletion threshold.';
$string['setting_warning_email_subject'] = 'Mail subject';
$string['setting_warning_email_subject_desc'] = 'The subject of the warning email.';
$string['setting_warning_email_subject_default'] = 'Your account will be deleted soon';
$string['setting_warning_email_body'] = 'Mail body';
$string['setting_warning_email_body_desc'] = 'The body of the warning email.';
$string['setting_warning_email_body_default'] = '<h1>TODO TODO TODO!</h1>Your account will be deleted soon! Please login to keep your account active.';
