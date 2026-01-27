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

    /**
     * Creates a new instance of this filter sub-plugin
     *
     * @param int $id The ID of this filter sub-plugin instance
     */
    private function __construct(
        /** @var int The ID of this filter sub-plugin instance */
        public readonly int $id,
    ) {
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

        return new $filtercls($filterid);
    }

    /**
     * Creates a new filter instance associated with the given step.
     *
     * @param step $step The step to associate the new filter instance with
     * @param string $pluginname The name of the filter sub-plugin to use
     * @return self
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function create_instance(step $step, string $pluginname): self {
        global $DB;

        // Create filter instance record.
        $filterid = $DB->insert_record(db_table::WORKFLOW_FILTER->value, [
            'stepid' => $step->id,
            'pluginname' => $pluginname,
        ]);

        $step->touch();

        return self::get_instance_by_id($filterid);
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
     * Returns the type of this sub-plugin
     *
     * @return subplugin_type The type of this sub-plugin
     */
    public function get_plugin_type(): subplugin_type {
        return subplugin_type::FILTER;
    }

    /**
     * Returns the name of this filter sub-plugin, e.g., 'lastaccess' for 'userdeletefilter_lastaccess'
     *
     * @return string The name of this filter sub-plugin
     */
    abstract public function get_plugin_name(): string;

    /**
     * Returns a userfilter_clause object defining the SQL where clause and parameters
     * to be used when querying user datasets that match this filter's criteria.
     *
     * Multiple filter clauses will be concatenated using a SQL 'AND' operator.
     *
     * @return userfilter_clause The SQL where clause and parameters for filtering user datasets
     */
    abstract public function user_records_filter_clause(): userfilter_clause;
}
