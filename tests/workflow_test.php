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
 * Tests for the workflow class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\process_state;
use tool_userautodelete\local\type\sort_move_direction;

/**
 * Tests for the workflow class
 */
final class workflow_test extends \advanced_testcase {
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
     * Tests creation, retrieval and deletion of a workflow.
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_workflow_create_retrieve_delete(): void {
        global $DB;

        $this->resetAfterTest();

        // Create new workflow and ensure that properties match.
        $workflow = workflow::create('Test Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step 1', description: '');
        $this->assertInstanceOf(workflow::class, $workflow, 'Created object is not a workflow');
        $this->assertSame('Test Workflow', $workflow->title, 'Stored workflow title is incorrect');
        $this->assertSame('Description', $workflow->description, 'Stored workflow description is incorrect');

        // Reload workflow from DB and ensure that properties match.
        $reloaded = workflow::get_by_id($workflow->id);
        $this->assertEquals($workflow, $reloaded, 'Reloaded workflow does not equal original workflow');
        $this->assertSame($workflow->id, $reloaded->id, 'Workflow ID mismatch after reload');

        // Delete workflow and ensure that it was deleted properly.
        $beforedelete = workflow::get_workflow_count();
        $workflow->delete();
        $this->assertSame($beforedelete - 1, workflow::get_workflow_count(), 'Workflow count did not decrease after delete');
        $this->assertFalse(
            $DB->record_exists(db_table::WORKFLOW_STEP->value, ['id' => $step->id]),
            'Step record still exists after parent workflow deletion'
        );

        $this->expectException(\dml_exception::class);
        workflow::get_by_id($workflow->id);
    }

