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
 * Unit tests for the userdeleteaction_unsuspend sub-plugin
 *
 * @package   userdeleteaction_unsuspend
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_unsuspend;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeleteaction_testcase.php');


/**
 * Unit tests for the userdeleteaction_unsuspend sub-plugin
 */
final class userdeleteaction_test extends \tool_userautodelete\userdeleteaction_testcase {
    /**
     * Returns the short plugin name of the action sub-plugin under test.
     */
    protected function get_plugin_name(): string {
        return 'unsuspend';
    }

    /**
     * Returns the expected font-awesome icon CSS class string for the action
     * sub-plugin under test, e.g. 'fa-solid fa-gear'.
     */
    protected function get_expected_icon_class(): string {
        return 'fa-regular fa-circle-play';
    }

    /**
     * Tests that execute() clears the user's suspended flag to 0 and returns true.
     *
     * @covers \userdeleteaction_unsuspend\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute(): void {
        global $DB;

        $this->resetAfterTest();

        // Start with a suspended user.
        $user = $this->getDataGenerator()->create_user(['suspended' => 1]);

        $step = $this->create_step();
        $action  = $this->create_action($step);
        $process = $this->create_process((int) $user->id, $step);

        $result = $action->execute($process);
        $this->assertTrue($result, 'unsuspend action execute() must return true on success');

        $updated = $DB->get_record('user', ['id' => $user->id], 'id,suspended', MUST_EXIST);
        $this->assertSame(
            0,
            (int) $updated->suspended,
            'User must be unsuspended (suspended = 0) after execute()'
        );
    }

    /**
     * Tests that execute() is idempotent: unsuspending an already-active user
     * leaves the user in the active state and still returns true.
     *
     * @covers \userdeleteaction_unsuspend\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_is_idempotent_for_already_active_user(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user(['suspended' => 0]);

        $step = $this->create_step();
        $action  = $this->create_action($step);
        $process = $this->create_process((int) $user->id, $step);

        $result = $action->execute($process);
        $this->assertTrue($result, 'unsuspend action execute() on already-active user must still return true');

        $updated = $DB->get_record('user', ['id' => $user->id], 'id,suspended', MUST_EXIST);
        $this->assertSame(
            0,
            (int) $updated->suspended,
            'User must remain unsuspended after a redundant unsuspend execute()'
        );
    }

    /**
     * Tests that a default instance (no required settings) is considered valid.
     *
     * @covers \userdeleteaction_unsuspend\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_default_instance_is_valid(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertTrue($action->is_valid(), 'unsuspend action without settings must be valid by default');
    }

    /**
     * Tests that get_instance_details() returns an empty string (no settings).
     *
     * @covers \userdeleteaction_unsuspend\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details_returns_empty_string(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertSame('', $action->get_instance_details(), 'unsuspend action get_instance_details() must return empty string');
    }
}
