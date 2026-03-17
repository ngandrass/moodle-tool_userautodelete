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
 * Interface for userdeletefilter sub-plugins
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

use tool_userautodelete\local\trait\subplugin_instance_settings;
use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\subplugin_type;
use tool_userautodelete\local\type\userfilter_clause;
use tool_userautodelete\local\util\plugin_util;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Interface for userdeletefilter sub-plugins
 */
abstract class userdeletefilter {
    use subplugin_instance_settings;

    /** @var step|null The step this filter instance belongs to (lazy-loaded) */
    protected ?step $step;

    /**
     * Creates a new instance of this filter sub-plugin
     *
     * @param int $id The ID of this filter sub-plugin instance
     * @param int $stepid The ID of the step this filter instance is part of
     */
    private function __construct(
        /** @var int The ID of this filter sub-plugin instance */
        public readonly int $id,
        /** @var int The ID of the step this filter instance is part of */
        public readonly int $stepid,
    ) {
        $this->step = null;
    }

    /**
     * Retrieves an user filter instance from the database and creates a local
     * instance of the respective filter sub-plugin class.
     *
     * @param int $filterid The ID of the filter instance to retrieve
     * @return self An instance of the respective userdeletefilter sub-plugin
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_instance_by_id(int $filterid): self {
        global $DB;

        // Fetch filter instance record and sub-plugin class to instantiate.
        $record = $DB->get_record(db_table::WORKFLOW_FILTER->value, ['id' => $filterid], '*', MUST_EXIST);
        $filtercls = plugin_util::get_subplugin_class('userdeletefilter', $record->pluginname);

        return new $filtercls($filterid, $record->stepid);
    }

    /**
     * Creates a new filter instance associated with the given step.
     *
     * @param step $step The step to associate the new filter instance with
     * @param string|null $pluginname The name of the filter sub-plugin to instantiate,
     * defaults to the actual plugin name of the class this method is called on
     * @param array $settings An associative array of setting key-value pairs
     * set for the new instance. Falls back to default values defined in th
     * respective sub-plugin implementation if not given explicitly.
     * @return self
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
        plugin_util::get_subplugin_class('userdeletefilter', $pluginname);

        try {
            $transaction = $DB->start_delegated_transaction();

            // Create filter instance.
            $filterid = $DB->insert_record(db_table::WORKFLOW_FILTER->value, [
                'stepid' => $step->id,
                'pluginname' => $pluginname,
            ]);

            $filter = static::get_instance_by_id($filterid);
            $filter->load_default_instance_settings($settings);

            // Mark step as modified.
            $step->touch();

            $transaction->allow_commit();
        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return $filter;
    }

    /**
     * Deletes this filter instance
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function delete(): void {
        global $DB;

        $DB->delete_records(db_table::WORKFLOW_FILTER->value, ['id' => $this->get_instance_id()]);
        $this->get_step()->touch();
    }

    /**
     * Returns the ID of this sub-plugin instance
     *
     * @return int The ID of this sub-plugin instance
     */
    public function get_instance_id(): int {
        return $this->id;
    }

    /**
     * Returns the step this filter instance belongs to
     *
     * @return step The step this filter instance belongs to
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_step(): step {
        if ($this->step === null) {
            $this->step = step::get_by_id($this->stepid);
        }

        return $this->step;
    }

    /**
     * Returns the type of this sub-plugin
     *
     * @return subplugin_type The type of this sub-plugin
     */
    public static function get_plugin_type(): subplugin_type {
        return subplugin_type::FILTER;
    }

    /**
     * Returns a font-awesome icon CSS class string that is shown in the UI for
     * this filter sub-plugin type.
     *
     * @return string A font-awesome icon CSS class string combination
     */
    public static function get_icon_class(): string {
        return 'fa-solid fa-filter';
    }

    /**
     * Returns a descriptive title of this filter instance to be shown in the UI
     *
     * This should be a very short human-readable string that allows to identify
     * what the filter does at a glance, e.g., 'Last access' for a filter that
     * filters users based on their last access time.
     *
     * @return string A descriptive title of this filter instance to be shown in the UI
     * @throws \coding_exception
     */
    public function get_instance_title(): string {
        return get_string('pluginname', 'userdeletefilter_' . static::get_plugin_name());
    }

    /**
     * Returns a descriptive string of this filter instance's settings to be shown in the UI
     *
     * This should be a human-readable string that describes the actual settings
     * of this filter instance, e.g., '<= 3 months' for a filter instance that
     * filters users based on their last access time with a threshold of 3 months.
     *
     * If no settings are defined, this function can simply return an empty string.
     *
     * @return string A descriptive string of this filter instance's settings to be shown in the UI
     */
    public function get_instance_details(): string {
        return '';
    }

    /**
     * Validates the settings of this filter instance and returns whether they
     * are considered valid and ready for use or not.
     *
     * @return bool True if this instance is valid and ready, false otherwise
     * @throws \dml_exception
     */
    public function is_valid(): bool {
        return $this->is_all_required_instance_settings_set();
    }

    /**
     * Returns the name of this filter sub-plugin, e.g., 'lastaccess' for 'userdeletefilter_lastaccess'
     *
     * @return string The name of this filter sub-plugin
     */
    abstract public static function get_plugin_name(): string;

    /**
     * Returns a userfilter_clause object defining the SQL where clause and parameters
     * to be used when querying user datasets that match this filter's criteria.
     *
     * User table fields must be accessed using the 'u' table alias, e.g., 'u.lastaccess'
     * for the 'lastaccess' field inside the Moodle 'user' table.
     *
     * Multiple filter clauses will be concatenated using a SQL 'AND' operator.
     *
     * @return userfilter_clause The SQL where clause and parameters for filtering user datasets
     */
    abstract public function user_records_filter_clause(): userfilter_clause;
}
