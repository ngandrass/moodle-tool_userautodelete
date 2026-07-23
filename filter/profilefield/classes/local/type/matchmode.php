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

// phpcs:disable moodle.Commenting.InlineComment.DocBlock

/**
 * Match modes for the profile field user delete filter
 *
 * @package     userdeletefilter_profilefield
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_profilefield\local\type;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Match modes for the profile field user delete filter.
 *
 * Backed value is persisted as the matchmode setting in the database.
 */
enum matchmode: string {
    /** @var string Field value contains the given string */
    case CONTAINS = 'contains';

    /** @var string Field value does not contain the given string */
    case NOT_CONTAINS = 'not_contains';

    /** @var string Field value equals the given string */
    case EQUALS = 'equals';

    /** @var string Field value does not equal the given string */
    case NOT_EQUALS = 'not_equals';

    /** @var string Field value starts with the given string */
    case STARTS_WITH = 'starts_with';

    /** @var string Field value ends with the given string */
    case ENDS_WITH = 'ends_with';

    /** @var string Field value is empty or not set */
    case EMPTY = 'empty';

    /** @var string Field value is set and not empty */
    case NOT_EMPTY = 'not_empty';

    /**
     * Returns whether this match mode requires a comparison value.
     *
     * @return bool True if a comparison value is required, false otherwise
     */
    public function requires_value(): bool {
        return match ($this) {
            self::EMPTY, self::NOT_EMPTY => false,
            default => true,
        };
    }
}
