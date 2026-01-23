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
 * SQL where clause with parameters to be used for filtering user datasets
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\type;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * SQL where clause with parameters to be used for filtering user datasets
 */
class userfilter_clause {
    /**
     * Creates a new userfilter_clause instance
     *
     * @param string $sql SQL statement to be used as part of a WHERE clause when querying the Moodle user table
     * @param array $params Array of parameter-value combinations used within SQL statement
     */
    public function __construct(
        /** @var string SQL statement to be used as part of a WHERE clause when querying the Moodle user table */
        public readonly string $sql,
        /** @var array Array of parameter-value combinations used within SQL statement */
        public readonly array $params,
    ) {
    }
}
