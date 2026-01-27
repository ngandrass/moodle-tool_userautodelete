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
 * Plugin upgrade steps are defined here.
 *
 * @package     tool_userautodelete
 * @category    upgrade
 * @copyright   2025 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

/**
 * Execute tool_userautodelete upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 * @throws ddl_exception
 * @throws downgrade_exception
 * @throws moodle_exception
 * @throws upgrade_exception
 */
function xmldb_tool_userautodelete_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // For further information please read {@link https://docs.moodle.org/dev/Upgrade_API}.
    //
    // You will also have to create the db/install.xml file by using the XMLDB Editor.
    // Documentation for the XMLDB Editor can be found at {@link https://docs.moodle.org/dev/XMLDB_editor}.

    if ($oldversion < 2025052600) {
        // Define table tool_userautodelete_log to be created.
        $table = new xmldb_table('tool_userautodelete_log');

        // Adding fields to table tool_userautodelete_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('runtime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('warned', XMLDB_TYPE_INTEGER, '8', null, XMLDB_NOTNULL, null, null);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '8', null, XMLDB_NOTNULL, null, null);
        $table->add_field('recovered', XMLDB_TYPE_INTEGER, '8', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tool_userautodelete_log.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for tool_userautodelete_log.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Userautodelete savepoint reached.
        upgrade_plugin_savepoint(true, 2025052600, 'tool', 'userautodelete');
    }

    if ($oldversion < 2026012400) {
        // Define table tool_userautodelete_workflow to be created.
        $table = new xmldb_table('tool_userautodelete_workflow');

        // Adding fields to table tool_userautodelete_workflow.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sort', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('modifiedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tool_userautodelete_workflow.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('createdby', XMLDB_KEY_FOREIGN, ['createdby'], 'user', ['id']);
        $table->add_key('modifiedby', XMLDB_KEY_FOREIGN, ['modifiedby'], 'user', ['id']);

        // Conditionally launch create table for tool_userautodelete_workflow.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table tool_userautodelete_step to be created.
        $table = new xmldb_table('tool_userautodelete_step');

        // Adding fields to table tool_userautodelete_step.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('workflowid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sort', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table tool_userautodelete_step.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('workflowid', XMLDB_KEY_FOREIGN, ['workflowid'], 'tool_userautodelete_workflow', ['id']);

        // Conditionally launch create table for tool_userautodelete_step.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table tool_userautodelete_filter to be created.
        $table = new xmldb_table('tool_userautodelete_filter');

        // Adding fields to table tool_userautodelete_filter.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('stepid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pluginname', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tool_userautodelete_filter.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('stepid', XMLDB_KEY_FOREIGN, ['stepid'], 'tool_userautodelete_step', ['id']);

        // Conditionally launch create table for tool_userautodelete_filter.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table tool_userautodelete_action to be created.
        $table = new xmldb_table('tool_userautodelete_action');

        // Adding fields to table tool_userautodelete_action.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('stepid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pluginname', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tool_userautodelete_action.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('stepid', XMLDB_KEY_FOREIGN, ['stepid'], 'tool_userautodelete_step', ['id']);

        // Conditionally launch create table for tool_userautodelete_action.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table tool_userautodelete_instance_settings to be created.
        $table = new xmldb_table('tool_userautodelete_instance_settings');

        // Adding fields to table tool_userautodelete_instance_settings.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('plugintype', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
        $table->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('datakey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('datavalue', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tool_userautodelete_instance_settings.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for tool_userautodelete_instance_settings.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Define table tool_userautodelete_process to be created.
        $table = new xmldb_table('tool_userautodelete_process');

        // Adding fields to table tool_userautodelete_process.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('stepid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('finished', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table tool_userautodelete_process.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('stepid', XMLDB_KEY_FOREIGN, ['stepid'], 'tool_userautodelete_step', ['id']);

        // Conditionally launch create table for tool_userautodelete_process.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // TODO (MDL-0): Take existing old v1 settings and migrate them to a new corresponding workflow.

        // Userautodelete savepoint reached.
        upgrade_plugin_savepoint(true, 2026012400, 'tool', 'userautodelete');
    }

    return true;
}
