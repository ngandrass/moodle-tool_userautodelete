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
 * User delete filter based to delay future actions by a certain amount of time
 * after a user entered a step
 *
 * @package     userdeletefilter_delay
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_delay;

use core\lang_string;
use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\local\type\userfilter_clause;
use tool_userautodelete\step;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * User delete filter based to delay future actions by a certain amount of time
 * after a user entered a step
 */
class userdeletefilter extends \tool_userautodelete\userdeletefilter {
    /**
     * Returns the name of this filter sub-plugin, e.g., 'lastaccess' for 'userdeletefilter_lastaccess'
     *
     * @return string The name of this filter sub-plugin
     */
    public static function get_plugin_name(): string {
        return 'delay';
    }

    /**
     * Returns a font-awesome icon CSS class string that is shown in the UI for
     * this filter sub-plugin type.
     *
     * @return string A font-awesome icon CSS class string combination
     */
    public static function get_icon_class(): string {
        return 'fa-solid fa-hourglass';
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
        return format_time($this->get_instance_setting('delaysec'));
    }

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
     * @throws \moodle_exception
     */
    public function user_records_filter_clause(): userfilter_clause {
        $delaysec = intval($this->get_instance_setting('delaysec'));

        if ($delaysec <= 0) {
            throw new \moodle_exception('negative_delaysec', 'userdeletefilter_delay');
        }

        return new userfilter_clause(
            'u.id IN (' .
                'SELECT userid FROM {' . db_table::USER_PROCESS->value . '} ' .
                'WHERE stepid = :stepid AND timemodified <= :procmodtime' .
            ')',
            [
                'stepid' => $this->stepid,
                'procmodtime' => time() - $delaysec,
            ]
        );
    }

    /**
     * Returns an array of descriptors for every setting this filter sub-plugin
     * defines and exposes.
     *
     * @return instance_setting_descriptor[] An array of setting descriptors
     */
    #[\Override]
    public static function instance_setting_descriptors(): array {
        return [
            new instance_setting_descriptor(
                key: 'delaysec',
                title: new lang_string('setting_delaysec', 'userdeletefilter_delay'),
                type: PARAM_INT,
                required: true,
                default: DAYSECS * 30,
                readonly: false
            ),
        ];
    }
}
