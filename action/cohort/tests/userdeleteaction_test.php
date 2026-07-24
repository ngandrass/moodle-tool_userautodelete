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
 * Unit tests for the userdeleteaction_cohort sub-plugin
 *
 * @package   userdeleteaction_cohort
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_cohort;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeleteaction_testcase.php');


/**
 * Unit tests for the userdeleteaction_cohort sub-plugin
 */
final class userdeleteaction_test extends \tool_userautodelete\userdeleteaction_testcase {
    /**
     * Returns the short plugin name of the action sub-plugin under test.
     */
    protected function get_plugin_name(): string {
        return 'cohort';
    }

    /**
     * Returns the expected font-awesome icon CSS class string for the action
     * sub-plugin under test.
     */
    protected function get_expected_icon_class(): string {
        return 'fa-solid fa-users';
    }

    /**
     * Returns true if the given user is a member of the given cohort.
     *
     * @param int $userid ID of the user to check
     * @param int $cohortid ID of the cohort to check
     * @return bool True if the user is a member of the cohort
     * @throws \dml_exception
     */
    private function is_cohort_member(int $userid, int $cohortid): bool {
        global $DB;
        return $DB->record_exists('cohort_members', ['userid' => $userid, 'cohortid' => $cohortid]);
    }

    /**
     * Tests that execute() adds a user to a cohort and returns true.
     *
     * @covers \userdeleteaction_cohort\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $cohort = $this->getDataGenerator()->create_cohort();

        $step = $this->create_step();
        $action = $this->create_action($step, ['cohortids' => [(int) $cohort->id], 'mode' => 'add']);
        $process = $this->create_process((int) $user->id, $step);

        $this->assertFalse(
            $this->is_cohort_member((int) $user->id, (int) $cohort->id),
            'User must not be a cohort member before execute()'
        );

        $result = $action->execute($process);
        $this->assertTrue($result, 'cohort action execute() must return true on success');

        $this->assertTrue(
            $this->is_cohort_member((int) $user->id, (int) $cohort->id),
            'User must be a cohort member after execute() in add mode'
        );
    }

    /**
     * Tests that execute() adds a user to multiple cohorts and returns true.
     *
     * @covers \userdeleteaction_cohort\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_adds_user_to_multiple_cohorts(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $cohortone = $this->getDataGenerator()->create_cohort();
        $cohorttwo = $this->getDataGenerator()->create_cohort();

        $step = $this->create_step();
        $action = $this->create_action($step, [
            'cohortids' => [(int) $cohortone->id, (int) $cohorttwo->id],
            'mode' => 'add',
        ]);
        $process = $this->create_process((int) $user->id, $step);

        $result = $action->execute($process);
        $this->assertTrue($result, 'cohort action execute() must return true on success');

        $this->assertTrue(
            $this->is_cohort_member((int) $user->id, (int) $cohortone->id),
            'User must be added to the first cohort'
        );
        $this->assertTrue(
            $this->is_cohort_member((int) $user->id, (int) $cohorttwo->id),
            'User must be added to the second cohort'
        );
    }

    /**
     * Tests that execute() removes a user from a cohort and returns true.
     *
     * @covers \userdeleteaction_cohort\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_removes_user_from_cohort(): void {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member((int) $cohort->id, (int) $user->id);

        $step = $this->create_step();
        $action = $this->create_action($step, ['cohortids' => [(int) $cohort->id], 'mode' => 'remove']);
        $process = $this->create_process((int) $user->id, $step);

        $this->assertTrue(
            $this->is_cohort_member((int) $user->id, (int) $cohort->id),
            'User must be a cohort member before execute()'
        );

        $result = $action->execute($process);
        $this->assertTrue($result, 'cohort action execute() must return true on success');

        $this->assertFalse(
            $this->is_cohort_member((int) $user->id, (int) $cohort->id),
            'User must no longer be a cohort member after execute() in remove mode'
        );
    }

    /**
     * Tests that execute() removes a user from multiple cohorts and returns true.
     *
     * @covers \userdeleteaction_cohort\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_removes_user_from_multiple_cohorts(): void {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $cohortone = $this->getDataGenerator()->create_cohort();
        $cohorttwo = $this->getDataGenerator()->create_cohort();
        cohort_add_member((int) $cohortone->id, (int) $user->id);
        cohort_add_member((int) $cohorttwo->id, (int) $user->id);

        $step = $this->create_step();
        $action = $this->create_action($step, [
            'cohortids' => [(int) $cohortone->id, (int) $cohorttwo->id],
            'mode' => 'remove',
        ]);
        $process = $this->create_process((int) $user->id, $step);

        $result = $action->execute($process);
        $this->assertTrue($result, 'cohort action execute() must return true on success');

        $this->assertFalse(
            $this->is_cohort_member((int) $user->id, (int) $cohortone->id),
            'User must be removed from the first cohort'
        );
        $this->assertFalse(
            $this->is_cohort_member((int) $user->id, (int) $cohorttwo->id),
            'User must be removed from the second cohort'
        );
    }

    /**
     * Tests that execute() is idempotent: adding an already-member user succeeds
     * and leaves the user in the cohort.
     *
     * @covers \userdeleteaction_cohort\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_is_idempotent_for_already_added_user(): void {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $cohort = $this->getDataGenerator()->create_cohort();
        cohort_add_member((int) $cohort->id, (int) $user->id);

        $step = $this->create_step();
        $action = $this->create_action($step, ['cohortids' => [(int) $cohort->id], 'mode' => 'add']);
        $process = $this->create_process((int) $user->id, $step);

        $result = $action->execute($process);
        $this->assertTrue($result, 'cohort action execute() on already-member user must still return true');

        $this->assertTrue(
            $this->is_cohort_member((int) $user->id, (int) $cohort->id),
            'User must remain a cohort member after a redundant add execute()'
        );
    }

    /**
     * Tests that execute() is idempotent: removing a user who is not a member
     * succeeds and the user remains not a member.
     *
     * @covers \userdeleteaction_cohort\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_is_idempotent_for_already_removed_user(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $cohort = $this->getDataGenerator()->create_cohort();

        $step = $this->create_step();
        $action = $this->create_action($step, ['cohortids' => [(int) $cohort->id], 'mode' => 'remove']);
        $process = $this->create_process((int) $user->id, $step);

        $result = $action->execute($process);
        $this->assertTrue($result, 'cohort action execute() on non-member user in remove mode must still return true');

        $this->assertFalse(
            $this->is_cohort_member((int) $user->id, (int) $cohort->id),
            'User must remain a non-member after a redundant remove execute()'
        );
    }

    /**
     * Tests that a default instance (no cohorts selected) is considered invalid,
     * and becomes valid once cohortids are set.
     *
     * @covers \userdeleteaction_cohort\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_validity_requires_cohortids(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertFalse($action->is_valid(), 'cohort action without cohortids must be invalid');

        $cohort = $this->getDataGenerator()->create_cohort();
        $action->set_instance_setting('cohortids', [(int) $cohort->id]);
        $this->assertTrue($action->is_valid(), 'cohort action with cohortids set must be valid');
    }

    /**
     * Tests that get_instance_details() returns a string containing the add mode
     * prefix and cohort name when in add mode.
     *
     * @covers \userdeleteaction_cohort\userdeleteaction
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details_returns_add_description(): void {
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort(['name' => 'Test Cohort Alpha']);
        $step = $this->create_step();
        $action = $this->create_action($step, [
            'cohortids' => [(int) $cohort->id],
            'mode' => 'add',
        ]);

        $details = $action->get_instance_details();

        $this->assertStringContainsString(
            get_string('details_mode_add', 'userdeleteaction_cohort'),
            $details,
            'Instance details must contain the add mode prefix'
        );
        $this->assertStringContainsString(
            'Test Cohort Alpha',
            $details,
            'Instance details must contain the cohort name'
        );
    }

    /**
     * Tests that get_instance_details() returns a string containing the remove mode
     * prefix and cohort name when in remove mode.
     *
     * @covers \userdeleteaction_cohort\userdeleteaction
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details_returns_remove_description(): void {
        $this->resetAfterTest();

        $cohort = $this->getDataGenerator()->create_cohort(['name' => 'Test Cohort Beta']);
        $step = $this->create_step();
        $action = $this->create_action($step, [
            'cohortids' => [(int) $cohort->id],
            'mode' => 'remove',
        ]);

        $details = $action->get_instance_details();

        $this->assertStringContainsString(
            get_string('details_mode_remove', 'userdeleteaction_cohort'),
            $details,
            'Instance details must contain the remove mode prefix'
        );
        $this->assertStringContainsString(
            'Test Cohort Beta',
            $details,
            'Instance details must contain the cohort name'
        );
    }

    /**
     * Tests that get_instance_details() returns an empty string when no cohorts
     * have been configured.
     *
     * @covers \userdeleteaction_cohort\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details_returns_empty_string_without_cohortids(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertSame(
            '',
            $action->get_instance_details(),
            'cohort action get_instance_details() must return empty string when no cohorts are configured'
        );
    }
}
