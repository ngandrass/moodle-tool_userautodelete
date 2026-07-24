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
 * External function to search for cohorts by name or ID number.
 *
 * @package     userdeleteaction_cohort
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_cohort\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\restricted_context_exception;
use userdeleteaction_cohort\userdeleteaction;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * External function that searches cohorts by name or ID number for AJAX autocomplete.
 *
 * Access is restricted to site administrators via the moodle/site:config capability,
 * matching the permission level required to manage user lifecycle workflows.
 */
class get_cohorts extends external_api {
    /**
     * Defines the parameters accepted by this external function.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query'    => new external_value(PARAM_TEXT, 'Search string matched against cohort name and ID number'),
            'limitnum' => new external_value(PARAM_INT, 'Maximum number of results to return', VALUE_DEFAULT, 50),
        ]);
    }

    /**
     * Defines the return value structure of this external function.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id'    => new external_value(PARAM_INT, 'Cohort ID'),
                'label' => new external_value(PARAM_TEXT, 'Formatted cohort label'),
            ])
        );
    }

    /**
     * Searches cohorts whose name or ID number contains the given query string.
     *
     * @param string $queryraw Search string
     * @param int $limitnumraw Maximum number of results
     * @return array{cohorts: array<array{id: int, label: string}>}
     * @throws restricted_context_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \required_capability_exception
     */
    public static function execute(string $queryraw, int $limitnumraw = 50): array {
        [
            'query' => $query,
            'limitnum' => $limitnum
        ] = self::validate_parameters(self::execute_parameters(), [
            'query' => $queryraw,
            'limitnum' => $limitnumraw,
        ]);

        self::validate_context(\context_system::instance());
        require_capability('moodle/site:config', \context_system::instance());

        $cohorts = userdeleteaction::get_cohorts($query, 0, $limitnum);
        $result = [];
        foreach ($cohorts as $id => $label) {
            $result[$id] = [
                'id' => $id,
                'label' => $label,
            ];
        }

        return $result;
    }
}
