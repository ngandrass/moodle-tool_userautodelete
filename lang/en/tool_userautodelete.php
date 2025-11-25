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
$string['back_to_settings'] = 'Back to settings';
$string['task_check_and_delete_users'] = 'Check and delete users';
$string['reltime_prefix_ago'] = '';
$string['reltime_suffix_ago'] = 'ago';
$string['reltime_prefix_in'] = 'in';
$string['reltime_suffix_in'] = '';
$string['last_check'] = 'Last check';
$string['next_check'] = 'Next check';
$string['next_check_never'] = 'Never (plugin disabled)';
$string['next_check_would'] = 'Would check';
$string['affected_users'] = 'Affected users';
$string['inactivity_warning'] = 'Inactivity warning';
$string['warned'] = 'Warned';
$string['deleted'] = 'Deleted';
$string['recovered'] = 'Recovered';

// Privacy.
$string['privacy:metadata:tool_userautodelete_mail'] = 'Information about users that received an inactivity warning email.';
$string['privacy:metadata:tool_userautodelete_mail:userid'] = 'The ID of the user that received the email.';
$string['privacy:metadata:tool_userautodelete_mail:timesent'] = 'The time the email was sent.';

// Task execution.
$string['plugin_disabled_skipping_execution'] = 'Plugin is disabled globally, skipping execution.';
$string['warning_email_disabled'] = 'Warning emails are disabled.';
$string['warning_email_disabled_skipping'] = 'Sending warning emails is disabled, skipping step ...';
$string['warning_email_sent_to_user'] = 'Warning email sent to user with ID {$a}';
$string['delete_email_sent_to_user'] = 'Deletion notification email sent to user with ID {$a}';
$string['user_anonymized'] = 'User with ID {$a} was anonymized';
$string['user_deleted'] = 'User with ID {$a} was deleted';
$string['user_recovered'] = 'User with ID {$a} was previously flagged as inactive but returned. User will not be deleted.';
$string['users_to_warn'] = 'Users to warn';
$string['users_to_warn_a'] = 'Found {$a} users eligible for a warning email.';
$string['no_users_to_warn'] = 'No users eligible for a warning email found.';
$string['users_to_delete'] = 'Users to delete';
$string['users_to_delete_a'] = 'Found {$a} users eligible for deletion.';
$string['no_users_to_delete'] = 'No users eligible for deletion found.';

// Admin settings.
$string['setting_plugin_desc'] = 'This plugin automatically deletes users that have not logged in for a configurable number of days. This is useful to keep your database clean and remove old accounts that are no longer needed. The plugin can be configured to send warning emails a number of days before the user is deleted. This gives users the chance to log back in and keep their accounts active. It furthermore supports deleting users in a GDPR-compliant way, leaving no trace of personally identifiable information (PII) inside the user record.';
$string['setting_enable'] = 'Enable plugin';
$string['setting_enable_desc'] = 'Enables or disables the plugin globally. If this is disabled, no action will be performed.';
$string['setting_ignore_auths'] = 'Ignored auths';
$string['setting_ignore_auths_desc'] = 'All users that use one of the selected authentication methods will never be deleted. Note that only enabled auth plugins are listed here.';
$string['setting_ignore_siteadmins'] = 'Ignore site admins';
$string['setting_ignore_siteadmins_desc'] = 'Global site admins can not be deleted. Revoke the admin role to include in automatic deletion process.';
$string['setting_ignore_roles'] = 'Ignored roles';
$string['setting_ignore_roles_desc'] = 'All users that have at least one of the selected roles assigned will never be deleted.';
$string['setting_dryrun'] = 'Dry-run';
$string['setting_dryrun_desc'] = 'A dry-run allows you to list all users that would receive a warning message or would be deleted during the next check interval. This is useful to verify that the plugin is configured correctly and to see which users would be affected. The dry-run does not perform any actions and therefore is fully non-destructive!';
$string['setting_dryrun_button'] = 'Perform dry-run';
$string['setting_suspended_only'] = 'Suspended users only';
$string['setting_suspended_only_desc'] = 'If enabled, only users that are currently suspended will be considered for automatic deletion. All active users will be kept indefinitely unless this option is disabled. This can be useful if your user lifecycle is externally managed (e.g., via SSO) or contains a suspension phase before the user is finally deleted.';
$string['setting_task_execution_interval'] = 'Check interval';
$string['setting_task_execution_interval_desc'] = 'The check for inactive users is performed using a scheduled task that is executed via the Moodle cron. You can configure the interval at which it runs by clicking the following button.';
$string['setting_task_execution_interval_button'] = 'Configure check interval';
$string['setting_task_logs'] = 'Action Log';
$string['setting_task_logs_desc'] = 'This plugin logs all actions it performs (e.g., user deletion). You can access the logs of all previously performed actions by clicking the following button.';
$string['setting_task_logs_button'] = 'View action log';

