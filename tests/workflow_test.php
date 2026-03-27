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
use tool_userautodelete\local\type\sort_move_direction;

/**
 * Tests for the workflow class
 */
final class workflow_test extends \advanced_testcase {
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
}
