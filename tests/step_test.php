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

use tool_userautodelete\local\type\db_table;
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

        // Create workflow with three steps.
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

    /**
     * Tests next/previous navigation helpers and first/final state checks.
     *
     * @covers \tool_userautodelete\step
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_step_navigation_helpers(): void {
        $this->resetAfterTest();

        $workflow = workflow::create('Test Workflow', 'Description');
        $step1 = step::create(workflow: $workflow, title: 'Step 1', description: '');
        $step2 = step::create(workflow: $workflow, title: 'Step 2', description: '');
        $step3 = step::create(workflow: $workflow, title: 'Step 3', description: '');

        $this->assertNull($step1->previous(), 'First step should not have a previous step');
        $this->assertSame($step2->id, $step1->next()?->id, 'First step should point to second step');
        $this->assertTrue($step1->is_first(), 'First step should be marked as first');
        $this->assertFalse($step1->is_final(), 'First step should not be marked as final');

        $this->assertSame($step1->id, $step2->previous()?->id, 'Second step should point to first as previous');
        $this->assertSame($step3->id, $step2->next()?->id, 'Second step should point to third as next');
        $this->assertFalse($step2->is_first(), 'Second step should not be marked as first');
        $this->assertFalse($step2->is_final(), 'Second step should not be marked as final');

        $this->assertSame($step2->id, $step3->previous()?->id, 'Third step should point to second as previous');
        $this->assertNull($step3->next(), 'Final step should not have a next step');
        $this->assertFalse($step3->is_first(), 'Final step should not be marked as first');
        $this->assertTrue($step3->is_final(), 'Final step should be marked as final');
    }

    /**
     * Tests title and description setters including nullification for empty values.
     *
     * @covers \tool_userautodelete\step
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_updating_step_metadata(): void {
        $this->resetAfterTest();

        $workflow = workflow::create('Test Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Initial title', description: 'Initial description');

        $step->set_title('Updated title');
        $step->set_description('Updated description');
        $updated = step::get_by_id($step->id);
        $this->assertSame('Updated title', $updated->title, 'Updated step title was not persisted');
        $this->assertSame('Updated description', $updated->description, 'Updated step description was not persisted');

        $step->set_title('');
        $step->set_description('');
        $nullified = step::get_by_id($step->id);
        $this->assertNull($nullified->title, 'Empty title should be persisted as null');
        $this->assertNull($nullified->description, 'Empty description should be persisted as null');
    }

    /**
     * Tests step validity handling for missing and required sub-plugin settings.
     *
     * @covers \tool_userautodelete\step
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_step_validity_checks(): void {
        $this->resetAfterTest();

        $workflow = workflow::create('Test Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Validity step', description: '');

        $this->assertFalse($step->is_valid(), 'Step without filters/actions should be invalid');

        userdeletefilter::create_instance($step, 'suspension');
        $this->assertFalse($step->is_valid(), 'Step with only one filter but no action should be invalid');

        $mailaction = userdeleteaction::create_instance($step, 'mail');
        $this->assertFalse($step->is_valid(), 'Step with missing required mail settings should be invalid');

        $mailaction->set_instance_setting('subject', 'Subject');
        $mailaction->set_instance_setting('message', 'Message body');
        $this->assertTrue($step->is_valid(), 'Step should be valid after required settings are set');
    }

    /**
     * Tests generation of the combined SQL filter clause for a step.
     *
     * @covers \tool_userautodelete\step
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_generate_user_filter_clause(): void {
        global $CFG;

        $this->resetAfterTest();

        // Prepare a step with a filter instance.
        $workflow = workflow::create('Test Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Filter step', description: '');
        $filter = userdeletefilter::create_instance($step, 'suspension', ['suspended' => true]);

        // Generate user filter clause and validate.
        $clause = $step->generate_user_filter_clause();
        $paramkey = 'f' . $filter->id . 'suspended';

        $this->assertStringContainsString('u.suspended = :' . $paramkey, $clause->sql, 'Suspension filter SQL is missing');
        $this->assertArrayHasKey($paramkey, $clause->params, 'Renamed filter parameter is missing');
        $this->assertSame(1, $clause->params[$paramkey], 'Suspension parameter value is incorrect');
        $this->assertStringContainsString('(u.id NOT IN (', $clause->sql, 'Ignored-user SQL clause is missing');

        foreach (array_map('intval', explode(',', $CFG->siteadmins)) as $adminid) {
            $this->assertStringContainsString(
                (string) $adminid,
                $clause->sql,
                'Admin ID is missing from ignored users clause'
            );
        }
        $this->assertStringContainsString(
            (string) $CFG->siteguest,
            $clause->sql,
            'Guest user ID is missing from ignored users clause'
        );
    }

    /**
     * Tests that filter SQL parameters are uniquely renamed across filters.
     *
     * @covers \tool_userautodelete\step
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_generate_user_filter_clause_parameter_name_collisions(): void {
        $this->resetAfterTest();

        $workflow = workflow::create('Test Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Filter step', description: '');

        // Both filters emit the same source parameter name (:suspended).
        $filter1 = userdeletefilter::create_instance($step, 'suspension', ['suspended' => true]);
        $filter2 = userdeletefilter::create_instance($step, 'suspension', ['suspended' => false]);

        $clause = $step->generate_user_filter_clause();

        preg_match_all('/:f\d+suspended/', $clause->sql, $matches);
        $uniqueparamnames = array_values(array_unique($matches[0] ?? []));

        $this->assertCount(2, $uniqueparamnames, 'Each filter parameter should get a unique generated name');
        $this->assertCount(2, $clause->params, 'Generated params should contain one entry per filter parameter');
        $this->assertSame(
            [
                "f{$filter1->id}suspended" => intval(true),
                "f{$filter2->id}suspended" => intval(false),
            ],
            $clause->params,
            'Generated params should preserve values from both filters'
        );
    }

    /**
     * Tests that deleting a step cascades to linked sub-plugins and reindexes sorts.
     *
     * @covers \tool_userautodelete\step
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_deleting_step_reindexes_and_cascades(): void {
        global $DB;

        $this->resetAfterTest();

        // Prepare workflow with three steps, one of them having linked filter and action instances.
        $workflow = workflow::create('Test Workflow', 'Description');
        $step1 = step::create(workflow: $workflow, title: 'Step 1', description: '');
        $step2 = step::create(workflow: $workflow, title: 'Step 2', description: '');
        $step3 = step::create(workflow: $workflow, title: 'Step 3', description: '');

        $filter = userdeletefilter::create_instance($step2, 'suspension');
        $action = userdeleteaction::create_instance($step2, 'suspend');

        // Ensure that filter and action were actually created (just a safeguard ...).
        $this->assertTrue(
            $DB->record_exists(db_table::WORKFLOW_FILTER->value, ['id' => $filter->id]),
            'Filter record does not exist in database'
        );
        $this->assertTrue(
            $DB->record_exists(db_table::WORKFLOW_ACTION->value, ['id' => $action->id]),
            'Action record does not exist in database'
        );

        // Delete step with filter and action. Validate deletion cascade and sort order re-indexing.
        $step2->delete();

        $remainingsteps = step::get_all_workflow_steps($workflow);
        $this->assertCount(2, $remainingsteps, 'Unexpected number of steps after deletion');
        $this->assertSame(
            [$step1->id, $step3->id],
            array_map(fn(step $step): int => $step->id, $remainingsteps),
            'Unexpected step sort order'
        );
        $this->assertSame(1, $remainingsteps[0]->sort, 'First remaining step sort should be 1');
        $this->assertSame(2, $remainingsteps[1]->sort, 'Second remaining step sort should be 2');

        $this->assertFalse(
            $DB->record_exists(db_table::WORKFLOW_FILTER->value, ['id' => $filter->id]),
            'Filter record still exists in database after step deletion'
        );
        $this->assertFalse(
            $DB->record_exists(db_table::WORKFLOW_ACTION->value, ['id' => $action->id]),
            'Action record still exists in database after step deletion'
        );
    }

    /**
     * Tests that requesting a non-existing read-only property throws an exception.
     *
     * @covers \tool_userautodelete\step
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_invalid_property_throws(): void {
        $this->resetAfterTest();

        $workflow = workflow::create('Test Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step', description: '');

        $this->expectException(\coding_exception::class);
        $unused = $step->nonexistingproperty;
    }

    /**
     * Tests that touching a step clears lazy-loaded sub-plugin caches and
     * propagates touch to workflow.
     *
     * @covers \tool_userautodelete\step
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_step_touch(): void {
        global $DB;

        $this->resetAfterTest();

        // Prepare workflow with step.
        $workflow = workflow::create('Test Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step', description: '');

        // Prime lazy-loaded caches.
        $this->assertCount(0, $step->filters, 'Expected no filters before fixture setup');
        $this->assertCount(0, $step->actions, 'Expected no actions before fixture setup');

        // Insert records directly to keep existing lazy caches stale until step::touch() is called.
        $filterid = $DB->insert_record(db_table::WORKFLOW_FILTER->value, [
            'stepid' => $step->id,
            'pluginname' => 'suspension',
        ]);
        $actionid = $DB->insert_record(db_table::WORKFLOW_ACTION->value, [
            'stepid' => $step->id,
            'pluginname' => 'suspend',
        ]);

        $this->assertCount(0, $step->filters, 'Filter cache should still be stale before touch');
        $this->assertCount(0, $step->actions, 'Action cache should still be stale before touch');

        // Switch user so workflow::touch() can be asserted via modifiedby.
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $beforetouch = $step->workflow->timemodified;
        sleep(1); // Wait one second to ensure that timemodified gets advanced.

        $step->touch();

        // Validate that everything got "touched".
        $this->assertCount(1, $step->filters, 'Filter cache should be refreshed after touch');
        $this->assertCount(1, $step->actions, 'Action cache should be refreshed after touch');
        $this->assertSame($filterid, $step->filters[0]->id, 'Touched step returned unexpected filter instance');
        $this->assertSame($actionid, $step->actions[0]->id, 'Touched step returned unexpected action instance');
        $this->assertSame((int) $user->id, $step->workflow->modifiedby, 'Workflow modifiedby was not updated by touch');
        $this->assertGreaterThanOrEqual($beforetouch, $step->workflow->timemodified, 'Workflow timemodified was not updated');
    }

    /**
     * Tests timeout determination based on next-step delay filters.
     *
     * @covers \tool_userautodelete\step
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_timeoutsec(): void {
        $this->resetAfterTest();

        // If next step has no delay filter, timeout should fall back to WEEKSECS.
        $workflow = workflow::create('Workflow', 'Description');
        $step1 = step::create(workflow: $workflow, title: 'Step 1', description: '');
        $step2 = step::create(workflow: $workflow, title: 'Step 2', description: '');
        userdeletefilter::create_instance($step2, 'suspension');

        $this->assertSame(WEEKSECS, $step1->timeoutsec, 'Timeout should default to one week when no delay filter is present');
        $this->assertNull($step2->timeoutsec, 'Final step should not have a timeout');

        // If next step has multiple delay filters, the longest one should be used.
        $workflow2 = workflow::create('Workflow 2', 'Description');
        $entry = step::create(workflow: $workflow2, title: 'Entry', description: '');
        $delayed = step::create(workflow: $workflow2, title: 'Delayed', description: '');
        userdeletefilter::create_instance($delayed, 'delay', ['delaysec' => DAYSECS]);
        userdeletefilter::create_instance($delayed, 'delay', ['delaysec' => DAYSECS * 3]);
        userdeletefilter::create_instance($delayed, 'suspension');

        $this->assertSame(DAYSECS * 3, $entry->timeoutsec, 'Timeout should use the longest configured delay in the next step');
    }
}
