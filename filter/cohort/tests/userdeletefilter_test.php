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
 * Unit tests for the userdeletefilter_cohort sub-plugin
 *
 * @package   userdeletefilter_cohort
 * @copyright 2026 ssystems GmbH <oss@ssystems.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_cohort;

use context_system;
use tool_userautodelete\step;
use tool_userautodelete\userdeletefilter;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeletefilter_testcase.php');
require_once($CFG->dirroot . '/cohort/lib.php');

/**
 * Unit tests for the userdeletefilter_cohort sub-plugin
 */
final class userdeletefilter_test extends \tool_userautodelete\userdeletefilter_testcase {
    /**
     * Returns the short plugin name of the filter sub-plugin under test.
     */
    protected function get_plugin_name(): string {
        return 'cohort';
    }

    /**
     * Returns the expected font-awesome icon CSS class string for the filter sub-plugin under test.
     */
    protected function get_expected_icon_class(): string {
        return 'fa-solid fa-users';
    }

    /**
     * Creates and returns a filter instance that carries valid settings so that
     * user_records_filter_clause() can be called without throwing.
     *
     * @param step $step The step to attach the filter instance to
     * @return userdeletefilter A properly configured filter instance
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function create_valid_filter_instance(step $step): userdeletefilter {
        $cohort = $this->create_cohort('Valid cohort');
        return $this->create_filter($step, ['cohortids' => [$cohort->id], 'inverted' => false]);
    }

    /**
     * Creates a cohort in the system context.
     *
     * @param string $name Cohort name
     * @param string|null $idnumber Optional idnumber
     * @return \stdClass Cohort record
     */
    private function create_cohort(string $name, ?string $idnumber = null): \stdClass {
        return $this->getDataGenerator()->create_cohort([
            'name' => $name,
            'idnumber' => $idnumber,
            'contextid' => context_system::instance()->id,
        ]);
    }

    /**
     * Tests that the filter clause includes users who are members of the selected cohort
     * and excludes users outside it, and vice versa when inverted.
     *
     * @covers \userdeletefilter_cohort\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_correct_users(): void {
        $this->resetAfterTest();

        $cohort = $this->create_cohort('Staff');
        $cohortmember = $this->getDataGenerator()->create_user();
        $nonmember = $this->getDataGenerator()->create_user();

        cohort_add_member($cohort->id, $cohortmember->id);

        $step = $this->create_step();

        $filter = $this->create_filter($step, ['cohortids' => [$cohort->id], 'inverted' => false]);
        $clause = $filter->user_records_filter_clause();
        $matched = $this->query_users_matching_clause($clause);

        $this->assertContains((int) $cohortmember->id, $matched, 'Cohort filter must include users in the selected cohort');
        $this->assertNotContains((int) $nonmember->id, $matched, 'Cohort filter must exclude users outside the selected cohort');

        $invertedfilter = $this->create_filter($step, ['cohortids' => [$cohort->id], 'inverted' => true]);
        $invertedclause = $invertedfilter->user_records_filter_clause();
        $invertedmatched = $this->query_users_matching_clause($invertedclause);

        $this->assertContains(
            (int) $nonmember->id,
            $invertedmatched,
            'Inverted cohort filter must include users outside the selected cohort'
        );
        $this->assertNotContains(
            (int) $cohortmember->id,
            $invertedmatched,
            'Inverted cohort filter must exclude users inside the selected cohort'
        );
    }

    /**
     * Tests that the filter clause correctly handles multiple cohorts in the configured list.
     *
     * @covers \userdeletefilter_cohort\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_multiple_cohorts(): void {
        $this->resetAfterTest();

        $cohorta = $this->create_cohort('Cohort A');
        $cohortb = $this->create_cohort('Cohort B');
        $usera = $this->getDataGenerator()->create_user();
        $userb = $this->getDataGenerator()->create_user();
        $userc = $this->getDataGenerator()->create_user();

        cohort_add_member($cohorta->id, $usera->id);
        cohort_add_member($cohortb->id, $userb->id);

        $step = $this->create_step();
        $filter = $this->create_filter($step, ['cohortids' => [$cohorta->id, $cohortb->id], 'inverted' => false]);
        $clause = $filter->user_records_filter_clause();
        $matched = $this->query_users_matching_clause($clause);

        $this->assertContains((int) $usera->id, $matched, 'Filter must include users from the first selected cohort');
        $this->assertContains((int) $userb->id, $matched, 'Filter must include users from the second selected cohort');
        $this->assertNotContains((int) $userc->id, $matched, 'Filter must exclude users outside all selected cohorts');
    }

    /**
     * Tests that is_valid() returns false when the required 'cohortids' setting
     * is empty and true once a valid cohort ID has been configured.
     *
     * @covers \userdeletefilter_cohort\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_validity(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        $invalid = $this->create_filter($step);
        $this->assertFalse($invalid->is_valid(), 'cohort filter without cohortids must be invalid');

        $valid = $this->create_valid_filter_instance($step);
        $this->assertTrue($valid->is_valid(), 'cohort filter with cohortids set must be valid');
    }

    /**
     * Tests that get_instance_details() returns an empty string when no cohorts
     * are configured, and a non-empty string once cohort IDs are set.
     *
     * @covers \userdeletefilter_cohort\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        $empty = $this->create_filter($step);
        $this->assertSame(
            '',
            $empty->get_instance_details(),
            'cohort filter without cohortids must return empty instance details'
        );

        $configured = $this->create_valid_filter_instance($step);
        $this->assertNotEmpty(
            $configured->get_instance_details(),
            'cohort filter with cohortids configured must return non-empty instance details'
        );
    }

    /**
     * Tests that available cohort choices expose cohort labels including identifiers.
     *
     * @covers \userdeletefilter_cohort\userdeletefilter
     *
     * @return void
     */
    public function test_get_available_cohorts_returns_expected_labels(): void {
        $this->resetAfterTest();

        $cohort = $this->create_cohort('Employees', 'staff');
        $choices = \userdeletefilter_cohort\userdeletefilter::get_available_cohorts();

        $this->assertArrayHasKey($cohort->id, $choices, 'Created cohort must be available in cohort choices');
        $this->assertStringContainsString('Employees', $choices[$cohort->id], 'Cohort name must be present in choice label');
        $this->assertStringContainsString('[staff]', $choices[$cohort->id], 'Cohort idnumber must be present in choice label');
    }
}
