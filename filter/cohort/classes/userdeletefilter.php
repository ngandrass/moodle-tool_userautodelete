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
     * Returns an URL to additional documentation for this sub-plugin, if
     * available. When this URL is set, an additional button to open the linked
     * documentation will be shown in the sub-plugin instance settings form.
     *
     * @return \moodle_url|null URL to the sub-plugin specific documentation, or
     * null if no additional documentation is available
     */
    public static function get_help_url(): ?\moodle_url {
        return new \moodle_url("https://moodleuserlifecycle.gandrass.de/filters/cohort/");
    }

    /**
     * Returns a descriptive string of this filter instance's settings to be shown in the UI.
     *
     * @return string A descriptive string of this filter instance's settings to be shown in the UI
     * @throws \dml_exception
     */
    public function get_instance_details(): string {
        $cohortids = $this->get_instance_setting('cohortids');
        $inverted = $this->get_instance_setting('inverted');

        if (!$cohortids) {
            return '';
        }

        $cohorts = self::get_cohorts_by_ids($cohortids);

        // Handle deleted cohorts.
        $cohortnames = array_map(
            fn(int $cohortid): string => $cohorts[$cohortid] ?? '#' . $cohortid,
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
     * @throws \dml_exception
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
                choices: null,
                serialize: true,
                mformtype: 'autocomplete-multi',
                ajax: 'userdeletefilter_cohort/filter_cohort_selector',
                choicesresolver: static fn(array $ids): array => self::get_cohorts_by_ids($ids),
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
     * Returns the labels for a specific set of cohorts, indexed by cohort ID.
     *
     * Only loads the requested IDs, making this suitable for resolving labels
     * of already-selected values without scanning the full cohort table.
     *
     * @param int[] $ids Cohort IDs to look up
     * @return array<int, string> Cohort labels indexed by cohort ID
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_cohorts_by_ids(array $ids): array {
        global $DB;

        // Fail early in the obvious case.
        if (empty($ids)) {
            return [];
        }

        // Get cohorts for IDs from DB.
        [$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'cohortbyid');
        $cohorts = $DB->get_records_sql(
            "SELECT id, name, idnumber FROM {cohort} WHERE id {$insql} ORDER BY name ASC, id ASC",
            $inparams
        );

        // Prepare response.
        $choices = [];
        foreach ($cohorts as $cohort) {
            $choices[(int)$cohort->id] = self::format_cohort_label(
                id: (int)$cohort->id,
                name: $cohort->name,
                idnumber: $cohort->idnumber
            );
        }

        return $choices;
    }

    /**
     * Generates a list of all available cohorts, indexed by their identifier.
     *
     * @return array<int, string> List of available cohorts
     * @throws \dml_exception
     */
    public static function get_cohorts(?string $query = null, int $limitfrom = 0, int $limitnum = 0): array {
        global $DB;

        // Prepare cohort query.
        $sql = "SELECT id, name, idnumber FROM {cohort}";
        $params = [];
        if ($query) {
            $searchpattern = '%' . $DB->sql_like_escape($query) . '%';
            $params["qname"] = $searchpattern;
            $params["qidnum"] = $searchpattern;

            $sql .= " WHERE " . $DB->sql_like('name', ':qname', false);
            $sql .= " OR " . $DB->sql_like('idnumber', ':qidnum', false);
        }
        $sql .= " ORDER BY name ASC, id ASC";

        // Get cohorts from DB and prepare response.
        $cohorts = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);

        $choices = [];
        foreach ($cohorts as $cohort) {
            $choices[(int) $cohort->id] = self::format_cohort_label(
                id: (int) $cohort->id,
                name: $cohort->name,
                idnumber: $cohort->idnumber
            );
        }

        return $choices;
    }

    /**
     * Generates a human-readable label for a cohort.
     *
     * @param int $id Internal ID of the cohort
     * @param string $name Name of the cohort
     * @param string|null $idnumber ID number of the cohort if available
     * @return string Formatted cohort label
     */
    protected static function format_cohort_label(int $id, string $name, ?string $idnumber): string {
        $label = format_string($name, true);

        if (!empty($idnumber)) {
            $label .= ' [' . $idnumber . ']';
        }

        $label .= ' (#' . $id . ')';

        return $label;
    }
}
