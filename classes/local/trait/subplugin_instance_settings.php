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
 * Bundles function required for storing custom settings of subplugin instances
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\local\trait;

use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\local\type\subplugin_type;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Allows to get all values of a backed enum as an array
 */
trait subplugin_instance_settings {
    /**
     * Returns an array of descriptors for every setting this filter sub-plugin
     * defines and exposes.
     *
     * @return instance_setting_descriptor[] An array of setting descriptors
     */
    abstract public function instance_setting_descriptors(): array;

    /**
     * Returns the ID of this sub-plugin instance
     *
     * @return int The ID of this sub-plugin instance
     */
    abstract public function get_instance_id(): int;

    /**
     * Returns the type of this sub-plugin
     *
     * @return subplugin_type The type of this sub-plugin
     */
    abstract public function get_plugin_type(): subplugin_type;

    /**
     * Retrieves all setting key-value pairs for this sub-plugin instance.
     *
     * @return array An associative array of setting key-value pairs
     * @throws \dml_exception
     */
    public function get_all_instance_settings(): array {
        global $DB;

        $records = $DB->get_records(
            db_table::INSTANCE_SETTINGS->value,
            [
                'plugintype' => $this->get_plugin_type()->value,
                'instanceid' => $this->get_instance_id(),
            ]
        );

        $settings = [];
        foreach ($records as $record) {
            $settings[$record->datakey] = $record->datavalue;
        }

        return $settings;
    }

    /**
     * Retrieves a specific setting value for this sub-plugin instance.
     *
     * @param string $key The key of the setting to retrieve
     * @return mixed The value of the setting, or null if not set
     */
    public function get_instance_setting(string $key): mixed {
        global $DB;

        try {
            return $DB->get_field(
                db_table::INSTANCE_SETTINGS->value,
                'datavalue',
                [
                    'plugintype' => $this->get_plugin_type()->value,
                    'instanceid' => $this->get_instance_id(),
                    'datakey' => $key,
                ],
                MUST_EXIST
            );
        } catch (\dml_exception $e) {
            return null;
        }
    }

    /**
     * Sets a specific setting value for this sub-plugin instance.
     *
     * @param string $key The key of the setting to set
     * @param mixed $value The value to set for the setting. Null deletes the setting entry.
     * @return void
     * @throws \dml_exception
     */
    public function set_instance_setting(string $key, mixed $value): void {
        global $DB;

        try {
            $transaction = $DB->start_delegated_transaction();

            // Determine if we need to update an existing record or create a new one.
            $current = $DB->get_record(db_table::INSTANCE_SETTINGS->value, [
                'plugintype' => $this->get_plugin_type()->value,
                'instanceid' => $this->get_instance_id(),
                'datakey' => $key,
            ], '*', IGNORE_MISSING);

            if (!$current) {
                $DB->insert_record(db_table::INSTANCE_SETTINGS->value, (object) [
                    'plugintype' => $this->get_plugin_type()->value,
                    'instanceid' => $this->get_instance_id(),
                    'datakey' => $key,
                    'datavalue' => $value,
                ]);
            } else {
                // Determine if we need to delete the value instead of updating.
                if ($value === null) {
                    $DB->delete_records(db_table::INSTANCE_SETTINGS->value, ['id' => $current->id]);
                } else {
                    $DB->update_record(db_table::INSTANCE_SETTINGS->value, [
                        'id' => $current->id,
                        'datavalue' => $value,
                    ]);
                }
            }

            $transaction->allow_commit();
        } catch (\Exception $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * Deletes all settings for this sub-plugin instance.
     *
     * @return void
     * @throws \dml_exception
     */
    public function delete_all_instance_settings(): void {
        global $DB;

        $DB->delete_records(
            db_table::INSTANCE_SETTINGS->value,
            [
                'plugintype' => $this->get_plugin_type()->value,
                'instanceid' => $this->get_instance_id(),
            ]
        );
    }
}
