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
 * User action that deletes users.
 *
 * @package     userdeleteaction_delete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_delete;

use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\process;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * User action that deletes users.
 */
class userdeleteaction extends \tool_userautodelete\userdeleteaction {
    /**
     * Returns the name of this filter sub-plugin, e.g., 'suspend' for 'userdeleteaction_suspend'
     *
     * @return string The name of this filter sub-plugin
     */
    public function get_plugin_name(): string {
        return 'delete';
    }

    /**
     * Executes this action for a given user deletion process
     *
     * @param process $process The user deletion process to execute this action for
     * @return bool True if the action was executed successfully, false otherwise
     * @throws \coding_exception
     */
    public function execute(process $process): bool {
        return delete_user(
            (object) ['id' => $process->userid]  // The delete_user() function re-fetches the full user record anyway ...
        );
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
