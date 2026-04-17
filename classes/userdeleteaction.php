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

use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\subplugin_type;
use tool_userautodelete\local\util\plugin_util;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Interface for userdeleteaction sub-plugins
 */
abstract class userdeleteaction extends step_subplugin {
    /**
     * Retrieves an user action instance from the database and creates a local
     * instance of the respective action sub-plugin class.
     *
     * @param int $instanceid The ID of the action instance to retrieve
     * @return self An instance of the respective userdeleteaction sub-plugin
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_instance_by_id(int $instanceid): self {
        global $DB;

        // Fetch action instance record and determine class to instantiate.
        $record = $DB->get_record(db_table::WORKFLOW_ACTION->value, ['id' => $instanceid], '*', MUST_EXIST);
        $actioncls = plugin_util::get_subplugin_class('userdeleteaction', $record->pluginname);

        // Instantiate.
        return new $actioncls($instanceid, $record->stepid);
    }

    /**
     * Creates a new action instance record in the database and returns
     * the respective action sub-plugin instance.
     *
     * @param step $step The step this action instance belongs to
     * @param string|null $pluginname The name of the action sub-plugin to instantiate,
     * defaults to the actual plugin name of the class this method is called on
     * @param array $settings An associative array of setting key-value pairs
     * set for the new instance. Falls back to default values defined in th
     * respective sub-plugin implementation if not given explicitly.
     * @return self An instance of the respective userdeleteaction sub-plugin
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function create_instance(
        step $step,
        ?string $pluginname = null,
        array $settings = []
    ): self {
        global $DB;

        // Validate pluginname by checking if the respective class exists (call throws moodle_exception).
        $pluginname = $pluginname ?: static::get_plugin_name();
        plugin_util::get_subplugin_class('userdeleteaction', $pluginname);

        try {
            $transaction = $DB->start_delegated_transaction();

            // Create action instance.
            $actionid = $DB->insert_record(db_table::WORKFLOW_ACTION->value, [
                'stepid' => $step->id,
                'pluginname' => $pluginname,
            ]);

            $action = static::get_instance_by_id($actionid);
            $action->load_default_instance_settings($settings);

            // Mark step as modified.
            $step->touch();

            $transaction->allow_commit();
        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return $action;
    }

    /**
     * Deletes this action instance
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function delete(): void {
        global $DB;

        try {
            $transaction = $DB->start_delegated_transaction();

            $this->delete_all_instance_settings();
            $DB->delete_records(db_table::WORKFLOW_ACTION->value, ['id' => $this->get_instance_id()]);
            $this->get_step()->touch();

            $transaction->allow_commit();
        } catch (\Exception $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * Returns the type of this sub-plugin
     *
     * @return subplugin_type The type of this sub-plugin
     */
    public static function get_plugin_type(): subplugin_type {
        return subplugin_type::ACTION;
    }

    /**
     * Returns a font-awesome icon CSS class string that is shown in the UI for
     * this action sub-plugin type.
     *
     * @return string A font-awesome icon CSS class string combination
     */
    #[\Override]
    public static function get_icon_class(): string {
        return 'fa-solid fa-gear';
    }

    /**
     * Executes this action for a given user deletion process
     *
     * @param process $process The user deletion process to execute this action for
     *
     * @return bool True if the action was executed successfully, false otherwise
     */
    abstract public function execute(process $process): bool;
}
