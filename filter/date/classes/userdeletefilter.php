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
 * User delete filter that checks for a configurable date condition, e.g., only
 * during January and July.
 *
 * @package     userdeletefilter_date
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_date;

use core\exception\coding_exception;
use core\lang_string;
use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\local\type\userfilter_clause;
use tool_userautodelete\local\util\data_util;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * User delete filter that checks for a configurable date condition, e.g., only
 * during January and July.
 */
class userdeletefilter extends \tool_userautodelete\userdeletefilter {
    /**
     * Returns the name of this filter sub-plugin, e.g., 'lastaccess' for 'userdeletefilter_lastaccess'
     *
     * @return string The name of this filter sub-plugin
     */
    public static function get_plugin_name(): string {
        return 'date';
    }

    /**
     * Returns a font-awesome icon CSS class string that is shown in the UI for
     * this filter sub-plugin type.
     *
     * @return string A font-awesome icon CSS class string combination
     */
    public static function get_icon_class(): string {
        return 'fa-regular fa-calendar';
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
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_instance_details(): string {
        $settings = $this->get_all_instance_settings();

        // Case: Multiple set.
        $numcriteriaset = array_reduce($settings, fn ($carry, $item) => $carry + (empty($item) ? 0 : 1), 0);
        if ($numcriteriaset > 1) {
            return get_string('multiple', 'userdeletefilter_date');
        }

        // Case: Weekday only set.
        if (!empty($settings['weekday'])) {
            $weekdays = array_map(
                fn ($weekday) => substr(get_string("weekday_{$weekday}", 'userdeletefilter_date'), 0, 3),
                $settings['weekday']
            );
            return get_string('day', 'userdeletefilter_date') . ': ' . implode(', ', $weekdays);
        }

        // Case: Day only set.
        if (!empty($settings['day'])) {
            sort($settings['day']);
            return get_string('day', 'userdeletefilter_date') . ': ' . implode(', ', $settings['day']);
        }

        // Case: Month only set.
        if (!empty($settings['month'])) {
            $months = array_map(
                fn ($month) => substr(get_string("month_{$month}", 'userdeletefilter_date'), 0, 3),
                $settings['month']
            );
            return get_string('month', 'userdeletefilter_date') . ': ' . implode(', ', $months);
        }

        // Case: Year only set.
        if (!empty($settings['year'])) {
            sort($settings['year']);
            return get_string('year', 'userdeletefilter_date') . ': ' . implode(', ', $settings['year']);
        }

        return '';
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
        if ($this->date_constraints_met()) {
            return new userfilter_clause('TRUE', []);
        } else {
            return new userfilter_clause('FALSE', []);
        }
    }

    /**
     * Validates the settings of this sub-plugin instance and returns whether they
     * are considered valid and ready for execution or not.
     *
     * This function can be extended by individual subplugins to perform custom
     * validity checks.
     *
     * @return string|null Null if this instance is valid, otherwise a localized
     * human-readable string describing why this instance is not valid
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function validate(): string|null {
        // Execute parent validation checks and bubble up if errors are present.
        if ($error = parent::validate()) {
            return $error;
        }

        // Check that at least one criterion is set.
        $settings = $this->get_all_instance_settings();
        if (
            empty($settings['weekday']) &&
            empty($settings['day']) &&
            empty($settings['month']) &&
            empty($settings['year'])
        ) {
            return get_string('error_no_criterion_set', 'userdeletefilter_date');
        }

        // Validate individual criteria.
        foreach ($settings['weekday'] as $weekday) {
            if ($weekday < 1 || $weekday > 7) {
                return get_string('error_invalid_weekday', 'userdeletefilter_date');
            }
        }

        foreach ($settings['day'] as $day) {
            if ($day < 0 || $day > 31) {
                return get_string('error_invalid_day', 'userdeletefilter_date');
            }
        }

        foreach ($settings['month'] as $month) {
            if ($month < 0 || $month > 12) {
                return get_string('error_invalid_month', 'userdeletefilter_date');
            }
        }

        foreach ($settings['year'] as $year) {
            if ($year < 0 || $year > 99999) {
                return get_string('error_invalid_year', 'userdeletefilter_date');
            }
        }

        return null;
    }


    /**
     * Returns an array of descriptors for every setting this filter sub-plugin
     * defines and exposes.
     *
     * @return instance_setting_descriptor[] An array of setting descriptors
     * @throws coding_exception
     */
    #[\Override]
    public static function instance_setting_descriptors(): array {
        return [
            new instance_setting_descriptor(
                key: 'weekday',
                title: new lang_string('setting_weekday', 'userdeletefilter_date'),
                type: PARAM_TEXT,
                required: false,
                default: [],
                choices: data_util::array_fill_closure(
                    1,
                    7,
                    fn($key) => new lang_string("weekday_{$key}", 'userdeletefilter_date')
                ),
                serialize: true,
                readonly: false,
                mformtype: 'autocomplete-multi'
            ),
            new instance_setting_descriptor(
                key: 'day',
                title: new lang_string('setting_day', 'userdeletefilter_date'),
                type: PARAM_INT,
                required: false,
                default: [],
                choices: data_util::array_fill_closure(1, 31, fn($key) => "{$key}."),
                serialize: true,
                readonly: false,
                mformtype: 'autocomplete-multi'
            ),
            new instance_setting_descriptor(
                key: 'month',
                title: new lang_string('setting_month', 'userdeletefilter_date'),
                type: PARAM_INT,
                required: false,
                default: [],
                choices: data_util::array_fill_closure(
                    1,
                    12,
                    fn($key) => new lang_string("month_{$key}", 'userdeletefilter_date')
                ),
                serialize: true,
                readonly: false,
                mformtype: 'autocomplete-multi'
            ),
            new instance_setting_descriptor(
                key: 'year',
                title: new lang_string('setting_year', 'userdeletefilter_date'),
                type: PARAM_INT,
                required: false,
                default: [],
                choices: data_util::array_fill_closure(((int) date('Y')) - 1, 50, fn($key) => "{$key}"),
                serialize: true,
                readonly: false,
                mformtype: 'autocomplete-multi'
            ),
        ];
    }

    /**
     * Determines if this filter instance date-based criteria are met, based on
     * the current datetime.
     *
     * @return bool True, if the configured date criteria are met. False if not.
     * @throws \moodle_exception If instance settings are invalid.
     */
    public function date_constraints_met(): bool {
        if ($error = $this->validate()) {
            throw new \moodle_exception('a', 'tool_userautodelete', a: $error);
        }

        $settings = $this->get_all_instance_settings();

        // Prepare current date variables for checks. Work with the same timestamp for all.
        $now = time();
        $weekday = (int) date('N', $now);
        $day = (int) date('j', $now);
        $month = (int) date('n', $now);
        $year = (int) date('Y', $now);

        // Check criteria.
        if (!empty($settings['weekday']) && !in_array($weekday, $settings['weekday'])) {
            return false;
        }

        if (!empty($settings['day']) && !in_array($day, $settings['day'])) {
            return false;
        }

        if (!empty($settings['month']) && !in_array($month, $settings['month'])) {
            return false;
        }

        if (!empty($settings['year']) && !in_array($year, $settings['year'])) {
            return false;
        }

        // No criterion failed. We are good!
        return true;
    }
}
