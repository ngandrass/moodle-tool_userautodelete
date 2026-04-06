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
 * Tests for the plugin test data generator
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

/**
 * Tests for the plugin test data generator
 */
final class generator_test extends \advanced_testcase {
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
     * Asserts the filter and action plugin composition of a generated step.
     *
     * @param step $step Step to inspect
     * @param string[] $expectedfilters Expected filter plugin names
     * @param string[] $expectedactions Expected action plugin names
     * @return void
     */
    private function assert_step_subplugins(step $step, array $expectedfilters, array $expectedactions): void {
        $this->assertEqualsCanonicalizing(
            $expectedfilters,
            array_map(static fn(userdeletefilter $filter): string => $filter::get_plugin_name(), $step->filters),
            'Unexpected step filter composition',
        );
        $this->assertEqualsCanonicalizing(
            $expectedactions,
            array_map(static fn(userdeleteaction $action): string => $action::get_plugin_name(), $step->actions),
            'Unexpected step action composition',
        );
    }

    /**
     * Tests creation of the default workflow fixture with generator defaults.
     *
     * @covers \tool_userautodelete_generator
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_create_default_workflow_with_defaults(): void {
        $this->resetAfterTest();

        // Create the default generator fixture.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_default_workflow();

        // Validate basic workflow metadata and activation state.
        $this->assertInstanceOf(workflow::class, $workflow, 'Generator did not return a workflow instance');
        $this->assertSame('Default Workflow', $workflow->title, 'Default workflow title is incorrect');
        $this->assertSame(
            'This is the default workflow created by the generator.',
            $workflow->description,
            'Default workflow description is incorrect'
        );
        $this->assertTrue($workflow->active, 'Default workflow fixture should be active by default');
        $this->assertTrue($workflow->is_valid(), 'Default workflow fixture should be valid');

        // Validate generated step and sub-plugin structure.
        $reloaded = workflow::get_by_id($workflow->id);
        $steps = $reloaded->steps;
        $this->assertCount(2, $steps, 'Default generator workflow should contain two steps');
        $this->assertSame(2, $reloaded->get_step_count(), 'Default workflow step count is incorrect after reload');
        $this->assertSame(1, $steps[0]->sort, 'First generated step sort index is incorrect');
        $this->assertSame(2, $steps[1]->sort, 'Second generated step sort index is incorrect');

        $this->assert_step_subplugins($steps[0], ['lastaccess'], ['mail']);
        $this->assert_step_subplugins($steps[1], ['delay'], ['mail', 'delete', 'anonymize']);

        // Validate relevant default sub-plugin settings.
        $this->assertSame(YEARSECS * 3, (int) $steps[0]->filters[0]->get_instance_setting('thresholdsec'));
        $this->assertSame(
            get_string('defaultworkflow_warningmail_subject', 'tool_userautodelete'),
            $steps[0]->actions[0]->get_instance_setting('subject'),
            'Default warning mail subject is incorrect'
        );
        $this->assertSame(
            get_string('defaultworkflow_warningmail_message', 'tool_userautodelete'),
            $steps[0]->actions[0]->get_instance_setting('message'),
            'Default warning mail message is incorrect'
        );
        $this->assertSame(DAYSECS * 30, (int) $steps[1]->filters[0]->get_instance_setting('delaysec'));
        $this->assertSame(
            get_string('defaultworkflow_deletemail_subject', 'tool_userautodelete'),
            $steps[1]->actions[0]->get_instance_setting('subject'),
            'Default deletion mail subject is incorrect'
        );
        $this->assertSame(
            get_string('defaultworkflow_deletemail_message', 'tool_userautodelete'),
            $steps[1]->actions[0]->get_instance_setting('message'),
            'Default deletion mail message is incorrect'
        );
    }

    /**
     * Tests creation of the default workflow fixture with custom metadata.
     *
     * @covers \tool_userautodelete_generator
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_create_default_workflow_with_overrides(): void {
        $this->resetAfterTest();

        // Create an inactive default workflow fixture with custom metadata.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_default_workflow('Custom workflow', 'Custom description', false);

        // Validate custom metadata and that the fixture remains inactive.
        $this->assertSame('Custom workflow', $workflow->title, 'Custom workflow title is incorrect');
        $this->assertSame('Custom description', $workflow->description, 'Custom workflow description is incorrect');
        $this->assertFalse($workflow->active, 'Workflow fixture should remain inactive when requested');
        $this->assertTrue($workflow->is_valid(), 'Inactive default workflow fixture should still be valid');
    }

    /**
     * Tests creation of the single-step suspension workflow fixture.
     *
     * @covers \tool_userautodelete_generator
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_create_simple_suspend_workflow(): void {
        $this->resetAfterTest();

        // Create single-step suspension workflow fixture using generator defaults.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_simple_suspend_workflow(null, null, true);

        // Validate workflow metadata and structure.
        $this->assertSame('Suspend Test Workflow', $workflow->title, 'Simple suspend workflow title is incorrect');
        $this->assertSame(
            'Simple workflow used for unit tests.',
            $workflow->description,
            'Simple suspend workflow description is incorrect'
        );
        $this->assertTrue($workflow->active, 'Simple suspend workflow fixture should be active');
        $this->assertTrue($workflow->is_valid(), 'Simple suspend workflow fixture should be valid');

        $reloaded = workflow::get_by_id($workflow->id);
        $steps = $reloaded->steps;
        $this->assertCount(1, $steps, 'Simple suspend workflow should contain exactly one step');
        $this->assertSame('Step 1', $steps[0]->title, 'Generated step title is incorrect');
        $this->assertTrue($steps[0]->is_first(), 'Generated step should be the first step');
        $this->assertTrue($steps[0]->is_final(), 'Generated step should also be the final step');

        $this->assert_step_subplugins($steps[0], ['suspension'], ['unsuspend']);
        $this->assertSame(
            1,
            (int) $steps[0]->filters[0]->get_instance_setting('suspended'),
            'Simple suspend workflow should target suspended users'
        );
    }

    /**
     * Tests creation of the multi-step suspension workflow fixture.
     *
     * @covers \tool_userautodelete_generator
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_create_multistep_suspend_workflow(): void {
        $this->resetAfterTest();

        // Create multi-step suspension workflow fixture using generator defaults.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_workflow(null, null, true);

        // Validate workflow metadata and that it remains active after the second step is added.
        $this->assertSame('Suspend Test Workflow', $workflow->title, 'Multistep suspend workflow title is incorrect');
        $this->assertSame(
            'Simple workflow used for unit tests.',
            $workflow->description,
            'Multistep suspend workflow description is incorrect'
        );
        $this->assertTrue($workflow->active, 'Multistep suspend workflow fixture should stay active');
        $this->assertTrue($workflow->is_valid(), 'Multistep suspend workflow fixture should be valid');

        // Validate the two generated steps and their complementary suspension logic.
        $reloaded = workflow::get_by_id($workflow->id);
        $steps = $reloaded->steps;
        $this->assertCount(2, $steps, 'Multistep suspend workflow should contain two steps');
        $this->assertSame(['Step 1', 'Step 2'], array_map(static fn(step $step): ?string => $step->title, $steps));

        $this->assert_step_subplugins($steps[0], ['suspension'], ['unsuspend']);
        $this->assertSame(
            1,
            (int) $steps[0]->filters[0]->get_instance_setting('suspended'),
            'First multistep filter should target suspended users'
        );

        $this->assert_step_subplugins($steps[1], ['suspension'], ['suspend']);
        $this->assertSame(
            0,
            (int) $steps[1]->filters[0]->get_instance_setting('suspended'),
            'Second multistep filter should target active users'
        );
    }

    /**
     * Tests creation of the multi-step suspension-delete workflow fixture.
     *
     * @covers \tool_userautodelete_generator
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_create_multistep_suspend_delete_workflow(): void {
        $this->resetAfterTest();

        // Create multi-step suspension-delete workflow fixture using generator defaults.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_delete_workflow(null, null, true);

        // Validate workflow metadata and that it remains active after the second step is added.
        $this->assertSame('Suspend Test Workflow', $workflow->title, 'Multistep suspend-delete workflow title is incorrect');
        $this->assertSame(
            'Simple workflow used for unit tests.',
            $workflow->description,
            'Multistep suspend-delete workflow description is incorrect'
        );
        $this->assertTrue($workflow->active, 'Multistep suspend-delete workflow fixture should stay active');
        $this->assertTrue($workflow->is_valid(), 'Multistep suspend-delete workflow fixture should be valid');

        // Validate the two generated steps and their deletion flow.
        $reloaded = workflow::get_by_id($workflow->id);
        $steps = $reloaded->steps;
        $this->assertCount(2, $steps, 'Multistep suspend-delete workflow should contain two steps');
        $this->assertSame(['Step 1', 'Step 2'], array_map(static fn(step $step): ?string => $step->title, $steps));

        $this->assert_step_subplugins($steps[0], ['suspension'], ['unsuspend']);
        $this->assertSame(
            1,
            (int) $steps[0]->filters[0]->get_instance_setting('suspended'),
            'First suspend-delete filter should target suspended users'
        );

        $this->assert_step_subplugins($steps[1], ['suspension'], ['delete']);
        $this->assertSame(
            0,
            (int) $steps[1]->filters[0]->get_instance_setting('suspended'),
            'Second suspend-delete filter should target active users'
        );
    }
}
