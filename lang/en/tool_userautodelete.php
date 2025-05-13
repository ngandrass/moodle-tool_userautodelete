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

// @codingStandardsIgnoreFile

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

// General.
$string['pluginname'] = 'Automatic user deletion';
$string['privacy:metadata'] = 'This plugin does not store any data personal identifiable user information.';
$string['task_check_and_delete_users'] = 'Check and delete users';

// Task execution.
$string['plugin_disabled_skipping_execution'] = 'Plugin is disabled globally, skipping execution.';
$string['warning_email_disabled_skipping'] = 'Sending warning emails is disabled, skipping step ...';
$string['warning_email_sent_to_user'] = 'Warning email sent to user with ID {$a}';
$string['delete_email_sent_to_user'] = 'Deletion notification email sent to user with ID {$a}';
$string['user_anonymized'] = 'User with ID {$a} was anonymized';
$string['user_deleted'] = 'User with ID {$a} was deleted';
$string['user_recovered'] = 'User with ID {$a} was previously flagged as inactive but returned. User will not be deleted.';
$string['users_to_warn_a'] = 'Found {$a} users eligible for a warning email.';
$string['no_users_to_warn'] = 'No users eligible for a warning email found.';
$string['users_to_delete_a'] = 'Found {$a} users eligible for deletion.';
$string['no_users_to_delete'] = 'No users eligible for deletion found.';

// Admin settings.
$string['setting_plugin_desc'] = 'This plugin automatically deletes users that have not logged in for a configurable number of days. This is useful to keep your database clean and remove old accounts that are no longer needed. The plugin can be configured to send warning emails a number of days before the user is deleted. This gives users the chance to log back in and keep their accounts active. It furthermore supports deleting users in a GDPR-compliant way, leaving no trace of personally identifiable information (PII) inside the user record.';
$string['setting_enable'] = 'Enable plugin';
$string['setting_enable_desc'] = 'Enables or disables the plugin globally. If this is disabled, no action will be performed.';
$string['setting_ignore_siteadmins'] = 'Ignore site admins';
$string['setting_ignore_siteadmins_desc'] = 'Global site admins can not be deleted. Revoke the admin role to include in automatic deletion process.';
$string['setting_ignore_roles'] = 'Ignored roles';
$string['setting_ignore_roles_desc'] = 'All users that have at least one of the selected roles assigned will never be deleted.';
$string['setting_task_execution_interval'] = 'Check interval';
$string['setting_task_execution_interval_desc'] = 'The check for inactive users is performed using a scheduled task that is executed via the Moodle cron. You can configure the interval at which it runs by clicking the following button.';
$string['setting_task_execution_interval_button'] = 'Configure check interval';
$string['setting_task_logs'] = 'Logs';
$string['setting_task_logs_desc'] = 'This plugin logs all actions it performs inside the respective scheduled task logs. You can access the logs from all previous runs by clicking the following button.';
$string['setting_task_logs_button'] = 'View logs';

$string['setting_header_user_deletion'] = 'User deletion';
$string['setting_header_user_deletion_desc'] = 'Configuration of the automatic user deletion. Users that have not logged in for at least the configured number of days and are not ignored by the above settings will be automatically deleted.';
$string['setting_delete_threshold_days'] = 'Deletion threshold';
$string['setting_delete_threshold_days_desc'] = 'The number of days of inactivity after which a user will be deleted. The deletion will only be performed if the user has not logged in for this number of days.';
$string['setting_anonymize_user_data'] = 'Anonymize deleted users';
$string['setting_anonymize_user_data_desc'] = 'When deleting a user, Moodle keeps parts of the user record inside its database, including firstname, lastname, e-mail address, and other sensitive information. This conflicts with the general data protection regulation (GDPR) of the EU. If this setting is enabled, all remaining user data will be anonymized, hereby fully eliminating the possibility trace back a deleted user account to the previous owner.';
$string['setting_delete_email_enable'] = 'Deletion notifications';
$string['setting_delete_email_enable_desc'] = 'If enabled, users will receive a final mail and are immediately deleted afterwards. This can be used to send confirmations of user deletion.';
$string['setting_delete_email_subject'] = 'Mail subject';
$string['setting_delete_email_subject_desc'] = 'The subject of the deletion notification email.';
$string['setting_delete_email_subject_default'] = 'Your account was deleted';
$string['setting_delete_email_body'] = 'Mail body';
$string['setting_delete_email_body_desc'] = 'The body of the deletion notification email.';
$string['setting_delete_email_body_default'] = '<p>Hello,</p><p>your account on our site was deleted due to inactivity. If you wish to continue using our service, please create a new account.</p><p>Kind regards</p>';

$string['setting_header_deletion_warning'] = 'Deletion warnings';
$string['setting_header_deletion_warning_desc'] = 'Configuration of the deletion warning emails. Warning emails are sent a number of days before the user is deleted. This informs users that their account will be deleted soon and gives them time to perform a login to keep their accounts active.';
$string['setting_warning_email_enable'] = 'Deletion warnings';
$string['setting_warning_email_enable_desc'] = 'If enabled, users will receive a warning email a configurable number of days before they are deleted.';
$string['setting_warning_threshold_days'] = 'Warning threshold';
$string['setting_warning_threshold_days_desc'] = 'The number of days before the user is deleted that the warning email will be sent. This is relative to the deletion threshold.';
$string['setting_warning_email_subject'] = 'Mail subject';
$string['setting_warning_email_subject_desc'] = 'The subject of the warning email.';
$string['setting_warning_email_subject_default'] = 'Your account will be deleted soon - Action required!';
$string['setting_warning_email_body'] = 'Mail body';
$string['setting_warning_email_body_desc'] = 'The body of the warning email.';
$string['setting_warning_email_body_default'] = '<p>Hello,</p><p>your account on our site has been inactive for a long period of time. To keep your account, please <strong>log back in now to prevent your account from being deleted</strong> according to our data protection policy within the next days.</p><p>If you wish your account to be deleted, you can ignore this message.</p><p>Kind regards</p>';

// Errors.
$string['error_invalid_config_aborting'] = 'Invalid plugin configuration found. Aborting ...';
$string['error_invalid_role_id'] = 'Invalid role ID {$a} found in the ignored roles list. This is most likely a bug in the plugin. Please report this to the plugin maintainer.';
$string['error_delete_threshold_days_negative'] = 'The deletion threshold days must be greater than 0.';
$string['error_warning_threshold_days_negative'] = 'The warning threshold days must be greater than 0.';
$string['error_warning_threshold_days_geq_delete'] = 'The warning threshold days must be less than the deletion threshold days.';
$string['error_warning_email_subject_empty'] = 'The warning email subject must not be empty.';
$string['error_warning_email_body_empty'] = 'The warning email body is empty.';
$string['error_delete_email_subject_empty'] = 'The deletion email subject must not be empty.';
$string['error_delete_email_body_empty'] = 'The deletion email body is empty.';
$string['error_sending_warning_mail_to_user'] = 'Sending warning email to user with ID {$a} failed.';
$string['error_sending_delete_mail_to_user'] = 'Sending deletion notification email to user with ID {$a} failed.';
$string['error_anonymizing_user'] = 'Anonymizing user with ID {$a} failed.';
$string['error_deleting_user'] = 'Deleting user with ID {$a} failed.';
