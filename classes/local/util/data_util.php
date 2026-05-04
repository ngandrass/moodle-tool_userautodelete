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
 * Utility class for working with data structures
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\local\util;


// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Utility class for working with data structures
 */
class data_util {
    /**
     * Fills an array using a PHP closure. Current index is passed as first element
     * to closure.
     *
     * @param int $startindex The first index of the returned array.
     * @param int $count Number of elements to insert.
     * @param \Closure $closure Closure to be called with current index. Return value
     * is used as array value for the current index.
     * @return array
     */
    public static function array_fill_closure(int $startindex, int $count, \Closure $closure): array {
        $result = [];
        for ($i = $startindex; $i < ($startindex + $count); $i++) {
            $result[$i] = $closure($i);
        }

        return $result;
    }
}
