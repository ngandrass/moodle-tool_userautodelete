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
 * Scheduled task that checks users for inactivity, performs configured steps,
 * and finally deletes eligible users
 *
 * @package     tool_userautodelete
 * @copyright   2025 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\task;


// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Scheduled task that checks users for inactivity, performs configured steps,
 * and finally deletes eligible users
 */
class check_and_delete_users extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for the task (shown to admins)
     *
     * @return string Localized name of this task
     * @throws \coding_exception
     */
    public function get_name() {
        return get_string('task_check_and_delete_users', 'tool_userautodelete');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     * @throws \coding_exception
     */
    public function execute() {
        $manager = new \tool_userautodelete\manager();
        $manager->execute();
    }

}
