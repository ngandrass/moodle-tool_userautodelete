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
 * Unit tests for the userdeletefilter_date sub-plugin.
 *
 * @package   userdeletefilter_date
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_date;

use tool_userautodelete\step;
use tool_userautodelete\userdeletefilter;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeletefilter_testcase.php');


/**
 * Unit tests for the userdeletefilter_date sub-plugin.
 */
final class userdeletefilter_test extends \tool_userautodelete\userdeletefilter_testcase {
    /**
     * Returns the short plugin name of the filter sub-plugin under test.
     */
    protected function get_plugin_name(): string {
        return 'date';
    }

    /**
     * Returns the expected font-awesome icon CSS class string for the filter
     * sub-plugin under test, e.g. 'fa-solid fa-filter'.
     */
    protected function get_expected_icon_class(): string {
        return 'fa-regular fa-calendar';
    }

    /**
     * Creates and returns a filter instance that carries valid settings so that
     * user_records_filter_clause() can be called without throwing.
     *
     * @param step $step The step to attach the filter instance to.
     * @return userdeletefilter A properly configured filter instance.
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function create_valid_filter_instance(step $step): userdeletefilter {
        return $this->create_filter($step, ['weekday' => [7]]);
    }

    /**
     * Tests that the filter clause is TRUE when criteria match the current date
     * and FALSE when criteria do not match.
     *
     * @covers \userdeletefilter_date\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_correct_users(): void {
        $this->resetAfterTest();
        $now = time();

        $matchinguser = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $step = $this->create_step();

        $matchingfilter = $this->create_filter($step, [
            'weekday' => [(int) date('N', $now)],
            'day' => [(int) date('j', $now)],
            'month' => [(int) date('n', $now)],
            'year' => [(int) date('Y', $now)],
        ]);
        $matchingclause = $matchingfilter->user_records_filter_clause();
        $matchingids = $this->query_users_matching_clause($matchingclause);

        $this->assertContains(
            (int) $matchinguser->id,
            $matchingids,
            'Filter must include users when all configured criteria match the current date'
        );
        $this->assertContains(
            (int) $otheruser->id,
            $matchingids,
            'Filter must evaluate to TRUE globally when criteria match and therefore include any user'
        );

        $nonmatchingfilter = $this->create_filter($step, ['year' => [(int) date('Y', $now) + 1]]);
        $nonmatchingclause = $nonmatchingfilter->user_records_filter_clause();
        $nonmatchingids = $this->query_users_matching_clause($nonmatchingclause);

        $this->assertNotContains(
            (int) $matchinguser->id,
            $nonmatchingids,
            'Filter must exclude users when configured criteria do not match the current date'
        );
        $this->assertNotContains(
            (int) $otheruser->id,
            $nonmatchingids,
            'Filter must evaluate to FALSE globally when criteria do not match'
        );
    }

    /**
     * Tests that the filter clause is TRUE when only the weekday criterion is
     * configured and it matches the current weekday, and FALSE when it does not.
     *
     * @covers \userdeletefilter_date\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_correct_users_weekday_only(): void {
        $this->resetAfterTest();
        $now = time();

        $user = $this->getDataGenerator()->create_user();
        $step = $this->create_step();

        $matchingfilter = $this->create_filter($step, ['weekday' => [(int) date('N', $now)]]);
        $matchingids = $this->query_users_matching_clause($matchingfilter->user_records_filter_clause());
        $this->assertContains(
            (int) $user->id,
            $matchingids,
            'Filter must include users when the weekday criterion matches the current weekday'
        );

        // Use a weekday that is guaranteed to not be today.
        $otherweekday = ((int) date('N', $now) % 7) + 1;
        $nonmatchingfilter = $this->create_filter($step, ['weekday' => [$otherweekday]]);
        $nonmatchingids = $this->query_users_matching_clause($nonmatchingfilter->user_records_filter_clause());
        $this->assertNotContains(
            (int) $user->id,
            $nonmatchingids,
            'Filter must exclude users when the weekday criterion does not match the current weekday'
        );
    }

    /**
     * Tests that the filter clause is TRUE when only the day-of-month criterion
     * is configured and it matches the current day, and FALSE when it does not.
     *
     * @covers \userdeletefilter_date\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_correct_users_day_only(): void {
        $this->resetAfterTest();
        $now = time();

        $user = $this->getDataGenerator()->create_user();
        $step = $this->create_step();

        $matchingfilter = $this->create_filter($step, ['day' => [(int) date('j', $now)]]);
        $matchingids = $this->query_users_matching_clause($matchingfilter->user_records_filter_clause());
        $this->assertContains(
            (int) $user->id,
            $matchingids,
            'Filter must include users when the day criterion matches the current day of month'
        );

        // Use a day that is guaranteed to not be today.
        $otherday = ((int) date('j', $now) % 28) + 1;
        $nonmatchingfilter = $this->create_filter($step, ['day' => [$otherday]]);
        $nonmatchingids = $this->query_users_matching_clause($nonmatchingfilter->user_records_filter_clause());
        $this->assertNotContains(
            (int) $user->id,
            $nonmatchingids,
            'Filter must exclude users when the day criterion does not match the current day of month'
        );
    }

    /**
     * Tests that the filter clause is TRUE when only the month criterion is
     * configured and it matches the current month, and FALSE when it does not.
     *
     * @covers \userdeletefilter_date\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_correct_users_month_only(): void {
        $this->resetAfterTest();
        $now = time();

        $user = $this->getDataGenerator()->create_user();
        $step = $this->create_step();

        $matchingfilter = $this->create_filter($step, ['month' => [(int) date('n', $now)]]);
        $matchingids = $this->query_users_matching_clause($matchingfilter->user_records_filter_clause());
        $this->assertContains(
            (int) $user->id,
            $matchingids,
            'Filter must include users when the month criterion matches the current month'
        );

        // Use a month that is guaranteed to not be the current one.
        $othermonth = ((int) date('n', $now) % 12) + 1;
        $nonmatchingfilter = $this->create_filter($step, ['month' => [$othermonth]]);
        $nonmatchingids = $this->query_users_matching_clause($nonmatchingfilter->user_records_filter_clause());
        $this->assertNotContains(
            (int) $user->id,
            $nonmatchingids,
            'Filter must exclude users when the month criterion does not match the current month'
        );
    }

    /**
     * Tests that the filter clause is TRUE when only the year criterion is
     * configured and it matches the current year, and FALSE when it does not.
     *
     * @covers \userdeletefilter_date\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_correct_users_year_only(): void {
        $this->resetAfterTest();
        $now = time();

        $user = $this->getDataGenerator()->create_user();
        $step = $this->create_step();

        $matchingfilter = $this->create_filter($step, ['year' => [(int) date('Y', $now)]]);
        $matchingids = $this->query_users_matching_clause($matchingfilter->user_records_filter_clause());
        $this->assertContains(
            (int) $user->id,
            $matchingids,
            'Filter must include users when the year criterion matches the current year'
        );

        $nonmatchingfilter = $this->create_filter($step, ['year' => [(int) date('Y', $now) + 1]]);
        $nonmatchingids = $this->query_users_matching_clause($nonmatchingfilter->user_records_filter_clause());
        $this->assertNotContains(
            (int) $user->id,
            $nonmatchingids,
            'Filter must exclude users when the year criterion does not match the current year'
        );
    }

    /**
     * Tests that user_records_filter_clause() throws a moodle_exception when no
     * criterion has been configured.
     *
     * @covers \userdeletefilter_date\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_clause_throws_for_missing_criteria(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $filter = $this->create_filter($step);

        $this->expectException(\moodle_exception::class);
        $filter->user_records_filter_clause();
    }

    /**
     * Tests that is_valid() returns false when no criterion is set and true once
     * a valid criterion has been configured.
     *
     * @covers \userdeletefilter_date\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_validity(): void {
        $this->resetAfterTest();
        $now = time();

        $step = $this->create_step();

        $invalid = $this->create_filter($step);
        $this->assertFalse($invalid->is_valid(), 'date filter without any criterion must be invalid');

        $valid = $this->create_filter($step, ['day' => [(int) date('j', $now)]]);
        $this->assertTrue($valid->is_valid(), 'date filter with at least one criterion must be valid');
    }

    /**
     * Tests that validate() rejects out-of-range values for each supported
     * criterion.
     *
     * @covers \userdeletefilter_date\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_validate_rejects_invalid_ranges(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        $invalidweekday = $this->create_filter($step, ['weekday' => [8]]);
        $this->assertNotNull($invalidweekday->validate(), 'weekday values outside 1..7 must be rejected');

        $invalidday = $this->create_filter($step, ['day' => [32]]);
        $this->assertNotNull($invalidday->validate(), 'day values outside 0..31 must be rejected');

        $invalidmonth = $this->create_filter($step, ['month' => [13]]);
        $this->assertNotNull($invalidmonth->validate(), 'month values outside 0..12 must be rejected');

        $invalidyear = $this->create_filter($step, ['year' => [-1]]);
        $this->assertNotNull($invalidyear->validate(), 'invalid year values must be rejected');
    }

    /**
     * Tests that get_instance_details() returns the expected representation for
     * empty, single-criterion, and multi-criterion configurations.
     *
     * @covers \userdeletefilter_date\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        $empty = $this->create_filter($step);
        $this->assertSame('', $empty->get_instance_details(), 'No configured criterion must return empty details');

        $weekday = $this->create_filter($step, ['weekday' => [1, 3]]);
        $this->assertStringStartsWith(get_string('day', 'userdeletefilter_date'), $weekday->get_instance_details());

        $day = $this->create_filter($step, ['day' => [21, 3]]);
        $this->assertSame(
            get_string('day', 'userdeletefilter_date') . ': 3, 21',
            $day->get_instance_details(),
            'Day details must contain sorted day values'
        );

        $month = $this->create_filter($step, ['month' => [1, 7]]);
        $this->assertStringStartsWith(get_string('month', 'userdeletefilter_date'), $month->get_instance_details());

        $year = $this->create_filter($step, ['year' => [2027, 2025]]);
        $this->assertSame(
            get_string('year', 'userdeletefilter_date') . ': 2025, 2027',
            $year->get_instance_details(),
            'Year details must contain sorted year values'
        );

        $multiple = $this->create_filter($step, ['day' => [1], 'month' => [1]]);
        $this->assertSame(
            get_string('multiple', 'userdeletefilter_date'),
            $multiple->get_instance_details(),
            'Multiple configured criteria must return the dedicated multiple label'
        );
    }
}
