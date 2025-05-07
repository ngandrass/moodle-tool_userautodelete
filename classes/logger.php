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
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Handles logging
 *
 * This is currently just an mtrace() wrapper but allows for future extension
 * of the logging system.
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

}
