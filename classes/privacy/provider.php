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
 * Privacy provider class for this plugin.
 *
 * @package   tool_userautodelete
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\privacy;


// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Privacy provider for tool_userautodelete
 *
 * @codeCoverageIgnore This is handled by Moodle core tests
 */
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * Returns the reason for this provider.
     *
     * @return string The reason for this provider.
     */
    public static function get_reason(): string {
        // FIXME: This is just a placeholder for unit test happiness. Implement properly if this plugin will store userdata!
        return 'privacy:metadata';
    }

}
