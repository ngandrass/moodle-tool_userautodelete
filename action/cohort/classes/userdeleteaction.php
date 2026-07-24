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
 * User action that manages cohort memberships.
 *
 * @package     userdeleteaction_cohort
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_cohort;

use core\lang_string;
use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\process;
use userdeleteaction_cohort\local\type\operation;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * User action that adds or removes users from cohorts.
 */
class userdeleteaction extends \tool_userautodelete\userdeleteaction {
    /**
     * Returns the name of this action sub-plugin.
     *
     * @return string The name of this action sub-plugin
     */
    public static function get_plugin_name(): string {
        return 'cohort';
    }

    /**
     * Returns a font-awesome icon CSS class string that is shown in the UI for
     * this action sub-plugin type.
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
        return new \moodle_url("https://moodleuserlifecycle.gandrass.de/actions/cohort/");
    }

    /**
     * Returns a descriptive string of this action instance's settings to be shown in the UI.
     *
     * @return string A descriptive string of this action instance's settings to be shown in the UI
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_instance_details(): string {
        $cohortids = $this->get_instance_setting('cohortids');
        $mode = $this->get_instance_setting('mode');

        if (!$cohortids) {
            return '';
        }

        $cohorts = self::get_cohorts_by_ids($cohortids);

        // Handle deleted cohorts.
        $cohortnames = array_map(
            fn(int $cohortid): string => $cohorts[$cohortid] ?? '#' . $cohortid,
            $cohortids
        );

        $verb = get_string('details_mode_' . operation::from($mode)->value, 'userdeleteaction_cohort');
        return $verb . ' ' . implode(', ', $cohortnames);
    }

    /**
     * Executes this action for a given user deletion process.
     *
     * @param process $process The user deletion process to execute this action for
     * @return bool True if the action was executed successfully, false otherwise
     * @throws \dml_exception
     */
    public function execute(process $process): bool {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $cohortids = $this->get_instance_setting('cohortids');
        $operation = operation::from($this->get_instance_setting('mode'));

        try {
            // Note: We do not need to wrap this in a transactions since this already happens in the core plugin.
            foreach ($cohortids as $cohortid) {
                switch ($operation) {
                    case operation::ADD:
                        cohort_add_member((int) $cohortid, $process->userid);
                        break;
                    case operation::REMOVE:
                        cohort_remove_member((int) $cohortid, $process->userid);
                        break;
                }
            }
        } catch (\Exception) {
            return false;
        }

        return true;
    }

    /**
     * Returns an array of descriptors for every setting this action sub-plugin
     * defines and exposes.
     *
     * @return instance_setting_descriptor[] An array of setting descriptors
     * @throws \coding_exception
     */
    #[\Override]
    public static function instance_setting_descriptors(): array {
        return [
            new instance_setting_descriptor(
                key: 'cohortids',
                title: new lang_string('setting_cohortids', 'userdeleteaction_cohort'),
                type: PARAM_TEXT,
                required: true,
                default: [],
                choices: null,
                serialize: true,
                mformtype: 'autocomplete-multi',
                ajax: 'userdeleteaction_cohort/action_cohort_selector',
                choicesresolver: static fn(array $ids): array => self::get_cohorts_by_ids($ids),
            ),
            new instance_setting_descriptor(
                key: 'mode',
                title: new lang_string('setting_mode', 'userdeleteaction_cohort'),
                type: PARAM_ALPHA,
                required: true,
                default: operation::ADD->value,
                choices: [
                    operation::ADD->value    => new lang_string('setting_mode_add', 'userdeleteaction_cohort'),
                    operation::REMOVE->value => new lang_string('setting_mode_remove', 'userdeleteaction_cohort'),
                ],
                mformtype: 'select',
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

        if (empty($ids)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'cohortbyid');
        $cohorts = $DB->get_records_sql(
            "SELECT id, name, idnumber FROM {cohort} WHERE id {$insql} ORDER BY name ASC, id ASC",
            $inparams
        );

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
     * @param string|null $query Search term to filter cohort names and idnumbers by
     * @param int $limitfrom First row of the resultset to return
     * @param int $limitnum Maximum number of rows to return
     * @return array<int, string> List of available cohorts
     * @throws \dml_exception
     */
    public static function get_cohorts(?string $query = null, int $limitfrom = 0, int $limitnum = 0): array {
        global $DB;

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
