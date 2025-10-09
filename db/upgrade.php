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

    return true;
}
