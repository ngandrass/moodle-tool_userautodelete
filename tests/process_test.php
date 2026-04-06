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
 * Tests for the process class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\process_state;

/**
 * Tests for the process class
 */
final class process_test extends \advanced_testcase {
    /**
     * Returns the plugin-specific test data generator.
     *
     * @return \tool_userautodelete_generator
     */
    private function get_userautodelete_generator(): \tool_userautodelete_generator {
        /** @var \tool_userautodelete_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_userautodelete');
        return $generator;
    }

    /**
     * Tests process creation, retrieval and aborting.
     *
     * @covers \tool_userautodelete\process
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_process_create_retrieve_abort(): void {
        global $DB;

        $this->resetAfterTest();

        // Prepare a valid and active multi-step workflow.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_workflow('Workflow', 'Description', true);
        $step = $workflow->steps[0];

        // Create process and verify initial data and side effects.
        $user = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $process = process::create((int) $user->id, $workflow);
        $this->assertInstanceOf(process::class, $process, 'Created object is not a process');
        $this->assertSame((int) $user->id, $process->userid, 'Process user ID mismatch');
        $this->assertSame($workflow->id, $process->workflowid, 'Process workflow ID mismatch');
        $this->assertSame($step->id, $process->stepid, 'Process should start at first workflow step');
        $this->assertSame(process_state::ACTIVE, $process->state, 'Multi-step workflow process should start active');

        $reloadeduser = $DB->get_record('user', ['id' => $user->id], 'id,suspended', MUST_EXIST);
        $this->assertSame(0, (int) $reloadeduser->suspended, 'Initial step action should have unsuspended the user');

        $reloadedprocess = process::get_by_id($process->id);
        $this->assertEquals($process, $reloadedprocess, 'Reloaded process should equal originally created process');

        // Abort process and ensure updated state is persisted.
        $beforeabort = $process->timemodified;
        sleep(1); // Ensure timemodified can advance.
        $process->abort();

        $this->assertSame(process_state::ABORTED, $process->state, 'Process state should be aborted after abort()');
        $this->assertGreaterThan($beforeabort, $process->timemodified, 'Process timemodified was not updated on abort');

        $aborted = process::get_by_id($process->id);
        $this->assertSame(process_state::ABORTED, $aborted->state, 'Aborted process state was not persisted');
    }

    /**
     * Tests get_user_processes() state filtering behavior.
     *
     * @covers \tool_userautodelete\process
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_user_processes_with_state_filters(): void {
        global $DB;

        $this->resetAfterTest();

        // Prepare user and active workflow fixture.
        $user = $this->getDataGenerator()->create_user();
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_workflow('Workflow', 'Description', true);

        // Create one process in each state.
        $aborted = process::create((int) $user->id, $workflow);
        $aborted->abort();

        $finished = process::create((int) $user->id, $workflow);
        $DB->update_record(db_table::USER_PROCESS->value, [
            'id' => $finished->id,
            'state' => process_state::FINISHED->value,
            'timemodified' => time(),
        ]);

        $active = process::create((int) $user->id, $workflow);

        // Validate state filtering behavior.
        $activeonly = process::get_user_processes((int) $user->id);
        $this->assertCount(1, $activeonly, 'Default process query should return only active processes');
        $this->assertArrayHasKey($active->id, $activeonly, 'Active process is missing from default query');

        $activeandfinished = process::get_user_processes((int) $user->id, includefinished: true, includeaborted: false);
        $this->assertCount(2, $activeandfinished, 'Query should include active and finished processes');
        $this->assertArrayHasKey($active->id, $activeandfinished, 'Active process missing when including finished');
        $this->assertArrayHasKey($finished->id, $activeandfinished, 'Finished process missing when including finished');

        $activeandaborted = process::get_user_processes((int) $user->id, includefinished: false, includeaborted: true);
        $this->assertCount(2, $activeandaborted, 'Query should include active and aborted processes');
        $this->assertArrayHasKey($active->id, $activeandaborted, 'Active process missing when including aborted');
        $this->assertArrayHasKey($aborted->id, $activeandaborted, 'Aborted process missing when including aborted');

        $allstates = process::get_user_processes((int) $user->id, includefinished: true, includeaborted: true);
        $this->assertCount(3, $allstates, 'Query should include active, finished and aborted processes');
        $this->assertArrayHasKey($active->id, $allstates, 'Active process missing when including all states');
        $this->assertArrayHasKey($finished->id, $allstates, 'Finished process missing when including all states');
        $this->assertArrayHasKey($aborted->id, $allstates, 'Aborted process missing when including all states');
    }

    /**
     * Tests get_process_stats_for_workflow() in indexed and non-indexed mode.
     *
     * @covers \tool_userautodelete\process
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_process_stats_for_workflow(): void {
        $this->resetAfterTest();

        // Prepare workflow fixture.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_workflow('Workflow', 'Description', true);
        $step1 = $workflow->steps[0];
        $step2 = $workflow->steps[1];

        // Place processes into different steps/states.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $p1 = process::create((int) $user1->id, $workflow);
        $p2 = process::create((int) $user2->id, $workflow);
        $p3 = process::create((int) $user3->id, $workflow);

        $p2->transition();
        $p3->abort();

        // Validate grouped stats in sortorder index mode.
        $stats = process::get_process_stats_for_workflow($workflow->id);
        $this->assertCount(2, $stats, 'Expected one stats entry per workflow step');

        $this->assertSame($step1->id, $stats[0]->stepid, 'First stats row should belong to step 1');
        $this->assertSame(2, $stats[0]->total, 'Unexpected total count for step 1');
        $this->assertSame(1, $stats[0]->active, 'Unexpected active count for step 1');
        $this->assertSame(0, $stats[0]->finished, 'Unexpected finished count for step 1');
        $this->assertSame(1, $stats[0]->aborted, 'Unexpected aborted count for step 1');

        $this->assertSame($step2->id, $stats[1]->stepid, 'Second stats row should belong to step 2');
        $this->assertSame(1, $stats[1]->total, 'Unexpected total count for step 2');
        $this->assertSame(0, $stats[1]->active, 'Unexpected active count for step 2');
        $this->assertSame(1, $stats[1]->finished, 'Unexpected finished count for step 2');
        $this->assertSame(0, $stats[1]->aborted, 'Unexpected aborted count for step 2');

        // Validate data from step ID indexed mode.
        $indexedstats = process::get_process_stats_for_workflow($workflow->id, indexbystepid: true);
        $this->assertArrayHasKey($step1->id, $indexedstats, 'Indexed stats should include step 1 key');
        $this->assertArrayHasKey($step2->id, $indexedstats, 'Indexed stats should include step 2 key');
        $this->assertEquals($stats[0], $indexedstats[$step1->id], 'Indexed stats for step 1 should match non-indexed data');
        $this->assertEquals($stats[1], $indexedstats[$step2->id], 'Indexed stats for step 2 should match non-indexed data');
    }

    /**
     * Tests get_active_processes_for_step() with and without transitionability filtering.
     *
     * @covers \tool_userautodelete\process
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_active_processes_for_step(): void {
        global $DB;

        $this->resetAfterTest();

        // Prepare workflow.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_workflow('Workflow', 'Description', true);
        $step = $workflow->steps[0];

        $user1 = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $user2 = $this->getDataGenerator()->create_user(['suspended' => 1]);

        // Create two active processes in the same step.
        $process1 = process::create((int) $user1->id, $workflow);
        $process2 = process::create((int) $user2->id, $workflow);

        // Both users are unsuspended by initial step action. Mark one as suspended again.
        $DB->update_record('user', ['id' => $user1->id, 'suspended' => 1]);

        // Validate non-filtered and filter-based process retrieval.
        $allactive = process::get_active_processes_for_step($step, transitionableonly: false);
        $this->assertCount(2, $allactive, 'Expected both processes in active process listing');
        $this->assertArrayHasKey($process1->id, $allactive, 'First process missing from active listing');
        $this->assertArrayHasKey($process2->id, $allactive, 'Second process missing from active listing');

        $transitionable = process::get_active_processes_for_step($step, transitionableonly: true);
        $this->assertCount(1, $transitionable, 'Transitionable query should apply step filter criteria');
        $this->assertArrayNotHasKey($process1->id, $transitionable, 'Suspended user should not be transitionable');
        $this->assertArrayHasKey($process2->id, $transitionable, 'Unsuspended user should be transitionable');
    }

    /**
     * Tests that process creation requires an active workflow.
     *
     * @covers \tool_userautodelete\process
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_create_rejects_inactive_workflow(): void {
        $this->resetAfterTest();

        // Prepare inactive but otherwise valid workflow.
        $user = $this->getDataGenerator()->create_user();
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_default_workflow('Workflow', 'Description', false);

        $this->expectException(\moodle_exception::class);
        process::create((int) $user->id, $workflow);
    }

    /**
     * Tests that process creation validates the initial step workflow relation.
     *
     * @covers \tool_userautodelete\process
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_create_rejects_invalid_initial_step(): void {
        $this->resetAfterTest();

        // Prepare two valid workflows with unrelated steps.
        $user = $this->getDataGenerator()->create_user();
        $generator = $this->get_userautodelete_generator();
        $workflow1 = $generator->create_default_workflow('Workflow 1', 'Description', true);

        $workflow2 = $generator->create_default_workflow('Workflow 2', 'Description', false);
        $workflow2step = $workflow2->steps[0];

        $this->expectException(\moodle_exception::class);
        process::create((int) $user->id, $workflow1, $workflow2step);
    }

    /**
     * Tests that a user can not have multiple active workflow processes at once.
     *
     * @covers \tool_userautodelete\process
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_create_rejects_user_already_in_workflow(): void {
        $this->resetAfterTest();

        // Prepare user and active workflow.
        $user = $this->getDataGenerator()->create_user();
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_workflow('Workflow', 'Description', true);

        // Second creation attempt for same user must fail.
        process::create((int) $user->id, $workflow);

        $this->expectException(\moodle_exception::class);
        process::create((int) $user->id, $workflow);
    }

    /**
     * Tests transition to an explicit target step and action execution.
     *
     * @covers \tool_userautodelete\process
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_transition_to_explicit_target_step(): void {
        global $DB;

        $this->resetAfterTest();

        // Prepare workflow where step actions toggle suspension state.
        $user = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_workflow('Workflow', 'Description', true);
        $step1 = $workflow->steps[0];
        $step2 = $workflow->steps[1];

        // Transition to explicit target and verify action execution.
        $process = process::create((int) $user->id, $workflow, $step1);
        $this->assertSame(
            0,
            (int) $DB->get_field('user', 'suspended', ['id' => $user->id]),
            'User should be unsuspended after first step'
        );

        $beforetransition = $process->timemodified;
        sleep(1); // Ensure timemodified can advance.
        $process->transition($step2);

        $this->assertSame($step2->id, $process->stepid, 'Process did not transition to explicit target step');
        $this->assertSame(1, (int) $DB->get_field('user', 'suspended', ['id' => $user->id]), 'Target step action was not executed');
        $this->assertGreaterThan($beforetransition, $process->timemodified, 'timemodified was not updated on transition');
    }

    /**
     * Tests that single-step workflows create immediately finished processes.
     *
     * @covers \tool_userautodelete\process
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_create_single_step_process_is_finished(): void {
        $this->resetAfterTest();

        // Prepare active single-step workflow.
        $user = $this->getDataGenerator()->create_user();
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_simple_suspend_workflow('Workflow', 'Description', true);

        $process = process::create((int) $user->id, $workflow);
        $this->assertSame(process_state::FINISHED, $process->state, 'Single-step workflow process should finish on creation');
    }

    /**
     * Tests that transition() without target fails on a single-step workflow process.
     *
     * @covers \tool_userautodelete\process
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_transition_without_target_on_final_step_throws(): void {
        $this->resetAfterTest();

        // Prepare single-step workflow where process finishes immediately.
        $user = $this->getDataGenerator()->create_user();
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_simple_suspend_workflow('Workflow', 'Description', true);

        $process = process::create((int) $user->id, $workflow);
        $this->assertSame(process_state::FINISHED, $process->state, 'Single-step workflow process should finish on creation');

        $this->expectException(\moodle_exception::class);
        $process->transition();
    }

    /**
     * Tests that transition() rejects already terminated processes.
     *
     * @covers \tool_userautodelete\process
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_transition_rejects_terminated_process(): void {
        $this->resetAfterTest();

        // Prepare process and terminate it before transition.
        $user = $this->getDataGenerator()->create_user();
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_workflow('Workflow', 'Description', true);

        $process = process::create((int) $user->id, $workflow);
        $process->abort();

        $this->expectException(\moodle_exception::class);
        $process->transition();
    }

    /**
     * Tests that transition() rejects steps from other workflows.
     *
     * @covers \tool_userautodelete\process
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_transition_rejects_step_from_other_workflow(): void {
        $this->resetAfterTest();

        // Prepare source workflow with active process.
        $user = $this->getDataGenerator()->create_user();
        $generator = $this->get_userautodelete_generator();
        $workflow1 = $generator->create_multistep_suspend_workflow('Workflow 1', 'Description', true);

        // Target step belongs to another workflow loaded from defaults.
        $workflow2 = $generator->create_default_workflow('Workflow 2', 'Description', true);
        $step2 = $workflow2->steps[0];

        $process = process::create((int) $user->id, $workflow1);

        $this->expectException(\moodle_exception::class);
        $process->transition($step2);
    }

    /**
     * Tests that requesting a non-existing read-only property throws an exception.
     *
     * @covers \tool_userautodelete\process
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_invalid_property_throws(): void {
        $this->resetAfterTest();

        // Prepare active process fixture.
        $user = $this->getDataGenerator()->create_user();
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_workflow('Workflow', 'Description', true);

        $process = process::create((int) $user->id, $workflow);

        $this->expectException(\coding_exception::class);
        $unused = $process->nonexistingproperty;
    }

    /**
     * Tests that abort_multiple() aborts only selected processes.
     *
     * @covers \tool_userautodelete\process
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_abort_multiple_aborts_selected_processes(): void {
        $this->resetAfterTest();

        // Prepare active multi-step workflow and three active processes.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_workflow('Workflow', 'Description', true);

        $user1 = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $user2 = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $user3 = $this->getDataGenerator()->create_user(['suspended' => 1]);

        $process1 = process::create((int) $user1->id, $workflow);
        $process2 = process::create((int) $user2->id, $workflow);
        $process3 = process::create((int) $user3->id, $workflow);

        $before1 = $process1->timemodified;
        $before2 = $process2->timemodified;
        $before3 = $process3->timemodified;

        sleep(1); // Ensure timemodified can advance.

        process::abort_multiple([$process1->id, $process2->id]);

        $aborted1 = process::get_by_id($process1->id);
        $aborted2 = process::get_by_id($process2->id);
        $untouched3 = process::get_by_id($process3->id);

        $this->assertSame(process_state::ABORTED, $aborted1->state, 'First selected process should be aborted');
        $this->assertSame(process_state::ABORTED, $aborted2->state, 'Second selected process should be aborted');
        $this->assertSame(process_state::ACTIVE, $untouched3->state, 'Non-selected process should remain active');
        $this->assertGreaterThan($before1, $aborted1->timemodified, 'First selected process timemodified should advance');
        $this->assertGreaterThan($before2, $aborted2->timemodified, 'Second selected process timemodified should advance');
        $this->assertSame($before3, $untouched3->timemodified, 'Non-selected process timemodified should remain unchanged');
    }

    /**
     * Tests that abort_multiple() can handle larger process ID lists.
     *
     * @covers \tool_userautodelete\process
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_abort_multiple_with_large_batches(): void {
        global $DB;

        $this->resetAfterTest();

        // Prepare active multi-step workflow and >128 active processes.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_workflow('Workflow', 'Description', true);

        $processids = [];
        for ($i = 0; $i < 130; $i++) {
            $user = $this->getDataGenerator()->create_user(['suspended' => 1]);
            $processids[] = process::create((int) $user->id, $workflow)->id;
        }

        process::abort_multiple($processids);

        $records = $DB->get_records_list(db_table::USER_PROCESS->value, 'id', $processids, '', 'id,state');
        $this->assertCount(130, $records, 'Expected all processes to be present after bulk abort');
        foreach ($records as $record) {
            $this->assertSame(
                process_state::ABORTED->value,
                (int) $record->state,
                'All processes in the large batch should be aborted'
            );
        }
    }
}
