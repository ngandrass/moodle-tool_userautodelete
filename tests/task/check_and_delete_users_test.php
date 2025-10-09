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
 * Tests for the check_and_delete_users task
 *
 * @package   tool_userautodelete
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\task;


/**
 * Tests for the logger class
 */
final class check_and_delete_users_test extends \advanced_testcase {
    /**
     * Tests that the task name getter does not throw any exceptions
     *
     * @covers \tool_userautodelete\task\check_and_delete_users::get_name
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_get_name(): void {
        $task = new check_and_delete_users();
        $this->assertNotEmpty($task->get_name());
    }

    /**
     * Tests that the task execute method does not throw any exceptions.
     *
     * Everything else is covered in the manager_test class.
     *
     * @covers \tool_userautodelete\task\check_and_delete_users::execute
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_execute(): void {
        $this->resetAfterTest();

        $task = new check_and_delete_users();
        $task->execute();
    }
}
