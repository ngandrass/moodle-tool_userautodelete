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
 * Unit tests for the userdeletefilter_enrolment sub-plugin
 *
 * @package   userdeletefilter_enrolment
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_enrolment;

use tool_userautodelete\step;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeletefilter_testcase.php');


/**
 * Unit tests for the userdeletefilter_enrolment sub-plugin
 */
final class userdeletefilter_test extends \tool_userautodelete\userdeletefilter_testcase {
    /**
     * Returns the short plugin name of the filter sub-plugin under test.
     */
    protected function get_plugin_name(): string {
        return 'enrolment';
    }

    /**
     * Returns the expected font-awesome icon CSS class string for the filter
     * sub-plugin under test, e.g. 'fa-solid fa-filter'.
     */
    protected function get_expected_icon_class(): string {
        return 'fa-solid fa-graduation-cap';
    }

    /**
     * Creates and returns a filter instance that carries valid settings so that
     * user_records_filter_clause() can be called without throwing.
     *
     * @param step $step The step to attach the filter instance to
     * @return \tool_userautodelete\userdeletefilter A properly configured filter instance
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function create_valid_filter_instance(step $step): \tool_userautodelete\userdeletefilter {
        return $this->create_filter($step);
    }

    /**
     * Tests that the filter clause with MUST_BE_ENROLLED includes users enrolled
     * in at least one course and excludes unenrolled users.
     *
     * @covers \userdeletefilter_enrolment\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_correct_users(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $enrolleduser = $this->getDataGenerator()->create_user();
        $unenrolleduser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($enrolleduser->id, $course->id);

        // Create a filter that targets enrolled users.
        $step = $this->create_step();
        $filter = $this->create_filter($step, ['enrolled' => userdeletefilter::MUST_BE_ENROLLED]);
        $clause = $filter->user_records_filter_clause();
        $matched = $this->query_users_matching_clause($clause);

        $this->assertContains(
            (int) $enrolleduser->id,
            $matched,
            'Filter for enrolled users must include a user enrolled in a course'
        );
        $this->assertNotContains(
            (int) $unenrolleduser->id,
            $matched,
            'Filter for enrolled users must exclude a user not enrolled in any course'
        );
    }

    /**
     * Tests that the filter clause with MUST_NOT_BE_ENROLLED includes users with
     * no enrolments and excludes users enrolled in at least one course.
     *
     * @covers \userdeletefilter_enrolment\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_correct_users_inverted(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $enrolleduser = $this->getDataGenerator()->create_user();
        $unenrolleduser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($enrolleduser->id, $course->id);

        // Create a filter that targets unenrolled users.
        $step = $this->create_step();
        $filter = $this->create_filter($step, ['enrolled' => userdeletefilter::MUST_NOT_BE_ENROLLED]);
        $clause = $filter->user_records_filter_clause();
        $matched = $this->query_users_matching_clause($clause);

        $this->assertContains(
            (int) $unenrolleduser->id,
            $matched,
            'Filter for unenrolled users must include a user not enrolled in any course'
        );
        $this->assertNotContains(
            (int) $enrolleduser->id,
            $matched,
            'Filter for unenrolled users must exclude a user enrolled in a course'
        );
    }

    /**
     * Tests that is_valid() always returns true for the enrolment filter, since
     * the enrolled setting is not required and carries a default value.
     *
     * @covers \userdeletefilter_enrolment\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_is_always_valid(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        // Instance with no explicit settings falls back to the default → must be valid.
        $filter = $this->create_filter($step);
        $this->assertTrue($filter->is_valid(), 'Enrolment filter without explicit settings must be valid');

        // Instance explicitly set to MUST_BE_ENROLLED → must also be valid.
        $filtermustbe = $this->create_filter($step, ['enrolled' => userdeletefilter::MUST_BE_ENROLLED]);
        $this->assertTrue($filtermustbe->is_valid(), 'Enrolment filter set to MUST_BE_ENROLLED must be valid');
    }

    /**
     * Tests that get_instance_details() returns the expected human-readable
     * summary string for each branch of the enrolment filter.
     *
     * @covers \userdeletefilter_enrolment\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        // Match users enrolled in at least one course.
        $filter = $this->create_filter($step, ['enrolled' => userdeletefilter::MUST_BE_ENROLLED]);
        $this->assertSame(
            get_string('enrolled', 'userdeletefilter_enrolment'),
            $filter->get_instance_details(),
            'get_instance_details() must return the "enrolled" string when targeting enrolled users'
        );

        // Match users not enrolled in any course.
        $filter->set_instance_setting('enrolled', userdeletefilter::MUST_NOT_BE_ENROLLED);
        $this->assertSame(
            get_string('not_enrolled', 'userdeletefilter_enrolment'),
            $filter->get_instance_details(),
            'get_instance_details() must return the "not_enrolled" string when targeting unenrolled users'
        );
    }
}
