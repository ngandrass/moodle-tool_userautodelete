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
 * External function to retrieve user process metadata for a workflow step.
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use tool_userautodelete\local\type\process_state;
use tool_userautodelete\process;
use tool_userautodelete\step;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * External function that exposes user process metadata for a given workflow step.
 *
 * This function is intended for use via AJAX and requires the moodle/site:config
 * capability, restricting access to site administrators.
 */
class get_step_user_processes extends external_api {
    /**
     * Defines the parameters accepted by this external function.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'stepid' => new external_value(PARAM_INT, 'ID of the workflow step to retrieve processes for'),
            'activeonly' => new external_value(
                PARAM_BOOL,
                'If true, only active processes are returned. Defaults to true (only active processes returned).',
                VALUE_DEFAULT,
                true
            ),
        ]);
    }

    /**
     * Defines the structure of the value returned by this external function.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'processid'       => new external_value(PARAM_INT, 'ID of the user process'),
                'userid'          => new external_value(PARAM_INT, 'ID of the user'),
                'username'        => new external_value(PARAM_TEXT, 'Username'),
                'fullname'        => new external_value(PARAM_TEXT, 'Full name of the user'),
                'profileurl'      => new external_value(PARAM_URL, 'URL to the user\'s profile page'),
                'isactive'        => new external_value(PARAM_BOOL, 'Whether the process is currently active'),
                'isfinished'      => new external_value(PARAM_BOOL, 'Whether the process has finished successfully'),
                'isaborted'       => new external_value(PARAM_BOOL, 'Whether the process was aborted'),
                'lastaccess'      => new external_value(PARAM_INT, 'Unix timestamp of the user\'s last login'),
                'timecreated'     => new external_value(PARAM_INT, 'Unix timestamp when the user entered the workflow'),
                'timemodified'    => new external_value(PARAM_INT, 'Unix timestamp of the last process state change'),
                'timemodifiedrel' => new external_value(PARAM_TEXT, 'Relative time since the last process state change'),
            ])
        );
    }

    /**
     * Returns metadata for all user processes within a given workflow step.
     *
     * @param int $stepidraw ID of the workflow step
     * @param bool $activeonlyraw Whether to return only active processes
     * @return array List of user process metadata objects
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \required_capability_exception
     */
    public static function execute(int $stepidraw, bool $activeonlyraw = true): array {
        // Validate and clean parameters.
        [
            'stepid' => $stepid,
            'activeonly' => $activeonly,
        ] = self::validate_parameters(self::execute_parameters(), [
            'stepid' => $stepidraw,
            'activeonly' => $activeonlyraw,
        ]);

        // Enforce admin-only access.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('moodle/site:config', $context);

        // Load step and retrieve process metadata.
        $step = step::get_by_id($stepid);
        $records = process::get_user_process_metadata_for_step($step, $activeonly);

        // Map DB records to the return structure.
        $now = time();
        $result = [];
        foreach ($records as $record) {
            $state = process_state::from((int) $record->state);
            $result[] = [
                'processid'       => (int) $record->id,
                'userid'          => (int) $record->userid,
                'username'        => (string) $record->username,
                'fullname'        => "{$record->firstname} {$record->lastname}",
                'profileurl'      => (new \moodle_url('/user/profile.php', ['id' => $record->userid]))->out(false),
                'isactive'        => $state === process_state::ACTIVE,
                'isfinished'      => $state === process_state::FINISHED,
                'isaborted'       => $state === process_state::ABORTED,
                'lastaccess'      => (int) $record->lastaccess,
                'timecreated'     => (int) $record->timecreated,
                'timemodified'    => (int) $record->timemodified,
                'timemodifiedrel' => format_time($now - (int) $record->timemodified),
            ];
        }

        return $result;
    }
}
