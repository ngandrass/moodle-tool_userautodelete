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
 * Tests for the process_abort_form class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\form;

use tool_userautodelete\process;

/**
 * Tests for the process_abort_form class
 */
final class process_abort_form_test extends \advanced_testcase {
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
     * Tests that the form definition path can be executed without errors.
     *
     * @covers \tool_userautodelete\form\process_abort_form
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_definition_runs(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a workflow with an active process.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_simple_suspend_workflow('Workflow', 'Description', true);
        $step = $workflow->steps[0];
        $user = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $process = process::create((int) $user->id, $workflow, $step);

        $generator->prepare_form_environment('/admin/tool/userautodelete/manageprocess.php', [
            'id'        => $process->id,
            'action'    => 'abort',
            'returnurl' => '/admin/tool/userautodelete/workflow.php?id=' . $workflow->id,
        ]);

        $form = new process_abort_form();
        $this->assertInstanceOf(process_abort_form::class, $form, 'Form could not be instantiated');
    }
}
