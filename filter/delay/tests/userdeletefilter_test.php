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
 * Unit tests for the userdeletefilter_delay sub-plugin
 *
 * @package   userdeletefilter_delay
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_delay;

use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\process_state;
use tool_userautodelete\step;
use tool_userautodelete\userdeletefilter;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeletefilter_testcase.php');


/**
 * Unit tests for the userdeletefilter_delay sub-plugin
 */
final class userdeletefilter_test extends \tool_userautodelete\userdeletefilter_testcase {
    /**
     * Returns the short plugin name of the filter sub-plugin under test.
     */
    protected function get_plugin_name(): string {
        return 'delay';
    }

    /**
     * Returns the expected font-awesome icon CSS class string for the filter
     * sub-plugin under test, e.g. 'fa-solid fa-filter'.
     */
    protected function get_expected_icon_class(): string {
        return 'fa-solid fa-hourglass';
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
        // Default value is DAYSECS * 30; any positive integer is valid.
        return $this->create_filter($step, ['delaysec' => DAYSECS * 7]);
    }

    /**
     * Inserts a raw process record for the given user and step, with a
     * configurable timemodified timestamp, and returns the process ID.
     *
     * This allows testing the delay filter's sub-query against controlled
     * process timestamps without running the full process::create() workflow.
     *
     * @param int  $userid       User ID the process belongs to
     * @param step $step         Step the process is placed in
     * @param int  $timemodified Unix timestamp for the timemodified field
     * @return int The ID of the inserted process record
     * @throws \dml_exception
     */
    private function insert_process_record(int $userid, step $step, int $timemodified): int {
        global $DB;

        $now = time();
        return $DB->insert_record(db_table::USER_PROCESS->value, [
            'userid'       => $userid,
            'stepid'       => $step->id,
            'state'        => process_state::ACTIVE->value,
            'timecreated'  => $now,
            'timemodified' => $timemodified,
        ]);
    }

    /**
     * Tests that the filter clause includes users whose process record has been
     * in the step longer than the delay and excludes users whose process was
     * created or updated recently.
     *
     * @covers \userdeletefilter_delay\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_correct_users(): void {
        $this->resetAfterTest();

        $delaysec = DAYSECS * 7;

        // Create users and filter instance.
        $delayeduser = $this->getDataGenerator()->create_user();
        $freshuser   = $this->getDataGenerator()->create_user();

        $step = $this->create_step();
        $filter = $this->create_filter($step, ['delaysec' => $delaysec]);

        // Delayed user: process entered the step longer ago than delaysec.
        $this->insert_process_record(
            (int) $delayeduser->id,
            $step,
            time() - $delaysec - 60
        );

        // Fresh user: process was just placed in the step.
        $this->insert_process_record(
            (int) $freshuser->id,
            $step,
            time()
        );

        $clause  = $filter->user_records_filter_clause();
        $matched = $this->query_users_matching_clause($clause);

        $this->assertContains(
            (int) $delayeduser->id,
            $matched,
            'Filter must include a user whose process has exceeded the delay threshold'
        );
        $this->assertNotContains(
            (int) $freshuser->id,
            $matched,
            'Filter must exclude a user whose process was just placed in the step'
        );
    }

    /**
     * Tests that the filter correctly uses only processes belonging to its own
     * step and ignores process records for other steps.
     *
     * @covers \userdeletefilter_delay\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_ignores_processes_for_other_steps(): void {
        $this->resetAfterTest();

        $delaysec = DAYSECS * 7;
        $user = $this->getDataGenerator()->create_user();

        // Create two separate steps; attach the delay filter to step1 only.
        $step1 = $this->create_step();
        $step2 = \tool_userautodelete\step::create(workflow: $step1->workflow, title: 'Step 2', description: '');

        $filter = $this->create_filter($step1, ['delaysec' => $delaysec]);

        // Insert an old process record for step2, NOT step1.
        $this->insert_process_record((int) $user->id, $step2, time() - $delaysec - 60);

        $clause  = $filter->user_records_filter_clause();
        $matched = $this->query_users_matching_clause($clause);

        $this->assertNotContains(
            (int) $user->id,
            $matched,
            'Delay filter must not match process records belonging to a different step'
        );
    }

    /**
     * Tests that user_records_filter_clause() throws a moodle_exception when
     * the configured delay is zero or negative.
     *
     * @covers \userdeletefilter_delay\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_clause_throws_for_non_positive_delay(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $filter = $this->create_filter($step, ['delaysec' => 0]);

        $this->expectException(\moodle_exception::class);
        $filter->user_records_filter_clause();
    }

    /**
     * Tests that is_valid() returns true when a positive delaysec value is set.
     *
     * @covers \userdeletefilter_delay\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_validity(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        // Default value is DAYSECS * 30, so a fresh instance must be valid.
        $filter = $this->create_filter($step);
        $this->assertTrue($filter->is_valid(), 'delay filter with default delaysec must be valid');
    }

    /**
     * Tests that get_instance_details() returns a non-empty human-readable
     * representation of the configured delay duration.
     *
     * @covers \userdeletefilter_delay\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details_returns_delay_representation(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $filter = $this->create_filter($step, ['delaysec' => DAYSECS * 7]);

        $details = $filter->get_instance_details();
        $this->assertNotEmpty($details, 'get_instance_details() must return a non-empty string');
    }
}
