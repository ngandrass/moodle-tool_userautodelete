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

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Privacy provider for tool_userautodelete
 *
 * @codeCoverageIgnore This is handled by Moodle core tests
 */
class provider implements // phpcs:ignore
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Returns meta data about this plugin.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'tool_userautodelete_mail',
            [
                'userid' => 'privacy:metadata:tool_userautodelete_mail:userid',
                'timesent' => 'privacy:metadata:tool_userautodelete_mail:timesent',
            ],
            'privacy:metadata:tool_userautodelete_mail'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return  contextlist   $contextlist  The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        // Everything happens on global system level.
        $contextlist = new contextlist();
        $contextlist->add_system_context();

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        // Process each given context. Only system context is relevant here.
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_SYSTEM) {
                // Get all user data from the database.
                $userdatarow = $DB->get_records('tool_userautodelete_mail', ['userid' => $contextlist->get_user()->id]);

                // Add all rows for the user to the export. Should only be one but loop anyway just to be sure ...
                foreach ($userdatarow as $row) {
                    writer::with_context($context)->export_data(
                        [
                            get_string('pluginname', 'tool_userautodelete'),
                            get_string('inactivity_warning', 'tool_userautodelete') . " #{$row->id}",
                        ],
                        (object) [
                            'userid' => $row->userid,
                            'timesent' => $row->timesent,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context $context The specific context to delete data for.
     * @throws \dml_exception
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            // Delete all data for all users in the system context.
            $DB->delete_records('tool_userautodelete_mail');
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     * @throws \dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        // Process each given context. Only system context is relevant here.
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_SYSTEM) {
                // Delete all data for the user in the system context.
                $DB->delete_records('tool_userautodelete_mail', ['userid' => $contextlist->get_user()->id]);
            }
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     * @throws \dml_exception
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;

        // Only process system context.
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        // Get user ids from the database and add to userlist.
        $userids = $DB->get_records('tool_userautodelete_mail', [], '', 'DISTINCT userid');
        $userlist->add_users(array_map(fn ($row) => $row->userid, $userids));
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        // Only process system context.
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        // Delete all data for the user in the system context.
        [$insql, $inparams] = $DB->get_in_or_equal($userlist->get_userids());
        $DB->delete_records_select('tool_userautodelete_mail', "userid {$insql}", $inparams);
    }
}
