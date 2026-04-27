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
 * Tests for the subplugin_instance_settings_form class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\form;

use tool_userautodelete\step;
use tool_userautodelete\userdeleteaction;
use tool_userautodelete\userdeletefilter;
use tool_userautodelete\workflow;

/**
 * Tests for the subplugin_instance_settings_form class
 */
final class subplugin_instance_settings_form_test extends \advanced_testcase {
    /**
     * Returns the plugin-specific test data generator.
     *
     * @return \tool_userautodelete_generator
     */
    private function get_generator(): \tool_userautodelete_generator {
        /** @var \tool_userautodelete_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_userautodelete');
        return $generator;
    }

    /**
     * Tests that the filter settings form definition path can be executed without errors.
     *
     * @covers \tool_userautodelete\form\subplugin_instance_settings_form
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_definition_runs_for_filter_instance(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Prepare filter instance with setting descriptors.
        $workflow = workflow::create('Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step 1', description: '');
        $filter = userdeletefilter::create_instance($step, 'suspension');
        $this->get_generator()->prepare_form_environment('/admin/tool/userautodelete/managefilter.php', [
            'instanceid' => $filter->id,
            'instancetype' => 'filter',
            'returnurl' => '/admin/tool/userautodelete/workflow.php?id=' . $workflow->id,
        ]);

        // Walk the form definition() path.
        $form = new subplugin_instance_settings_form();
        $this->assertInstanceOf(subplugin_instance_settings_form::class, $form, 'Form could not be instantiated');
    }

    /**
     * Tests that the form definition path can be executed without errors for a
     * filter using the 'autocomplete-multi' mformtype.
     *
     * The 'auth' filter exposes both an 'autocomplete-multi' and a 'selectyesno'
     * element, covering two branches of the switch in definition().
     *
     * @covers \tool_userautodelete\form\subplugin_instance_settings_form::definition
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_definition_runs_for_autocomplete_multi_mformtype(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // The 'auth' filter has settings with mformtype 'autocomplete-multi' and 'selectyesno'.
        $workflow = workflow::create('Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step 1', description: '');
        $filter = userdeletefilter::create_instance($step, 'auth');
        $this->get_generator()->prepare_form_environment('/admin/tool/userautodelete/managefilter.php', [
            'instanceid' => $filter->id,
            'instancetype' => 'filter',
            'returnurl' => '/admin/tool/userautodelete/workflow.php?id=' . $workflow->id,
        ]);

        // Walk the form definition() path.
        $form = new subplugin_instance_settings_form();
        $this->assertInstanceOf(subplugin_instance_settings_form::class, $form, 'Form could not be instantiated');
    }

    /**
     * Tests that the form definition path can be executed without errors for a
     * filter using the 'duration' mformtype (default switch case).
     *
     * @covers \tool_userautodelete\form\subplugin_instance_settings_form::definition
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_definition_runs_for_duration_mformtype(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // The 'delay' filter has a setting with mformtype 'duration'.
        $workflow = workflow::create('Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step 1', description: '');
        $filter = userdeletefilter::create_instance($step, 'delay');
        $this->get_generator()->prepare_form_environment('/admin/tool/userautodelete/managefilter.php', [
            'instanceid' => $filter->id,
            'instancetype' => 'filter',
            'returnurl' => '/admin/tool/userautodelete/workflow.php?id=' . $workflow->id,
        ]);

        // Walk the form definition() path.
        $form = new subplugin_instance_settings_form();
        $this->assertInstanceOf(subplugin_instance_settings_form::class, $form, 'Form could not be instantiated');
    }

    /**
     * Tests that the action settings form definition path can be executed without
     * errors for an action using the 'text' and 'editor' mformtype values.
     *
     * The 'mail' action exposes both a 'text' element (dedicated switch case) and
     * an 'editor' element (default switch case).
     *
     * @covers \tool_userautodelete\form\subplugin_instance_settings_form::definition
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_definition_runs_for_action_instance(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // The 'mail' action has settings with mformtype 'text' and 'editor'.
        $workflow = workflow::create('Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step 1', description: '');
        $action = userdeleteaction::create_instance($step, 'mail');
        $this->get_generator()->prepare_form_environment('/admin/tool/userautodelete/manageaction.php', [
            'instanceid' => $action->id,
            'instancetype' => 'action',
            'returnurl' => '/admin/tool/userautodelete/workflow.php?id=' . $workflow->id,
        ]);

        // Walk the form definition() path.
        $form = new subplugin_instance_settings_form();
        $this->assertInstanceOf(subplugin_instance_settings_form::class, $form, 'Form could not be instantiated');
    }

    /**
     * Tests that set_data_for_dynamic_submission() loads the current instance
     * settings into the form without throwing any exceptions.
     *
     * @covers \tool_userautodelete\form\subplugin_instance_settings_form::set_data_for_dynamic_submission
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_set_data_for_dynamic_submission(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Prepare a filter instance with a known initial setting value.
        $workflow = workflow::create('Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step 1', description: '');
        $filter = userdeletefilter::create_instance($step, 'delay', ['delaysec' => 4242424242424242]);
        $this->get_generator()->prepare_form_environment('/admin/tool/userautodelete/managefilter.php', [
            'instanceid' => $filter->id,
            'instancetype' => 'filter',
            'returnurl' => '/admin/tool/userautodelete/workflow.php?id=' . $workflow->id,
        ]);

        // Instantiate the form and populate it with current instance data.
        $form = new subplugin_instance_settings_form();
        $form->set_data_for_dynamic_submission();

        // Validate that the default data was loaded correctly.
        $this->assertStringContainsString(
            $filter->get_instance_setting('delaysec'),
            $form->render(),
            'Instance setting value was not loaded as current input value'
        );
    }

    /**
     * Tests that process_dynamic_submission() persists the submitted form values
     * to the underlying sub-plugin instance settings.
     *
     * @covers \tool_userautodelete\form\subplugin_instance_settings_form::process_dynamic_submission
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_process_dynamic_submission(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Prepare a filter instance with an initial setting value that will be changed.
        $workflow = workflow::create('Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step 1', description: '');
        $filter = userdeletefilter::create_instance($step, 'suspension', ['suspended' => false]);

        // Simulate a full form POST submission by setting up the request data.
        $postdata = [
            'instanceid' => $filter->id,
            'instancetype' => 'filter',
            'returnurl' => '/admin/tool/userautodelete/workflow.php?id=' . $workflow->id,
            's_suspended' => 1, // Change the value from false to true.
            'sesskey' => sesskey(),
            '_qf__tool_userautodelete_form_subplugin_instance_settings_form' => 1,
        ];
        $this->get_generator()->prepare_form_environment(
            '/admin/tool/userautodelete/managefilter.php',
            $postdata
        );
        $_POST = $postdata;

        // Process the simulated form submission.
        $form = new subplugin_instance_settings_form();
        $result = $form->process_dynamic_submission();

        // Verify the returned data object matches the submitted values.
        $this->assertNotNull($result, 'process_dynamic_submission() returned null');
        $this->assertEquals($filter->id, $result->instanceid, 'Returned instanceid does not match');

        // Verify that the setting was actually persisted to the database.
        $updatedfilter = userdeletefilter::get_instance_by_id($filter->id);
        $this->assertEquals(
            1,
            (int) $updatedfilter->get_instance_setting('suspended'),
            'The submitted setting value was not saved to the database'
        );
    }

    /**
     * Tests that process_dynamic_submission() stores editor settings as text.
     *
     * @covers \tool_userautodelete\form\subplugin_instance_settings_form::process_dynamic_submission
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_process_dynamic_submission_persists_editor_text_for_action(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $workflow = workflow::create('Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step 1', description: '');
        $action = userdeleteaction::create_instance($step, 'mail', [
            'subject' => 'Initial subject',
            'message' => 'Initial body',
        ]);

        $requestparams = [
            'instanceid' => $action->id,
            'instancetype' => 'action',
            'returnurl' => '/admin/tool/userautodelete/workflow.php?id=' . $workflow->id,
        ];
        $postdata = $requestparams + [
            's_subject' => 'Updated subject',
            's_message' => [
                'text' => '<p>Updated body</p>',
                'format' => FORMAT_HTML,
                'itemid' => 0,
            ],
            'sesskey' => sesskey(),
            '_qf__tool_userautodelete_form_subplugin_instance_settings_form' => 1,
        ];
        $this->get_generator()->prepare_form_environment(
            '/admin/tool/userautodelete/manageaction.php',
            $requestparams
        );
        $_POST = $postdata;
        $_REQUEST = $postdata;

        $form = new subplugin_instance_settings_form();
        $form->process_dynamic_submission();

        $updatedaction = userdeleteaction::get_instance_by_id($action->id);
        $this->assertSame('Updated subject', $updatedaction->get_instance_setting('subject'));
        $this->assertSame('<p>Updated body</p>', $updatedaction->get_instance_setting('message'));
    }

    /**
     * Tests that set_data_for_dynamic_submission() rehydrates editor defaults.
     *
     * @covers \tool_userautodelete\form\subplugin_instance_settings_form::set_data_for_dynamic_submission
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_set_data_for_dynamic_submission_reloads_editor_value_for_action(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $workflow = workflow::create('Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step 1', description: '');
        $action = userdeleteaction::create_instance($step, 'mail', [
            'subject' => 'Subject',
            'message' => '<p>Persisted body value</p>',
        ]);
        $this->get_generator()->prepare_form_environment('/admin/tool/userautodelete/manageaction.php', [
            'instanceid' => $action->id,
            'instancetype' => 'action',
            'returnurl' => '/admin/tool/userautodelete/workflow.php?id=' . $workflow->id,
        ]);

        $form = new subplugin_instance_settings_form();
        $form->set_data_for_dynamic_submission();

        $this->assertStringContainsString(
            'Persisted body value',
            $form->render(),
            'The editor body value was not restored in the form output.'
        );
    }
}
