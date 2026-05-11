<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file defines the logger class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

use core\exception\coding_exception;
use tool_userautodelete\local\type\db_table;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Handles logging
 *
 * This class provides an mtrace() wrapper with formatting for various logging
 * levels. It furthermore helps with creating entries in the action log store.
 */
class logger {
    /** @var bool If true, all logging operations are suppressed */
    protected static bool $suppresslogs = false;

    /**
     * Enables logging globally
     *
     * @return void
     */
    public static function enable(): void {
        self::$suppresslogs = false;
    }

    /**
     * Disables logging globally
     *
     * @return void
     */
    public static function disable(): void {
        self::$suppresslogs = true;
    }

    /**
     * Logs a debug message
     *
     * @param string $message The message to log
     * @return void
     */
    public static function debug(string $message): void {
        if (!self::$suppresslogs) {
            mtrace("[DEBUG] $message");
        }
    }

    /**
     * Logs an info message
     *
     * @param string $message The message to log
     * @return void
     */
    public static function info(string $message): void {
        if (!self::$suppresslogs) {
            mtrace("[INFO] $message");
        }
    }

    /**
     * Logs a warning message
     *
     * @param string $message The message to log
     * @return void
     */
    public static function warning(string $message): void {
        if (!self::$suppresslogs) {
            mtrace("[WARN] $message");
        }
    }

    /**
     * Logs an error message
     *
     * @param string $message The message to log
     * @return void
     */
    public static function error(string $message): void {
        if (!self::$suppresslogs) {
            mtrace("[ERROR] $message");
        }
    }

    /**
     * Creates a new entry in the action log table.
     *
     * This method works independent of the current logging suppression state.
     *
     * @param string $name Name of the action that happened
     * @param int $affectedusers Number of users affacted by the action
     * @param int|null $workflowid Optional ID of a corresponding workflow
     * @param int|null $stepid Optional ID of a corresponding workflow step
     * @param int|null $timestamp Optional custom timestamp of the log event. Defaults to current unix time if null
     * @param mixed|null $details Optional serializable object to store additional details
     * @return void
     * @throws \dml_exception
     * @throws coding_exception
     */
    public static function action(
        string $name,
        int $affectedusers,
        ?int $workflowid = null,
        ?int $stepid = null,
        ?int $timestamp = null,
        mixed $details = null
    ) {
        global $DB;

        // Validate arguments.
        if (empty($name)) {
            throw new coding_exception('Action name cannot be empty');
        }
        if ($affectedusers < 0) {
            throw new coding_exception('Affected users count cannot be negative');
        }
        if ($workflowid !== null && $workflowid <= 0) {
            throw new coding_exception('Workflow ID must be a positive integer');
        }
        if ($stepid !== null && $stepid <= 0) {
            throw new coding_exception('Step ID must be a positive integer');
        }
        if ($timestamp !== null && $timestamp <= 0) {
            throw new coding_exception('Timestamp must be a positive integer');
        }
        if ($details !== null) {
            $details = json_encode($details);
            if ($details === false) {
                throw new coding_exception('Failed to encode action details as JSON');
            }
        }

        // Insert log entry into DB.
        $DB->insert_record(
            db_table::ACTIONLOG->value,
            (object) [
                'workflowid' => $workflowid,
                'stepid' => $stepid,
                'timestamp' => $timestamp ?? time(),
                'affectedusers' => $affectedusers,
                'action' => $name,
                'details' => $details,
            ]
        );
    }
}