$string['setting_header_user_deletion'] = 'User deletion';
$string['setting_header_user_deletion_desc'] = 'Configuration of the automatic user deletion. Users that have not logged in for at least the configured number of days and are not ignored by the above settings will be automatically deleted.';
$string['setting_delete_threshold_days'] = 'Deletion threshold';
$string['setting_delete_threshold_days_desc'] = 'The number of days of inactivity after which a user will be deleted. The deletion will only be performed if the user has not logged in for this number of days.';
$string['setting_anonymize_user_data'] = 'Anonymize deleted users';
$string['setting_anonymize_user_data_desc'] = 'When deleting a user, Moodle retains some parts of the user record inside the database by design. This includes first name, last name, e-mail address, last IP address, and other sensitive information. This may be a concern, depending on the specific data privacy policies of your organization or local legal interpretations. If this setting is enabled, all remaining user data will be anonymized, hereby fully eliminating the possibility trace back a deleted user account to the previous owner.';
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

// Page: info / dryrun
$string['dry_run'] = 'Dry-run';
$string['page_title_dryrun'] = 'Automatic user deletion (dry-run)';
$string['dry_run_explanation'] = 'This page shows the actions that would be performed during the next check interval. None of the actions have been actually performed yet!';
$string['current_configuration'] = 'Current configuration';
$string['current_configuration_table_desc'] = 'This table lists the next time a check for inactive users would be executed in the background as well as the configured thresholds for warning e-mails and user deletions. Additionally, user roles that are excluded from the deletion process are listed here.';
$string['dry_run_affected_users_desc'] = 'Users listed here will either receive a warning email or will be deleted right away. If no users are listed, no user has been inactive for longer than the configured thresholds, or the user is ignored according to the plugin configuration. Accounts that have been created but to which no user ever logged in are listed with their creation date <i>"Time created"</i> instead of the last login date <i>"Last access"</i>.';
$string['a_users_would_be_deleted'] = 'The following {$a} users would be deleted';
$string['a_users_would_be_warned'] = 'The following {$a} users would receive a warning email';

// Page: action logs
$string['action_log'] = 'Action log';
$string['action_log_explanation'] = 'This page lists all actions that have been performed by the automatic user deletion plugin. Every time a warning e-mail is sent, a user is finally deleted, or a user was marked as recovered after a previously sent warning e-mail, an entry in this action log is created. User inactivity checks that did not perform any actions are not logged.';
$string['inspecting_raw_logs'] = 'Inspecting raw logs';
$string['latest_actions'] = 'Latest actions';
$string['latest_actions_desc'] = 'This table lists the latest actions that have been performed by the automatic user deletion plugin. Checks without any action are excluded.';
$string['page_title_action_log'] = 'Automatic user deletion (action log)';
$string['view_scheduled_task_logs'] = 'View raw scheduled task logs';
$string['view_scheduled_task_logs_desc'] = 'The checks for inactive users are performed by a Moodle scheduled task, running in the background. If you experience problems with this plugin, you can inspect the raw logs of the scheduled task runs by clicking the button below.';

// Errors.
$string['error_invalid_config'] = 'Invalid plugin configuration found.';
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
