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
 * User action that deletes all badges awarded to users.
 *
 * @package     userdeleteaction_badges
 * @author      Marcus Green
 * @copyright   2026 Catalyst-EU
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_badges;

use core_privacy\local\request\approved_contextlist;
use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\process;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * User action that deletes all badges awarded to users.
 */
class userdeleteaction extends \tool_userautodelete\userdeleteaction {
    /**
     * Returns the name of this filter sub-plugin, e.g., 'suspend' for 'userdeleteaction_suspend'
     *
     * @return string The name of this filter sub-plugin
     */
    public static function get_plugin_name(): string {
        return 'badges';
    }

    /**
     * Returns a font-awesome icon CSS class string that is shown in the UI for
     * this action sub-plugin type.
     *
     * @return string A font-awesome icon CSS class string combination
     */
    public static function get_icon_class(): string {
        return 'fa-solid fa-award';
    }

    /**
     * Returns an URL to additional documentation for this sub-plugin, if
     * available. When this URL is set, an additional button to open the linked
     * documentation will be shown in the sub-plugin instance settings form.
     *
     * @return \moodle_url|null URL to the sub-plugin specific documentation, or
     * null if no additional documentation is available
     */
    public static function get_help_url(): ?\moodle_url {
        return new \moodle_url("https://moodleuserlifecycle.gandrass.de/actions/badges/");
    }

    /**
     * Executes this action for a given user deletion process
     *
     * Badge data is removed via the core_badges privacy provider, so that the
     * set of purged tables stays in sync with Moodle core. This removes issued
     * badges, manual awards, met criteria, and connected backpacks. Baked badge
     * images stored in the user context are removed as well, since the privacy
     * provider does not cover them and they are only deleted by delete_user().
     *
     * @param process $process The user deletion process to execute this action for
     * @return bool True if the action was executed successfully, false otherwise
     * @throws \dml_exception
     */
    public function execute(process $process): bool {
        $user = \core_user::get_user($process->userid);
        if (!$user) {
            return false;
        }

        // The user context is kept by delete_user(), so this also works for already deleted users.
        $usercontext = \context_user::instance($user->id, IGNORE_MISSING);
        if (!$usercontext) {
            return false;
        }

        \core_badges\privacy\provider::delete_data_for_user(
            new approved_contextlist($user, 'core_badges', [$usercontext->id])
        );
        get_file_storage()->delete_area_files($usercontext->id, 'badges', 'userbadge');

        return true;
    }

    /**
     * Returns an array of descriptors for every setting this filter sub-plugin
     * defines and exposes.
     *
     * @return instance_setting_descriptor[] An array of setting descriptors
     */
    public static function instance_setting_descriptors(): array {
        return [];
    }
}
