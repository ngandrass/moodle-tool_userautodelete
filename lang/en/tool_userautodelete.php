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
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreFile

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

$string['action'] = 'Action';
$string['action_is_invalid'] = 'This action is invalid and must be fixed before the workflow can be enabled. Please make sure that all required configuration fields of this action are properly filled.';
$string['actions'] = 'Actions';
$string['active_processes'] = '{$a} active process(es)';
$string['add_action'] = 'Add action';
$string['add_action_desc'] = 'Click on the type of action you want to add to the workflow step.';
$string['add_filter'] = 'Add filter';
$string['add_filter_desc'] = 'Click on the type of filter you want to add to the workflow step.';
$string['add_step'] = 'Add step';
$string['affected_users'] = 'Affected users';
$string['back_to_overview'] = 'Back to overview';
$string['back_to_settings'] = 'Back to settings';
$string['back_to_workflow'] = 'Back to workflow';
$string['can_not_activate_invalid_workflow'] = 'This workflow contains invalid steps and can therefore not be enabled right now. Please fix the issues and try again.';
$string['can_not_edit_active_workflow'] = 'Active workflows must be disabled before they can be edited.';
$string['create_new_workflow'] = 'Create new workflow';
$string['defaultworkflow_delete_step_desc'] = 'Deletes users that have been inactive for a long time.';
$string['defaultworkflow_delete_step_title'] = 'Delete inactive users';
$string['defaultworkflow_deletemail_message'] = '<p>Hello,</p><p>your account on our site was deleted due to inactivity. If you wish to continue using our service, please create a new account.</p><p>Kind regards</p>';
$string['defaultworkflow_deletemail_subject'] = 'Your account was deleted';
$string['defaultworkflow_desc'] = 'This is the default workflow that was created during plugin installation.';
$string['defaultworkflow_title'] = 'Default workflow';
$string['defaultworkflow_warning_step_desc'] = 'Sends a warning mail to inactive users. Users are selected by the given filter criteria.';
$string['defaultworkflow_warning_step_title'] = 'Warn inactive users';
$string['defaultworkflow_warningmail_message'] = '<p>Hello,</p><p>your account on our site has been inactive for a long period of time. To keep your account, please <strong>log back in now to prevent your account from being deleted</strong> according to our data protection policy within the next days.</p><p>If you wish your account to be deleted, you can ignore this message.</p><p>Kind regards</p>';
$string['defaultworkflow_warningmail_subject'] = 'Your account will be deleted soon - Action required!';
$string['delete_action'] = 'Delete action';
$string['delete_filter'] = 'Delete filter';
$string['delete_step'] = 'Delete step';
$string['delete_step_warning'] = 'You are about to delete a step. This will also delete all actions and filters that are part of it. Are you sure you want to continue?';
$string['delete_workflow'] = 'Delete workflow';
$string['delete_workflow_warning'] = '<p>You are about to <b>permanently delete a workflow</b>. This will remove all active users from this workflow and delete all associated steps, including their filters and actions. Are you sure that you want to continue?</p><p>Instead of deleting, you can simply disable an existing workflow on its workflow inspection page. This will stop users from being processed by this workflow without deleting the workflow definition and settings.</p>';
$string['deleted'] = 'Deleted';
$string['description'] = 'Description';
$string['disable_workflow'] = 'Disable workflow';
$string['disable_workflow_warning'] = '<p>You are about to disable the workflow listed below. No new users will be processed until the workflow is enabled again. This does not affect other active workflows. Disabling the workflow will also terminate all active user processes at their current state.</p><p>The workflow can be re-enabled at any point in the future, however, all applicable user will start from the first step again. Are you sure you want to disable this workflow now?</p>';
$string['dry_run'] = 'Dry-run';
$string['dry_run_explanation'] = '<p>Below you will find all users that would be ingested by this workflow upon execution. None of the users is actually ingested at this point, so you can safely inspect the list to verify that the workflow is configured correctly.</p><p>Please note, that a single user can only be part of a single workflow at any point in time. Therefore, only users that match the defined filter criteria of the first workflow step and are not yet part of any workflow are listed below.</p>';
$string['edit_action'] = 'Edit action';
$string['edit_filter'] = 'Edit filter';
$string['edit_step'] = 'Edit step';
$string['edit_workflow'] = 'Edit workflow';
$string['enable_workflow'] = 'Enable workflow';
$string['enable_workflow_warning'] = '<p>You are about to enable the workflow listed below. This will prevent any further changes to the workflow steps, actions, and filters until it is disabled again. The workflow can be disabled at any point in the future, however, all active user processes will be terminated at their current state when disabling a workflow.</p><p>Once a workflow is active, applicable users will be processed according to the workflow definition. Are you sure you want to enable this workflow now?</p>';
$string['filter'] = 'Filter';
$string['filter_log_entries'] = 'Filter log entries';
$string['filter_is_invalid'] = 'This filter is invalid and must be fixed before the workflow can be enabled. Please make sure that all required configuration fields of this filter are properly filled.';
$string['filters'] = 'Filters';
$string['finished'] = 'Finished';
$string['id'] = 'ID';
$string['inactivity_warning'] = 'Inactivity warning';
$string['last_check'] = 'Last check';
$string['log_filter_all_steps'] = 'All steps';
$string['log_filter_all_workflows'] = 'All workflows';
$string['log_filter_apply'] = 'Apply filter';
$string['log_filter_reset'] = 'Reset';
$string['logs'] = 'Logs';
$string['manage_workflow'] = 'Manage workflow';
$string['manage_workflow_desc'] = 'This page displays a single user lifecycle workflow and allows you to edit it. Each workflow is composed of steps that each have filters and actions. The filters define a set of criteria for user selection and timing. The actions define what should happen to the user accounts that enter the given step.';
$string['manage_workflows'] = 'Manage workflows';
$string['manage_workflows_desc'] = 'All currently existing workflows are listed here.';
$string['newworkflow_desc'] = 'This is a freshly created workflow';
$string['newworkflow_title'] = 'New workflow';
$string['next_check'] = 'Next check';
$string['next_check_never'] = 'Never (plugin disabled)';
$string['next_check_would'] = 'Would check';
$string['plugin_disabled_skipping_execution'] = 'Plugin is disabled globally, skipping execution.';
$string['pluginname'] = 'Automatic user deletion';
$string['privacy:metadata:tool_userautodelete_mail'] = 'Information about users that received an inactivity warning email.';
$string['privacy:metadata:tool_userautodelete_mail:timesent'] = 'The time the email was sent.';
$string['privacy:metadata:tool_userautodelete_mail:userid'] = 'The ID of the user that received the email.';
$string['process'] = 'Process';
$string['processes'] = 'Processes';
$string['recovered'] = 'Recovered';
$string['reltime_prefix_ago'] = '';
$string['reltime_prefix_in'] = 'in';
$string['reltime_suffix_ago'] = 'ago';
$string['reltime_suffix_in'] = '';
$string['selection_filters'] = 'Selection filters';
$string['setting_enable'] = 'Enable plugin';
$string['setting_enable_desc'] = 'Enables or disables the plugin globally. If this is disabled, no action will be performed.';
$string['setting_plugin_desc'] = 'This plugin automatically deletes users that have not logged in for a configurable number of days. This is useful to keep your database clean and remove old accounts that are no longer needed. The plugin can be configured to send warning emails a number of days before the user is deleted. This gives users the chance to log back in and keep their accounts active. It furthermore supports deleting users in a GDPR-compliant way, leaving no trace of personally identifiable information (PII) inside the user record.';
$string['setting_task_execution_interval'] = 'Check interval';
$string['setting_task_execution_interval_button'] = 'Configure check interval';
$string['setting_task_execution_interval_desc'] = 'The check for inactive users is performed using a scheduled task that is executed via the Moodle cron. You can configure the interval at which it runs by clicking the following button.';
$string['step'] = 'Step';
$string['step_is_invalid'] = 'This step is invalid and must be fixed before the workflow can be enabled. Each step must have at least one filter and one action assigned. Please also make sure that all filters and actions are properly configured.';
$string['user_processes'] = 'User processes';
$string['in_workflow_since'] = 'In workflow since';
$string['in_step_since'] = 'In step since';
$string['step_processes_none'] = 'No user processes found for this step.';
$string['state'] = 'State';
$string['aborted'] = 'Aborted';
$string['view_user_processes'] = 'View user processes';
$string['steps'] = 'Steps';
$string['subplugintype_userdeleteaction'] = 'User lifecycle action';
$string['subplugintype_userdeleteaction_plural'] = 'User lifecycle actions';
$string['subplugintype_userdeletefilter'] = 'User lifecycle filter';
$string['subplugintype_userdeletefilter_plural'] = 'User lifecycle filters';
$string['task_cleanup'] = 'Cleanup';
$string['task_executeworkflows'] = 'Execute workflows';
$string['title'] = 'Title';
$string['unnamed'] = 'Unnamed';
$string['users_to_delete'] = 'Users to delete';
$string['users_to_warn'] = 'Users to warn';
$string['warned'] = 'Warned';
$string['workflow'] = 'Workflow';
$string['workflows'] = 'Workflows';
$string['workflow_stat_processes_in_steps'] = '{$a->active} active ({$a->finished} finished) process(es) in {$a->steps} step(s)';
