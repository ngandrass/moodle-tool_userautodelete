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
 * Test-only action fixture that fails for specified user IDs.
 *
 * This class is intentionally NOT placed inside action/ so it is never
 * discovered by Moodle's plugin manager and therefore never installed in
 * production. It is loaded in unit tests via require_once.
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_failforuser;

use core\lang_string;
use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\process;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Test fixture: returns false from execute() for user IDs listed in 'failuserids'.
 */
class userdeleteaction extends \tool_userautodelete\userdeleteaction {
    /**
     * Returns the short name of this sub-plugin.
     *
     * @return string Plugin name.
     */
    #[\Override]
    public static function get_plugin_name(): string {
        return 'failforuser';
    }

    /**
     * Returns false for any user ID listed in the 'failuserids' setting.
     *
     * @param process $process The user deletion process to execute this action for.
     * @return bool False if the process user is in the configured fail list, true otherwise.
     */
    #[\Override]
    public function execute(process $process): bool {
        $raw = $this->get_instance_setting('failuserids') ?? '';
        if ($raw === '') {
            return true;
        }

        $failids = array_flip(array_map('intval', explode(',', $raw)));
        return !isset($failids[$process->userid]);
    }

    /**
     * Returns the instance setting descriptors for this action.
     *
     * @return instance_setting_descriptor[] Array of setting descriptors.
     * @throws \coding_exception
     */
    #[\Override]
    public static function instance_setting_descriptors(): array {
        return [
            new instance_setting_descriptor(
                key: 'failuserids',
                title: new lang_string('filter', 'tool_userautodelete'),
                type: PARAM_TEXT,
                required: false,
                default: '',
            ),
        ];
    }
}
