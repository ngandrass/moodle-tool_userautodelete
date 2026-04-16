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
 * Unit tests for the userdeleteaction_suspend sub-plugin
 *
 * @package   userdeleteaction_suspend
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_suspend;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeleteaction_testcase.php');


/**
 * Unit tests for the userdeleteaction_suspend sub-plugin
 */
final class userdeleteaction_test extends \tool_userautodelete\userdeleteaction_testcase {
    /**
     * @inheritDoc
     */
    protected function get_plugin_name(): string {
        return 'suspend';
    }

    /**
     * @inheritDoc
     */
    protected function get_expected_icon_class(): string {
        return 'fa-regular fa-circle-pause';
    }

    /**
     * Tests that execute() sets the user's suspended flag to 1 and returns true.
     *
     * @covers \userdeleteaction_suspend\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute(): void {
        global $DB;

        $this->resetAfterTest();

        // Start with an active (non-suspended) user.
        $user = $this->getDataGenerator()->create_user(['suspended' => 0]);

        $step = $this->create_step();
        $action  = $this->create_action($step);
        $process = $this->create_process((int) $user->id, $step);

        $result = $action->execute($process);
        $this->assertTrue($result, 'suspend action execute() must return true on success');

        $updated = $DB->get_record('user', ['id' => $user->id], 'id,suspended', MUST_EXIST);
        $this->assertSame(
            1,
            (int) $updated->suspended,
            'User must be suspended (suspended = 1) after execute()'
        );
    }

    /**
     * Tests that execute() is idempotent: suspending an already-suspended user
     * leaves the user in the suspended state and still returns true.
     *
     * @covers \userdeleteaction_suspend\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_is_idempotent_for_already_suspended_user(): void {
        global $DB;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user(['suspended' => 1]);

        $step = $this->create_step();
        $action  = $this->create_action($step);
        $process = $this->create_process((int) $user->id, $step);

        $result = $action->execute($process);
        $this->assertTrue($result, 'suspend action execute() on already-suspended user must still return true');

        $updated = $DB->get_record('user', ['id' => $user->id], 'id,suspended', MUST_EXIST);
        $this->assertSame(
            1,
            (int) $updated->suspended,
            'User must remain suspended after a redundant suspend execute()'
        );
    }

    /**
     * Tests that a default instance (no required settings) is considered valid.
     *
     * @covers \userdeleteaction_suspend\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_default_instance_is_valid(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertTrue($action->is_valid(), 'suspend action without settings must be valid by default');
    }

    /**
     * Tests that get_instance_details() returns an empty string (no settings).
     *
     * @covers \userdeleteaction_suspend\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details_returns_empty_string(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertSame('', $action->get_instance_details(), 'suspend action get_instance_details() must return empty string');
    }
}

