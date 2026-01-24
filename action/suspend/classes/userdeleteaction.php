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
 * User action that (un-)suspends users.
 *
 * @package     userdeleteaction_suspend
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_suspend;

use tool_userautodelete\filter\userdeleteaction_base;
use tool_userautodelete\trait\subplugin_instance_settings;
use tool_userautodelete\type\instance_setting_descriptor;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * User action that (un-)suspends users.
 */
class userdeleteaction extends userdeleteaction_base {
    use subplugin_instance_settings;

    /**
     * Returns the name of this filter sub-plugin, e.g., 'suspend' for 'userdeleteaction_suspend'
     *
     * @return string The name of this filter sub-plugin
     */
    public function get_plugin_name(): string {
        return 'suspend';
    }

    /**
     * Executes this action for a given user deletion process
     *
     * @param int $processid The ID of the user deletion process to execute this action for
     *
     * @return void
     */
    public function execute(int $processid): void {
        // TODO (MDL-0): Implement execute() method.
    }

    /**
     * Returns an array of descriptors for every setting this filter sub-plugin
     * defines and exposes.
     *
     * @return instance_setting_descriptor[] An array of setting descriptors
     */
    public function instance_setting_descriptors(): array {
        return [];
    }
}
