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
 * Unit tests for the userdeleteaction_delete sub-plugin
 *
 * @package   userdeleteaction_delete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_delete;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeleteaction_testcase.php');


/**
 * Unit tests for the userdeleteaction_delete sub-plugin
 */
final class userdeleteaction_test extends \tool_userautodelete\userdeleteaction_testcase {
    /**
     * @inheritDoc
     */
    protected function get_plugin_name(): string {
        return 'delete';
    }

    /**
     * @inheritDoc
     */
    protected function get_expected_icon_class(): string {
        return 'fa-solid fa-trash';
    }

    /**
     * Tests that execute() calls delete_user() for the process user, resulting
     * in the user being marked as deleted in the database, and returns true.
     *
     * @covers \userdeleteaction_delete\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute(): void {
        global $DB;

        // Prepare user, step, and process.
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        $step = $this->create_step();
        $action  = $this->create_action($step);
        $process = $this->create_process((int) $user->id, $step);

        // Execute and assert success.
        $result = $action->execute($process);
        $this->assertTrue($result, 'delete action execute() must return true on success');

        // Moodle's delete_user() marks the user record with deleted = 1.
        $deleteduser = $DB->get_record('user', ['id' => $user->id], 'id,deleted', MUST_EXIST);
        $this->assertSame(
            1,
            (int) $deleteduser->deleted,
            'User must be marked as deleted (deleted = 1) in the database after execute()'
        );
    }

    /**
     * Tests that a default instance (no required settings) is considered valid.
     *
     * @covers \userdeleteaction_delete\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_default_instance_is_valid(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertTrue($action->is_valid(), 'delete action without settings must be valid by default');
    }

    /**
     * Tests that get_instance_details() returns an empty string (no settings).
     *
     * @covers \userdeleteaction_delete\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details_returns_empty_string(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertSame('', $action->get_instance_details(), 'delete action get_instance_details() must return empty string');
    }
}

