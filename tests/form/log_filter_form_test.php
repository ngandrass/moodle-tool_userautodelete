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
 * Tests for the log_filter_form class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\form;

/**
 * Tests for the log_filter_form class.
 */
final class log_filter_form_test extends \advanced_testcase {
    /**
     * Prepares the page environment and returns a new log_filter_form instance.
     *
     * @param array $params Request parameters (e.g. workflowid, stepid)
     * @return log_filter_form
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function create_form(array $params = []): log_filter_form {
        /** @var \tool_userautodelete_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_userautodelete');
        $generator->prepare_form_environment('/admin/tool/userautodelete/log.php', $params);
        return new log_filter_form();
    }

    /**
     * Tests that the form can be instantiated without any filter parameters set.
     *
     * @covers \tool_userautodelete\form\log_filter_form
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_definition_runs_without_filters(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $form = $this->create_form();

        $this->assertInstanceOf(log_filter_form::class, $form, 'Form could not be instantiated without filter parameters');
    }

    /**
     * Tests that the form can be instantiated when a valid workflowid is passed.
     *
     * @covers \tool_userautodelete\form\log_filter_form
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_definition_runs_with_workflow_filter(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        /** @var \tool_userautodelete_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_userautodelete');
        $workflow = $generator->create_simple_suspend_workflow('Filter Workflow', '', true);

        $form = $this->create_form(['workflowid' => $workflow->id]);

        $this->assertInstanceOf(log_filter_form::class, $form, 'Form could not be instantiated with a workflowid parameter');
    }

    /**
     * Tests that the workflow select element contains all existing workflows.
     *
     * @covers \tool_userautodelete\form\log_filter_form
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_workflow_select_contains_all_workflows(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        /** @var \tool_userautodelete_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_userautodelete');
        $workflow1 = $generator->create_simple_suspend_workflow('Alpha Workflow', '', true);
        $workflow2 = $generator->create_simple_suspend_workflow('Beta Workflow', '', true);

        $form = $this->create_form();
        $html = $form->render();

        $this->assertStringContainsString('Alpha Workflow', $html, 'First workflow is missing from the workflow select');
        $this->assertStringContainsString('Beta Workflow', $html, 'Second workflow is missing from the workflow select');
        $this->assertStringContainsString(
            get_string('log_filter_all_workflows', 'tool_userautodelete'),
            $html,
            '"All workflows" option is missing from the workflow select'
        );
    }

    /**
     * Tests that the step select is disabled when no workflowid filter is active.
     *
     * @covers \tool_userautodelete\form\log_filter_form
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_step_select_is_disabled_without_workflow_filter(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $form = $this->create_form();
        $html = $form->render();

        $this->assertMatchesRegularExpression(
            '/name="stepid"[^>]*disabled/i',
            $html,
            'Step select should be disabled when no workflowid is selected'
        );
    }

    /**
     * Tests that the step select is enabled and populated when a workflowid filter is active.
     *
     * @covers \tool_userautodelete\form\log_filter_form
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_step_select_is_enabled_and_populated_with_workflow_filter(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        /** @var \tool_userautodelete_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_userautodelete');
        $workflow = $generator->create_simple_suspend_workflow('Step Workflow', '', true);
        $step = $workflow->steps[0];

        $form = $this->create_form(['workflowid' => $workflow->id]);
        $html = $form->render();

        $this->assertDoesNotMatchRegularExpression(
            '/name="stepid"[^>]*disabled/i',
            $html,
            'Step select should not be disabled when a workflowid is selected'
        );
        $this->assertStringContainsString(
            get_string('log_filter_all_steps', 'tool_userautodelete'),
            $html,
            '"All steps" option is missing from the step select'
        );
        $this->assertStringContainsString(
            $step->title,
            $html,
            'Step title is missing from the step select'
        );
    }
}
