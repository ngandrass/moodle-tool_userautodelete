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
 * Cohort membership operation modes for the cohort action sub-plugin
 *
 * @package     userdeleteaction_cohort
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_cohort\local\type;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Cohort membership operation modes for the cohort action sub-plugin.
 *
 * Backed value is persisted as the mode setting in the database.
 */
enum operation: string {
    /** @var string Add the user to the selected cohorts */
    case ADD = 'add';

    /** @var string Remove the user from the selected cohorts */
    case REMOVE = 'remove';
}
