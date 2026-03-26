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
 * Tests for the step class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

use tool_userautodelete\local\type\sort_move_direction;


/**
 * Tests for the step class
 */
final class step_test extends \advanced_testcase {
    /**
     * Tests creation, retrieval, and deletion of a step
     *
     * @covers \tool_userautodelete\step
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_step_create_retrieve_delete(): void {
        $this->resetAfterTest();

        // Create workflow to link step to.
        $workflow = workflow::create('Test Workflow', 'Description');

        // Create step and validate set properties are retrieved correctly.
        $step = step::create(
            workflow: $workflow,
            title: 'Test Step',
            description: 'Test Step Description',
        );

        $this->assertInstanceOf(step::class, $step, 'Created step object is not an instance of step class');
        $this->assertSame($workflow->id, $step->workflow->id, 'Stored workflow is incorrect');
        $this->assertSame('Test Step', $step->title, 'Step title is incorrect');
        $this->assertSame('Test Step Description', $step->description, 'Step description is incorrect');

        // Retrieve step by ID and check properties.
        $retrievedstep = step::get_by_id($step->id);
        $this->assertEquals($step, $retrievedstep, 'Retrieved step did not evaluate as equal to original step');
        $this->assertSame($step->id, $retrievedstep->id, 'Step ID missmatch');
        $this->assertSame($workflow->id, $retrievedstep->workflow->id, 'Workflow missmtach');
        $this->assertSame($step->title, $retrievedstep->title, 'Step title missmatch');
        $this->assertSame($step->description, $retrievedstep->description, 'Step description missmatch');
        $this->assertEquals($step->sort, $retrievedstep->sort, 'Step sort missmatch');

        // Delete step and check it no longer exists.
        $step->delete();
        $this->expectException(\dml_exception::class);
        step::get_by_id($step->id);
    }

    /**
     * Tests that that creating multiple steps for the same workflow results in
     * correct sort order.
     *
     * @covers \tool_userautodelete\step
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_creating_multiple_steps(): void {
        $this->resetAfterTest();
        $workflow = workflow::create('Test Workflow', 'Description');

        $step1 = step::create(workflow: $workflow, title: 'Step 1', description: '');
        $step2 = step::create(workflow: $workflow, title: 'Step 2', description: '');
        $step3 = step::create(workflow: $workflow, title: 'Step 3', description: '');

        $this->assertEquals(1, $step1->sort, 'First step sort should be 1');
        $this->assertEquals(2, $step2->sort, 'Second step sort should be 2');
        $this->assertEquals(3, $step3->sort, 'Third step sort should be 3');
    }

    /**
     * Tests that moving steps up and down results in correct sort order.
     *
     * @covers \tool_userautodelete\step
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_sorting_steps(): void {
        $this->resetAfterTest();
        $workflow = workflow::create('Test Workflow', 'Description');

        $step1 = step::create(workflow: $workflow, title: 'Step 1', description: '');
        $step2 = step::create(workflow: $workflow, title: 'Step 2', description: '');
        $step3 = step::create(workflow: $workflow, title: 'Step 3', description: '');

        $this->assertSame(
            [$step1->id, $step2->id, $step3->id],
            array_map(fn(step $step): int => $step->id, step::get_all_workflow_steps($workflow)),
            'Unexpected initial sort order',
        );

        // Move middle step up and validate swapped order.
        $step2->move(sort_move_direction::UP);
        $this->assertSame(1, $step2->sort, 'Step 2 should now be first');
        $this->assertSame(
            [$step2->id, $step1->id, $step3->id],
            array_map(fn(step $step): int => $step->id, step::get_all_workflow_steps($workflow)),
            'Sort order is incorrect after moving step 2 up',
        );
        $this->assertSame(2, step::get_by_id($step1->id)->sort, 'Step 1 should now be second');

        // Move the same step back down and validate original order is restored.
        $step2 = step::get_by_id($step2->id);
        $step2->move(sort_move_direction::DOWN);
        $this->assertSame(2, $step2->sort, 'Step 2 should now be second again');
        $this->assertSame(
            [$step1->id, $step2->id, $step3->id],
            array_map(fn(step $step): int => $step->id, step::get_all_workflow_steps($workflow)),
            'Sort order is incorrect after moving step 2 down',
        );

        // Ensure boundary moves do not change order.
        $step1 = step::get_by_id($step1->id);
        $step1->move(sort_move_direction::UP);
        $step3 = step::get_by_id($step3->id);
        $step3->move(sort_move_direction::DOWN);
        $this->assertSame(
            [$step1->id, $step2->id, $step3->id],
            array_map(fn(step $step): int => $step->id, step::get_all_workflow_steps($workflow)),
            'Sort order changed after boundary move attempts',
        );
    }
}
