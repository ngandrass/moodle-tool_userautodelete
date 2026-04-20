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
 * Tests for the action log table renderer
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\output;

use tool_userautodelete\logger;

/**
 * Tests for the action log table renderer.
 */
final class log_table_test extends \advanced_testcase {
    /**
     * Creates a log table instance and renders its HTML output.
     *
     * @param int|null $workflowid Optional workflow ID filter
     * @param int|null $stepid Optional step ID filter
     * @return string Rendered HTML output
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    protected function render_table(?int $workflowid = null, ?int $stepid = null): string {
        $table = new log_table('log-table-test', $workflowid, $stepid);
        $table->define_baseurl(new \moodle_url('/admin/tool/userautodelete/log.php'));

        ob_start();
        $table->out(20, false);
        return (string) ob_get_clean();
    }

    /**
     * Tests that a log entry with a known action, workflow, and step is rendered correctly.
     *
     * @covers \tool_userautodelete\output\log_table
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_rendered_table_html_contains_log_entry_data(): void {
        $this->resetAfterTest();

        /** @var \tool_userautodelete_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_userautodelete');
        $workflow = $generator->create_simple_suspend_workflow('Test Workflow', 'Description', true);
        $step = $workflow->steps[0];

        $timestamp = 1700000000;
        logger::action('unsuspend', 5, $workflow->id, $step->id, $timestamp);

        $html = $this->render_table();

        $this->assertStringContainsString(
            'Test Workflow',
            $html,
            'Workflow title is missing from table output'
        );
        $this->assertStringContainsString(
            '#step-' . $step->id,
            $html,
            'Step anchor is missing from table output'
        );
        $this->assertStringContainsString(
            get_string('pluginname', 'userdeleteaction_unsuspend'),
            $html,
            'Action plugin name is missing from table output'
        );
    }

    /**
     * Tests that log entries are filtered correctly when a workflowid is provided.
     *
     * @covers \tool_userautodelete\output\log_table::__construct
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_workflow_filter_limits_displayed_entries(): void {
        $this->resetAfterTest();

        /** @var \tool_userautodelete_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_userautodelete');
        $workflow1 = $generator->create_simple_suspend_workflow('Workflow One', '', true);
        $workflow2 = $generator->create_simple_suspend_workflow('Workflow Two', '', true);

        logger::action('unsuspend', 1, $workflow1->id, null, 1700000000);
        logger::action('suspend', 2, $workflow2->id, null, 1700000001);

        $html = $this->render_table($workflow1->id);

        $this->assertStringContainsString('Workflow One', $html, 'Filtered workflow entry is missing');
        $this->assertStringNotContainsString('Workflow Two', $html, 'Non-matching workflow entry should not be visible');
    }

    /**
     * Tests that log entries are filtered correctly when a stepid is provided.
     *
     * @covers \tool_userautodelete\output\log_table::__construct
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_step_filter_limits_displayed_entries(): void {
        $this->resetAfterTest();

        /** @var \tool_userautodelete_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_userautodelete');
        $workflow = $generator->create_multistep_suspend_workflow('Multi Workflow', '', true);
        $step1 = $workflow->steps[0];
        $step2 = $workflow->steps[1];

        logger::action('unsuspend', 1, $workflow->id, $step1->id, 1700000000);
        logger::action('suspend', 1, $workflow->id, $step2->id, 1700000001);

        $html = $this->render_table(null, $step1->id);

        $this->assertStringContainsString('#step-' . $step1->id, $html, 'Filtered step anchor is missing');
        $this->assertStringNotContainsString('#step-' . $step2->id, $html, 'Non-matching step entry should not be visible');
    }

    /**
     * Tests that the workflow column shows "None" when workflowid is null.
     *
     * @covers \tool_userautodelete\output\log_table::col_workflow
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_col_workflow_shows_none_when_no_workflow(): void {
        $this->resetAfterTest();

        logger::action('unsuspend', 3, null, null, 1700000000);

        $html = $this->render_table();

        $this->assertStringContainsString(get_string('none'), $html, '"None" label is missing for entries without a workflow');
    }

    /**
     * Tests that the step column shows "None" when stepid is null.
     *
     * @covers \tool_userautodelete\output\log_table::col_step
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_col_step_shows_none_when_no_step(): void {
        $this->resetAfterTest();

        /** @var \tool_userautodelete_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_userautodelete');
        $workflow = $generator->create_simple_suspend_workflow('Workflow', '', true);

        logger::action('unsuspend', 1, $workflow->id, null, 1700000000);

        $html = $this->render_table($workflow->id);

        $this->assertStringContainsString(get_string('none'), $html, '"None" label is missing for entries without a step');
    }

    /**
     * Tests that an unknown action name is rendered as a plain label with a fallback icon.
     *
     * @covers \tool_userautodelete\output\log_table::col_action
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_col_action_renders_unknown_action_as_plain_label(): void {
        $this->resetAfterTest();

        logger::action('nonexistentaction', 0, null, null, 1700000000);

        $html = $this->render_table();

        $this->assertStringContainsString(
            'nonexistentaction',
            $html,
            'Unknown action name should appear as plain text in the table'
        );
    }
}
