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
 * User delete filter based on cohort memberships
 *
 * @package     userdeletefilter_cohort
 * @copyright   2026 ssystems GmbH <oss@ssystems.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_cohort;

use core\lang_string;
use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\local\type\userfilter_clause;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

/**
 * User delete filter based on cohort memberships
 */
class userdeletefilter extends \tool_userautodelete\userdeletefilter {
    /**
     * Returns the name of this filter sub-plugin.
     *
     * @return string The name of this filter sub-plugin
     */
    public static function get_plugin_name(): string {
        return 'cohort';
    }

    /**
     * Returns a font-awesome icon CSS class string that is shown in the UI for
     * this filter sub-plugin type.
     *
     * @return string A font-awesome icon CSS class string combination
     */
    public static function get_icon_class(): string {
        return 'fa-solid fa-users';
    }

    /**
     * Returns a descriptive string of this filter instance's settings to be shown in the UI.
     *
     * @return string A descriptive string of this filter instance's settings to be shown in the UI
     */
    public function get_instance_details(): string {
        $cohortids = $this->get_instance_setting('cohortids');
        $inverted = $this->get_instance_setting('inverted');

        if (!$cohortids) {
            return '';
        }

        $availablecohorts = self::get_available_cohorts();
        $cohortnames = array_map(
            fn(int $cohortid): string => $availablecohorts[$cohortid] ?? '#' . $cohortid,
            $cohortids
        );

        return ($inverted ? '! ' : '') . implode(', ', $cohortnames);
    }

    /**
     * Returns a userfilter_clause object defining the SQL where clause and parameters
     * to be used when querying user datasets that match this filter's criteria.
     *
     * @return userfilter_clause The SQL where clause and parameters for filtering user datasets
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function user_records_filter_clause(): userfilter_clause {
        global $DB;

        [$insql, $inparams] = $DB->get_in_or_equal(
            items: $this->get_instance_setting('cohortids'),
            type: SQL_PARAMS_NAMED,
            prefix: 'cohortparam',
        );

        $sqlverb = $this->get_instance_setting('inverted') ? 'NOT EXISTS' : 'EXISTS';

        return new userfilter_clause(
            sql: "{$sqlverb} (
                      SELECT 1 FROM {cohort_members} cm
                      WHERE cm.userid = u.id AND cm.cohortid {$insql}
                  )",
            params: $inparams
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
                key: 'cohortids',
                title: new lang_string('setting_cohortids', 'userdeletefilter_cohort'),
                type: PARAM_TEXT,
                required: true,
                default: [],
                choices: self::get_available_cohorts(),
                serialize: true,
                readonly: false,
                mformtype: 'autocomplete-multi'
            ),
            new instance_setting_descriptor(
                key: 'inverted',
                title: new lang_string('setting_inverted', 'userdeletefilter_cohort'),
                type: PARAM_BOOL,
                required: false,
                default: false,
                readonly: false,
                mformtype: 'selectyesno'
            ),
        ];
    }

    /**
     * Generates a list of all available cohorts, indexed by their identifier.
     *
     * @return array<int, string> List of available cohorts
     * @throws \dml_exception
     */
    public static function get_available_cohorts(): array {
        global $DB;

        $cohorts = $DB->get_records_sql(
            'SELECT id, name, idnumber, contextid
               FROM {cohort}
           ORDER BY name ASC, id ASC'
        );

        $choices = [];
        foreach ($cohorts as $cohort) {
            $label = format_string($cohort->name, true);
            if (!empty($cohort->idnumber)) {
                $label .= ' [' . $cohort->idnumber . ']';
            }
            $label .= ' (#' . $cohort->id . ')';
            $choices[(int) $cohort->id] = $label;
        }

        return $choices;
    }
}
