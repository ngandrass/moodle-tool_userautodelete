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
 * @package     userdeleteaction_mail
 * @category    upgrade
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

/**
 * Execute userdeleteaction_mail upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 * @throws dml_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_userdeleteaction_mail_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2026072400) {
        // Backfill recipient='user' for all existing mail action instances that
        // predate the recipient setting. The setting is now required.
        $missingids = $DB->get_fieldset_sql("
            SELECT a.id
            FROM {tool_userautodelete_action} a
            LEFT JOIN {tool_userautodelete_instance_settings} s
                ON s.plugintype = 'action'
                AND s.instanceid = a.id
                AND s.datakey = 'recipient'
            WHERE a.pluginname = 'mail'
                AND s.id IS NULL
        ");

        if (!empty($missingids)) {
            $records = [];
            foreach ($missingids as $id) {
                $records[] = (object) [
                    'plugintype' => 'action',
                    'instanceid' => $id,
                    'datakey'    => 'recipient',
                    'datavalue'  => 'user',
                ];
            }
            $DB->insert_records('tool_userautodelete_instance_settings', $records);
        }

        upgrade_plugin_savepoint(true, 2026072400, 'userdeleteaction', 'mail');
    }

    return true;
}