    /**
     * Tests workflow creation requirements.
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_workflow_create_validation(): void {
        $this->resetAfterTest();

        $this->expectException(\moodle_exception::class);
        workflow::create('', 'Description');
    }

    /**
     * Tests that a workflow with an invalid step is considered invalid.
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_workflow_validation_fails_for_invalid_step(): void {
        $this->resetAfterTest();

        $workflow = workflow::create('Workflow', 'Description');
        step::create(workflow: $workflow, title: 'Invalid step', description: '');

        $this->assertFalse(
            $workflow->is_valid(),
            'Workflow should be invalid when one of its steps has no filters/actions'
        );
    }

    /**
     * Tests workflow::get_step_count().
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_workflow_get_step_count(): void {
        $this->resetAfterTest();

        $workflow = workflow::create('Workflow', 'Description');
        $this->assertSame(0, $workflow->get_step_count(), 'New workflow should not have any steps');

        step::create(workflow: $workflow, title: 'Step 1', description: '');
        $this->assertSame(1, $workflow->get_step_count(), 'Workflow step count should be 1 after first step creation');

        step::create(workflow: $workflow, title: 'Step 2', description: '');
        $this->assertSame(2, $workflow->get_step_count(), 'Workflow step count should be 2 after second step creation');
    }

    /**
     * Tests that empty descriptions are stored as null.
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_workflow_create_empty_description_normalized_to_null(): void {
        $this->resetAfterTest();

        $workflow = workflow::create('Workflow', '');
        $this->assertNull($workflow->description, 'Empty description should be persisted as null');
    }

    /**
     * Tests that that creating multiple workflows results in correct sort order.
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_creating_multiple_workflows(): void {
        $this->resetAfterTest();

        $workflow1 = workflow::create('Workflow 1', '');
        $workflow2 = workflow::create('Workflow 2', '');
        $workflow3 = workflow::create('Workflow 3', '');

        $this->assertSame(1, $workflow1->sort, 'First workflow sort should be 1');
        $this->assertSame(2, $workflow2->sort, 'Second workflow sort should be 2');
        $this->assertSame(3, $workflow3->sort, 'Third workflow sort should be 3');

        $this->assertSame(
            [$workflow1->id, $workflow2->id, $workflow3->id],
            array_map(fn(workflow $workflow): int => $workflow->id, workflow::get_all()),
            'Unexpected initial workflow order',
        );
        $this->assertSame(3, workflow::get_workflow_count(), 'Workflow count mismatch');
    }

    /**
     * Tests that moving workflows up and down results in correct sort order.
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_sorting_workflows(): void {
        $this->resetAfterTest();

        // Prepare workflows to sort.
        $workflow1 = workflow::create('Workflow 1', '');
        $workflow2 = workflow::create('Workflow 2', '');
        $workflow3 = workflow::create('Workflow 3', '');

        // Ensure initial sort order.
        $this->assertSame(
            [$workflow1->id, $workflow2->id, $workflow3->id],
            array_map(fn(workflow $workflow): int => $workflow->id, workflow::get_all()),
            'Unexpected initial workflow order',
        );

        // Move second workflow to the beginning.
        $workflow2->move(sort_move_direction::UP);
        $this->assertSame(1, $workflow2->sort, 'Workflow 2 should now be first');
        $this->assertSame(
            [$workflow2->id, $workflow1->id, $workflow3->id],
            array_map(fn(workflow $workflow): int => $workflow->id, workflow::get_all()),
            'Workflow order is incorrect after moving workflow 2 up',
        );

        // Move second workflow back down.
        $workflow2 = workflow::get_by_id($workflow2->id);
        $workflow2->move(sort_move_direction::DOWN);
        $this->assertSame(2, $workflow2->sort, 'Workflow 2 should now be second again');
        $this->assertSame(
            [$workflow1->id, $workflow2->id, $workflow3->id],
            array_map(fn(workflow $workflow): int => $workflow->id, workflow::get_all()),
            'Workflow order is incorrect after moving workflow 2 down',
        );

        // Ensure that we can not sort beyond boundaries.
        $workflow1 = workflow::get_by_id($workflow1->id);
        $workflow1->move(sort_move_direction::UP);
        $workflow3 = workflow::get_by_id($workflow3->id);
        $workflow3->move(sort_move_direction::DOWN);
        $this->assertSame(
            [$workflow1->id, $workflow2->id, $workflow3->id],
            array_map(fn(workflow $workflow): int => $workflow->id, workflow::get_all()),
            'Workflow order changed after boundary move attempts',
        );
    }

    /**
     * Tests title/description mutators and workflow touch behavior.
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_updating_workflow_metadata_and_touch(): void {
        global $DB;

        $this->resetAfterTest();

        // Prepare workflow instance.
        $workflow = workflow::create('Workflow', 'Initial description');
        step::create(workflow: $workflow, title: 'Step 1', description: '');
        step::create(workflow: $workflow, title: 'Step 2', description: '');

        // Prime step cache and change modification user.
        $this->assertCount(2, $workflow->steps, 'Expected two cached steps before touch fixture');
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Update props and ensure that they got updated within the existing object.
        $workflow->set_title('Updated workflow');
        $workflow->set_description('Updated description');
        $this->assertSame('Updated workflow', $workflow->title, 'Workflow title was not updated on object');
        $this->assertSame('Updated description', $workflow->description, 'Workflow description was not updated on object');

        // Insert one more step directly to keep cached steps stale until touch().
        $DB->insert_record(db_table::WORKFLOW_STEP->value, [
            'workflowid' => $workflow->id,
            'sort' => 3,
            'title' => 'Step 3',
            'description' => null,
        ]);
        $this->assertCount(2, $workflow->steps, 'Step cache should still be stale before touch');

        $beforetouch = $workflow->timemodified;
        sleep(1); // Sleep a second to ensure that timemodified will have changed.
        $workflow->touch();

        // Assert that the workflow metadata was updated.
        $this->assertSame((int) $user->id, $workflow->modifiedby, 'Workflow modifiedby was not updated');
        $this->assertGreaterThanOrEqual($beforetouch, $workflow->timemodified, 'Workflow timemodified was not updated');
        $this->assertCount(3, $workflow->steps, 'Step cache should be refreshed after touch');
    }

    /**
     * Tests workflow validity checks and activation lifecycle.
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_workflow_validity_and_activation(): void {
        $this->resetAfterTest();

        $workflow = workflow::create('Workflow', 'Description');
        $this->assertFalse($workflow->is_valid(), 'Workflow without steps should be invalid');

        $this->expectException(\moodle_exception::class);
        $workflow->activate();
    }

    /**
     * Tests activation and deactivation of a valid workflow.
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_activate_deactivate_valid_workflow(): void {
        $this->resetAfterTest();

        $workflow = workflow::create('Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step 1', description: '');
        userdeletefilter::create_instance($step, 'suspension');
        userdeleteaction::create_instance($step, 'suspend');

        $this->assertTrue($workflow->is_valid(), 'Workflow fixture should be valid before activation');
        $this->assertFalse($workflow->active, 'Workflow should be inactive by default');

        $workflow->activate();
        $this->assertTrue($workflow->active, 'Workflow should be active after activation');

        $workflow->deactivate();
        $this->assertFalse($workflow->active, 'Workflow should be inactive after deactivation');
    }

    /**
     * Tests that deactivating a workflow aborts all still active processes.
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_deactivate_aborts_active_processes(): void {
        $this->resetAfterTest();

        // Prepare active multi-step workflow with two active processes.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_workflow('Workflow', 'Description', true);
        $steps = $workflow->steps;

        $user1 = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $user2 = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $process1 = process::create((int) $user1->id, $workflow);
        $process2 = process::create((int) $user2->id, $workflow);

        $this->assertSame(process_state::ACTIVE, $process1->state, 'First fixture process should start active');
        $this->assertSame(process_state::ACTIVE, $process2->state, 'Second fixture process should start active');
        $this->assertCount(
            2,
            process::get_active_processes_for_step($steps[0]),
            'Expected two active processes before deactivation'
        );

        // Deactivate workflow and ensure all active processes are aborted.
        $workflow->deactivate();

        $this->assertFalse($workflow->active, 'Workflow should be inactive after deactivation');
        $this->assertSame(
            process_state::ABORTED,
            process::get_by_id($process1->id)->state,
            'First active process should be aborted during workflow deactivation'
        );
        $this->assertSame(
            process_state::ABORTED,
            process::get_by_id($process2->id)->state,
            'Second active process should be aborted during workflow deactivation'
        );
        $this->assertCount(
            0,
            process::get_active_processes_for_step($steps[0]),
            'No active processes should remain in the first step'
        );
        $this->assertCount(
            0,
            process::get_active_processes_for_step($steps[1]),
            'No active processes should remain in the final step'
        );
    }

    /**
     * Tests loading the default workflow into an empty workflow.
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_load_default_workflow_on_empty_workflow(): void {
        $this->resetAfterTest();

        $workflow = workflow::create('Workflow', 'Description');
        $workflow->load_default_workflow();

        // Assert basic steps and sub-plugin counters.
        $steps = $workflow->steps;
        $this->assertCount(2, $steps, 'Default workflow should contain two steps');
        $this->assertCount(1, $steps[0]->filters, 'Warning step should contain one filter');
        $this->assertCount(1, $steps[0]->actions, 'Warning step should contain one action');
        $this->assertCount(1, $steps[1]->filters, 'Deletion step should contain one filter');
        $this->assertCount(3, $steps[1]->actions, 'Deletion step should contain three actions');
    }

    /**
     * Tests that loading default workflow on non-empty workflow requires force.
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_load_default_workflow_requires_force_on_non_empty(): void {
        $this->resetAfterTest();

        $workflow = workflow::create('Workflow', 'Description');
        step::create(workflow: $workflow, title: 'Existing step', description: '');

        try {
            $workflow->load_default_workflow();
            $this->fail('Expected moodle_exception when loading default workflow without force');
        } catch (\moodle_exception $e) { // phpcs:ignore
        }

        $workflow->load_default_workflow(true);
        $this->assertCount(2, $workflow->steps, 'Forced default workflow load should replace existing steps');
    }

    /**
     * Tests workflow::get_applicable_users_count().
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_applicable_users_count(): void {
        $this->resetAfterTest();

        // Prepare workflow fixture and matching/non-matching users.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_simple_suspend_workflow('Workflow', 'Description', false);

        $this->getDataGenerator()->create_user(['suspended' => 1]);
        $this->getDataGenerator()->create_user(['suspended' => 1]);
        $this->getDataGenerator()->create_user(['suspended' => 0]);

        // Add a suspended user already covered by another workflow process.
        $blockeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $otherworkflow = $generator->create_multistep_suspend_workflow('Other workflow', 'Description', true);
        process::create((int) $blockeduser->id, $otherworkflow);

        // Assert that only users matching the first-step filter and without any process are counted.
        $this->assertSame(2, $workflow->get_applicable_users_count(), 'Applicable user count is incorrect');
    }

    /**
     * Tests that inactive workflows are ignored by workflow::process().
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_process_ignores_inactive_workflow(): void {
        global $DB;

        $this->resetAfterTest();

        // Prepare valid but inactive workflow with one matching user.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_simple_suspend_workflow('Workflow', 'Description', false);
        $user = $this->getDataGenerator()->create_user(['suspended' => 1]);

        // Processing an inactive workflow must be a no-op.
        $workflow->process();

        $this->assertSame(
            0,
            $DB->count_records(db_table::USER_PROCESS->value),
            'Inactive workflow should not create any processes'
        );
        $this->assertSame(
            1,
            (int) $DB->get_field('user', 'suspended', ['id' => $user->id]),
            'Inactive workflow should not execute step actions'
        );
    }

    /**
     * Tests that workflow::process() progresses existing processes and ingests new users.
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_process_progresses_existing_processes_and_ingests_new_users(): void {
        global $DB;

        $this->resetAfterTest();

        // Prepare active multi-step workflow with final deletion step.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_delete_workflow('Workflow', 'Description', true);

        // Seed one existing process and one yet-to-be-ingested matching user.
        $existinguser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $newuser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $nonmatchinguser = $this->getDataGenerator()->create_user(['suspended' => 0]);

        $existingprocess = process::create((int) $existinguser->id, $workflow);

        // Process workflow and assert both transition and ingestion effects.
        $workflow->process();

        $existingreloaded = process::get_by_id($existingprocess->id);
        $this->assertSame(
            $workflow->steps[1]->id,
            $existingreloaded->stepid,
            'Existing process did not transition to the second step'
        );
        $this->assertSame(
            process_state::FINISHED,
            $existingreloaded->state,
            'Existing process should finish in the final step'
        );
        $this->assertSame(
            1,
            (int) $DB->get_field('user', 'deleted', ['id' => $existinguser->id]),
            'Final step should delete transitioned user'
        );
        $this->assertCount(
            1,
            process::get_user_processes((int) $existinguser->id, includefinished: true),
            'Transitioned user should not be re-ingested into the same workflow run'
        );

        $newprocesses = process::get_user_processes((int) $newuser->id, includefinished: true);
        $this->assertCount(1, $newprocesses, 'New applicable user should be ingested into the workflow');
        $newprocess = reset($newprocesses);
        $this->assertSame(
            $workflow->steps[0]->id,
            $newprocess->stepid,
            'Newly ingested user should start in first step'
        );
        $this->assertSame(
            process_state::ACTIVE,
            $newprocess->state,
            'Newly ingested user should remain active in first step'
        );
        $this->assertSame(
            0,
            (int) $DB->get_field('user', 'suspended', ['id' => $newuser->id]),
            'Initial step should unsuspend newly ingested user'
        );

        $this->assertEmpty(
            process::get_user_processes((int) $nonmatchinguser->id, includefinished: true),
            'Non-matching user should not be ingested into the workflow'
        );
    }

    /**
     * Tests that requesting a non-existing read-only property throws an exception.
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_invalid_property_throws(): void {
        $this->resetAfterTest();

        $workflow = workflow::create('Workflow', 'Description');

        $this->expectException(\coding_exception::class);
        $unused = $workflow->nonexistingproperty;
    }

    /**
     * Tests that users with aborted processes can be ingested again.
     *
     * @covers \tool_userautodelete\workflow
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_process_reingests_user_after_aborted_process(): void {
        global $DB;

        $this->resetAfterTest();

        // Prepare active multi-step workflow and one matching suspended user.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_workflow('Workflow', 'Description', true);
        $user = $this->getDataGenerator()->create_user(['suspended' => 1]);

        // Create and abort an initial process for the user.
        $abortedprocess = process::create((int) $user->id, $workflow);
        $abortedprocess->abort();
        $this->assertSame(process_state::ABORTED, $abortedprocess->state, 'Fixture process should be aborted');

        // Restore matching filter state so the user is applicable again.
        $DB->set_field('user', 'suspended', 1, ['id' => $user->id]);

        // Process workflow and ensure a new process is ingested.
        $workflow->process();

        $allprocesses = process::get_user_processes((int) $user->id, includefinished: true, includeaborted: true);
        $this->assertCount(2, $allprocesses, 'User should have historical aborted and newly ingested process records');

        $abortedcount = count(array_filter(
            $allprocesses,
            fn(process $process): bool => $process->state === process_state::ABORTED
        ));
        $this->assertSame(1, $abortedcount, 'Exactly one aborted process should remain');

        $activeprocesses = array_values(array_filter(
            $allprocesses,
            fn(process $process): bool => $process->state === process_state::ACTIVE
        ));
        $this->assertCount(1, $activeprocesses, 'Exactly one new active process should be created');
        $this->assertSame(
            $workflow->steps[0]->id,
            $activeprocesses[0]->stepid,
            'Newly ingested process should start in first workflow step'
        );

        // First-step action should have been executed for the newly ingested process.
        $this->assertSame(
            0,
            (int) $DB->get_field('user', 'suspended', ['id' => $user->id]),
            'Re-ingested user should be unsuspended by first-step action'
        );
    }
}
