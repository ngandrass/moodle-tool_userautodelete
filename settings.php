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
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


if ($hassiteconfig) {
    $category = new admin_category('tool_userautodelete', new lang_string('pluginname', 'tool_userautodelete'));
    $settings = new admin_settingpage('tool_userautodelete_settings', new lang_string('generalsettings', 'admin'));

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // Header description text.
        $settings->add(new admin_setting_heading(
            'tool_userautodelete/header_common',
            "",
            get_string('setting_plugin_desc', 'tool_userautodelete')
        ));

        // Enable plugin globally.
        $settings->add(new admin_setting_configcheckbox(
            'tool_userautodelete/enable',
            get_string('setting_enable', 'tool_userautodelete'),
            get_string('setting_enable_desc', 'tool_userautodelete'),
            '0'
        ));

        // Task execution interval.
        $crontaskconfigurl = new moodle_url('/admin/tool/task/scheduledtasks.php', [
            'action' => 'edit',
            'task' => 'tool_userautodelete\task\executeworkflows',
        ]);
        $settings->add(new admin_setting_description(
            'tool_userautodelete/task_execution_interval',
            get_string('setting_task_execution_interval', 'tool_userautodelete'),
            get_string('setting_task_execution_interval_desc', 'tool_userautodelete') .
            '<br><a href="' . $crontaskconfigurl . '" class="btn btn-primary my-2" role="button" target="_blank">' .
                get_string('setting_task_execution_interval_button', 'tool_userautodelete') .
            '</a>'
        ));
    }

    $ADMIN->add('tools', $category);
    $ADMIN->add($category->name, $settings);
    $ADMIN->add($category->name, new admin_externalpage(
        'tool_userautodelete_workflows',
        get_string('manage_workflows', 'tool_userautodelete'),
        new moodle_url('/admin/tool/userautodelete/workflows.php')
    ));
}
