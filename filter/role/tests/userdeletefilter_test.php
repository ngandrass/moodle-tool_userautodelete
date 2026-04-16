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
 * Unit tests for the userdeletefilter_role sub-plugin
 *
 * @package   userdeletefilter_role
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_role;

use context_system;
use tool_userautodelete\step;
use tool_userautodelete\userdeletefilter;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeletefilter_testcase.php');


/**
 * Unit tests for the userdeletefilter_role sub-plugin
 */
final class userdeletefilter_test extends \tool_userautodelete\userdeletefilter_testcase {
    /**
     * @inheritDoc
     */
    protected function get_plugin_name(): string {
        return 'role';
    }

    /**
     * @inheritDoc
     */
    protected function get_expected_icon_class(): string {
        return 'fa-solid fa-user-tag';
    }

    /**
     * @inheritDoc
     */
    protected function create_valid_filter_instance(step $step): userdeletefilter {
        // Use the student role which is always present in Moodle installations.
        $studentrole = $this->get_role_id('student');
        return $this->create_filter($step, ['roleids' => [$studentrole], 'inverted' => false]);
    }

    /**
     * Returns the ID of a built-in role by its short name.
     *
     * @param string $shortname Role shortname, e.g. 'student'
     * @return int The role ID
     * @throws \dml_exception
     */
    private function get_role_id(string $shortname): int {
        global $DB;

        return (int) $DB->get_field('role', 'id', ['shortname' => $shortname], MUST_EXIST);
    }

    /**
     * Tests that the filter clause includes users who hold the specified role
     * and excludes users without it (inverted = false), and vice versa
     * (inverted = true).
     *
     * @covers \userdeletefilter_role\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_correct_users(): void {
        $this->resetAfterTest();

        $context = context_system::instance();
        $roleid  = $this->get_role_id('manager');

        $userrole    = $this->getDataGenerator()->create_user();
        $usernorole  = $this->getDataGenerator()->create_user();

        // Assign the student role to only the first user.
        role_assign($roleid, $userrole->id, $context->id);

        $step = $this->create_step();

        // Non-inverted: include only users who have the role.
        $filter  = $this->create_filter($step, ['roleids' => [$roleid], 'inverted' => false]);
        $clause  = $filter->user_records_filter_clause();
        $matched = $this->query_users_matching_clause($clause);

        $this->assertContains(
            (int) $userrole->id,
            $matched,
            'Non-inverted role filter must include a user who has the specified role'
        );
        $this->assertNotContains(
            (int) $usernorole->id,
            $matched,
            'Non-inverted role filter must exclude a user who does not have the role'
        );

        // Inverted: exclude users who have the role, include users without it.
        $invertedfilter  = $this->create_filter($step, ['roleids' => [$roleid], 'inverted' => true]);
        $invertedclause  = $invertedfilter->user_records_filter_clause();
        $invertedmatched = $this->query_users_matching_clause($invertedclause);

        $this->assertContains(
            (int) $usernorole->id,
            $invertedmatched,
            'Inverted role filter must include a user who does NOT have the specified role'
        );
        $this->assertNotContains(
            (int) $userrole->id,
            $invertedmatched,
            'Inverted role filter must exclude a user who DOES have the specified role'
        );
    }

    /**
     * Tests that the filter clause correctly handles multiple roles in the
     * configured list.
     *
     * @covers \userdeletefilter_role\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_multiple_roles(): void {
        $this->resetAfterTest();

        $context   = context_system::instance();
        $studentid = $this->get_role_id('student');
        $teacherid = $this->get_role_id('teacher');

        $student     = $this->getDataGenerator()->create_user();
        $teacher     = $this->getDataGenerator()->create_user();
        $neitherrole = $this->getDataGenerator()->create_user();

        role_assign($studentid, $student->id, $context->id);
        role_assign($teacherid, $teacher->id, $context->id);

        $step = $this->create_step();
        $filter = $this->create_filter($step, ['roleids' => [$studentid, $teacherid], 'inverted' => false]);
        $clause = $filter->user_records_filter_clause();
        $matched = $this->query_users_matching_clause($clause);

        $this->assertContains((int) $student->id, $matched, 'Filter must include the student');
        $this->assertContains((int) $teacher->id, $matched, 'Filter must include the teacher');
        $this->assertNotContains((int) $neitherrole->id, $matched, 'Filter must exclude user with no matching role');
    }

    /**
     * Tests that is_valid() returns false when the required 'roleids' setting
     * is empty and true once a valid role ID has been configured.
     *
     * @covers \userdeletefilter_role\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_validity(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        // No roleids (default empty string) → must be invalid.
        $invalid = $this->create_filter($step);
        $this->assertFalse($invalid->is_valid(), 'role filter without roleids must be invalid');

        // Set a valid role ID → must become valid.
        $valid = $this->create_valid_filter_instance($step);
        $this->assertTrue($valid->is_valid(), 'role filter with roleids set must be valid');
    }

    /**
     * Tests that get_instance_details() returns an empty string when no roles
     * are configured, and a non-empty string once role IDs are set.
     *
     * @covers \userdeletefilter_role\userdeletefilter::get_instance_details
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        // No roles → empty details.
        $empty = $this->create_filter($step);
        $this->assertSame(
            '',
            $empty->get_instance_details(),
            'role filter without roleids must return empty instance details'
        );

        // With a valid role → non-empty details.
        $configured = $this->create_valid_filter_instance($step);
        $this->assertNotEmpty(
            $configured->get_instance_details(),
            'role filter with roleids configured must return non-empty instance details'
        );
    }
}

