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
 * Interface for userdeleteaction sub-plugins
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

use tool_userautodelete\local\trait\subplugin_instance_settings;
use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\util\plugin_util;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Interface for userdeleteaction sub-plugins
 */
abstract class userdeleteaction {
    use subplugin_instance_settings;

    /**
     * Creates a new instance of this action sub-plugin
     *
     * @param int $id The ID of this action sub-plugin instance
     */
    private function __construct(
        /** @var int The ID of this action sub-plugin instance */
        public readonly int $id,
    ) {
    }

    /**
     * Retrieves an user action instance from the database and creates a local
     * instance of the respective action sub-plugin class.
     *
     * @param int $actionid The ID of the action instance to retrieve
     * @return self An instance of the respective userdeleteaction sub-plugin
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_instance_by_id(int $actionid): self {
        global $DB;

        // Fetch action instance record and determine class to instantiate.
        $record = $DB->get_record(db_table::WORKFLOW_ACTION->value, ['id' => $actionid], '*', MUST_EXIST);
        $actioncls = plugin_util::get_subplugin_class('userdeleteaction', $record->pluginname);

        // Instantiate.
        return new $actioncls($actionid);
    }

    /**
     * Creates a new action instance record in the database and returns
     * the respective action sub-plugin instance.
     *
     * @param step $step The step this action instance belongs to
     * @param string $pluginname The name of the action sub-plugin to instantiate
     * @return self An instance of the respective userdeleteaction sub-plugin
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function create_instance(step $step, string $pluginname): self {
        global $DB;

        // Create action instance record.
        $actionid = $DB->insert_record(db_table::WORKFLOW_ACTION->value, [
            'stepid' => $step->id,
            'pluginname' => $pluginname,
        ]);

        return self::get_instance_by_id($actionid);
    }

    /**
     * Returns the name of this action sub-plugin, e.g., 'suspend' for 'userdeleteaction_suspend'
     *
     * @return string The name of this action sub-plugin
     */
    abstract public function get_plugin_name(): string;

    /**
     * Executes this action for a given user deletion process
     *
     * @param int $processid The ID of the user deletion process to execute this action for
     *
     * @return void
     */
    abstract public function execute(int $processid): void;
}
