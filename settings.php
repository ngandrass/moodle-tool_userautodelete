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
 * Plugin administration pages are defined here.
 *
 * @package     tool_userautodelete
 * @category    admin
 * @copyright   2025 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


use tool_userautodelete\local\admin\admin_setting_configcheckbox_alwaystrue;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


if ($hassiteconfig) {
    $settings = new admin_settingpage('tool_userautodelete_settings', new lang_string('pluginname', 'tool_userautodelete'));
    $ADMIN->add('tools', $settings);

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // Header description text.
        $settings->add(new admin_setting_heading('tool_userautodelete/header_common',
            "",
            get_string('setting_plugin_desc', 'tool_userautodelete')
        ));

        // Enable plugin globally.
        $settings->add(new admin_setting_configcheckbox('tool_userautodelete/enable',
            get_string('setting_enable', 'tool_userautodelete'),
            get_string('setting_enable_desc', 'tool_userautodelete'),
            '0'
        ));

        // Exclude site admins.
        $settings->add(new admin_setting_configcheckbox_alwaystrue('tool_userautodelete/ignore_siteadmins',
            get_string('setting_ignore_siteadmins', 'tool_userautodelete'),
            get_string('setting_ignore_siteadmins_desc', 'tool_userautodelete'),
            '1'
        ));

        // Excluded roles.
        $settings->add(new admin_setting_pickroles('tool_userautodelete/ignore_roles',
            get_string('setting_ignore_roles', 'tool_userautodelete'),
            get_string('setting_ignore_roles_desc', 'tool_userautodelete'),
            []
        ));

        // Dry-run.
        $dryrunurl = new moodle_url('/admin/tool/userautodelete/info.php');
        $settings->add(new admin_setting_description('tool_userautodelete/dryrun',
            get_string('setting_dryrun', 'tool_userautodelete'),
            get_string('setting_dryrun_desc', 'tool_userautodelete').
            '<br><a href="'.$dryrunurl.'" class="btn btn-primary my-2" role="button">'.
                get_string('setting_dryrun_button', 'tool_userautodelete').
            '</a>'
        ));

        // Task execution interval.
        $crontaskconfigurl = new moodle_url('/admin/tool/task/scheduledtasks.php', [
            'action' => 'edit',
            'task' => 'tool_userautodelete\task\check_and_delete_users',
        ]);
        $settings->add(new admin_setting_description('tool_userautodelete/task_execution_interval',
            get_string('setting_task_execution_interval', 'tool_userautodelete'),
            get_string('setting_task_execution_interval_desc', 'tool_userautodelete').
            '<br><a href="'.$crontaskconfigurl.'" class="btn btn-primary my-2" role="button" target="_blank">'.
                get_string('setting_task_execution_interval_button', 'tool_userautodelete').
            '</a>'
        ));

        // Task logs.
        $tasklogsurl = new moodle_url('/admin/tool/userautodelete/log.php');
        $settings->add(new admin_setting_description('tool_userautodelete/task_logs',
            get_string('setting_task_logs', 'tool_userautodelete'),
            get_string('setting_task_logs_desc', 'tool_userautodelete').
            '<br><a href="'.$tasklogsurl.'" class="btn btn-primary my-2" role="button">'.
                get_string('setting_task_logs_button', 'tool_userautodelete').
            '</a>'
        ));

        // Header: Automatic user deletion.
        $settings->add(new admin_setting_heading('tool_userautodelete/header_user_deletion',
            get_string('setting_header_user_deletion', 'tool_userautodelete'),
            get_string('setting_header_user_deletion_desc', 'tool_userautodelete')
        ));

        // Deletion threshold.
        $settings->add(new admin_setting_configtext('tool_userautodelete/delete_threshold_days',
            get_string('setting_delete_threshold_days', 'tool_userautodelete'),
            get_string('setting_delete_threshold_days_desc', 'tool_userautodelete'),
            '1095', // 3 years.
            PARAM_INT
        ));

        // Anonymize user data.
        $settings->add(new admin_setting_configcheckbox('tool_userautodelete/anonymize_user_data',
            get_string('setting_anonymize_user_data', 'tool_userautodelete'),
            get_string('setting_anonymize_user_data_desc', 'tool_userautodelete'),
            '1'
        ));

        // Enable deletion notifications.
        $settings->add(new admin_setting_configcheckbox('tool_userautodelete/delete_email_enable',
            get_string('setting_delete_email_enable', 'tool_userautodelete'),
            get_string('setting_delete_email_enable_desc', 'tool_userautodelete'),
            '0'
        ));

        // Deletion email subject.
        $settings->add(new admin_setting_configtext('tool_userautodelete/delete_email_subject',
            get_string('setting_delete_email_subject', 'tool_userautodelete'),
            get_string('setting_delete_email_subject_desc', 'tool_userautodelete'),
            get_string('setting_delete_email_subject_default', 'tool_userautodelete')
        ));

        // Deletion email body.
        $settings->add(new admin_setting_confightmleditor('tool_userautodelete/delete_email_body',
            get_string('setting_delete_email_body', 'tool_userautodelete'),
            get_string('setting_delete_email_body_desc', 'tool_userautodelete'),
            get_string('setting_delete_email_body_default', 'tool_userautodelete'),
            PARAM_RAW,
            80,
            20
        ));

        // Header: Deletion warnings.
        $settings->add(new admin_setting_heading('tool_userautodelete/header_deletion_warning',
            get_string('setting_header_deletion_warning', 'tool_userautodelete'),
            get_string('setting_header_deletion_warning_desc', 'tool_userautodelete')
        ));

        // Enable warnings.
        $settings->add(new admin_setting_configcheckbox('tool_userautodelete/warning_email_enable',
            get_string('setting_warning_email_enable', 'tool_userautodelete'),
            get_string('setting_warning_email_enable_desc', 'tool_userautodelete'),
            '1'
        ));

        // Warning threshold.
        $settings->add(new admin_setting_configtext('tool_userautodelete/warning_threshold_days',
            get_string('setting_warning_threshold_days', 'tool_userautodelete'),
            get_string('setting_warning_threshold_days_desc', 'tool_userautodelete'),
            '30', // 30 days.
            PARAM_INT
        ));

        // Warning email subject.
        $settings->add(new admin_setting_configtext('tool_userautodelete/warning_email_subject',
            get_string('setting_warning_email_subject', 'tool_userautodelete'),
            get_string('setting_warning_email_subject_desc', 'tool_userautodelete'),
            get_string('setting_warning_email_subject_default', 'tool_userautodelete')
        ));

        // Warning email body.
        $settings->add(new admin_setting_confightmleditor('tool_userautodelete/warning_email_body',
            get_string('setting_warning_email_body', 'tool_userautodelete'),
            get_string('setting_warning_email_body_desc', 'tool_userautodelete'),
            get_string('setting_warning_email_body_default', 'tool_userautodelete'),
            PARAM_RAW,
            80,
            20
        ));
    }
}
