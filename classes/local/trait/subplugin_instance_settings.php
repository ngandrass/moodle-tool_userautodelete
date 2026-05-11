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
    abstract public static function instance_setting_descriptors(): array;

    /**
     * Returns the type of this sub-plugin
     *
     * @return subplugin_type The type of this sub-plugin
     */
    abstract public static function get_plugin_type(): subplugin_type;

    /**
     * Returns the ID of this sub-plugin instance
     *
     * @return int The ID of this sub-plugin instance
     */
    abstract public function get_instance_id(): int;

    /**
     * Determines which instance settings need to be (de-)serialized
     *
     * @return array List of setting keys that require (de-)serialization
     */
    public function get_serialized_settings(): array {
        return array_reduce(static::instance_setting_descriptors(), function ($carry, $item) {
            if ($item->serialize) {
                $carry[] = $item->key;
            }

            return $carry;
        }, []);
    }

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

        // Build response object.
        $settings = [];
        $serializedsettings = $this->get_serialized_settings();
        foreach ($records as $record) {
            if (in_array($record->datakey, $serializedsettings)) {
                $settings[$record->datakey] = json_decode($record->datavalue);
            } else {
                $settings[$record->datakey] = $record->datavalue;
            }
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

        $serialized = in_array($key, $this->get_serialized_settings());

        try {
            $value = $DB->get_field(
                db_table::INSTANCE_SETTINGS->value,
                'datavalue',
                [
                    'plugintype' => $this->get_plugin_type()->value,
                    'instanceid' => $this->get_instance_id(),
                    'datakey' => $key,
                ],
                MUST_EXIST
            );

            if ($serialized) {
                $value = json_decode($value);
            }

            return $value;
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

        if (in_array($key, $this->get_serialized_settings())) {
            $value = json_encode($value);
        }

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
     * Sets all settings to their default values. Single settings can instead be
     * set to a custom value by passing an key-value pair within the $overrides
     * array.
     *
     * @param array $overrides An associative array of setting key-value pairs
     * to set instead of the default value
     * @return void
     * @throws \dml_transaction_exception
     */
    public function load_default_instance_settings(array $overrides = []): void {
        global $DB;

        // Prepare settings to be inserted.
        $settings = [];
        foreach (static::instance_setting_descriptors() as $descriptor) {
            $key = $descriptor->key;
            $value = $overrides[$key] ?? $descriptor->default;

            // Skip null values, as they indicate that the setting should not be set.
            if ($value === null) {
                continue;
            }

            if ($descriptor->serialize) {
                $value = json_encode($value);
            }

            $settings[] = (object) [
                'plugintype' => static::get_plugin_type()->value,
                'instanceid' => $this->get_instance_id(),
                'datakey' => $key,
                'datavalue' => $value,
            ];
        }

        // Clear all existing settings and write new ones in a transaction.
        try {
            $transaction = $DB->start_delegated_transaction();

            $this->delete_all_instance_settings();
            $DB->insert_records(db_table::INSTANCE_SETTINGS->value, $settings);

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

    /**
     * Checks if all required settings as defined in the respective sub-plugin's
     * instance_setting_descriptors are set to an non-empty value for this
     * instance.
     *
     * @return bool
     * @throws \dml_exception
     */
    public function is_all_required_instance_settings_set(): bool {
        $presentsettings = $this->get_all_instance_settings();
        foreach (static::instance_setting_descriptors() as $descriptor) {
            if (!$descriptor->required) {
                continue;
            }

            if (!array_key_exists($descriptor->key, $presentsettings)) {
                return false;
            }

            if ($descriptor->type !== PARAM_BOOL && empty($presentsettings[$descriptor->key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Takes a given array of potential instance settings key-value pairs, validates it,
     * and returns an array of per-key error messages.
     *
     * This method is called prior to any instance setting save operation that is triggered
     * via the UI. If the validation fails, changes are not saved and the returned error
     * messages are displayed instead.
     *
     * The default implementation returns an empty array (no errors). Sub-plugins may
     * override this method to perform custom validation logic, e.g., checking for
     * invalid variable references.
     *
     * @param array $settings Associative array of setting key-value pairs to validate
     * @return string[] Associative array of setting key => localized error message for each invalid setting
     */
    public function validate_instance_settings_data(array $settings): array {
        return [];
    }
}
