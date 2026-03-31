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
 * Tests for the workflow_enable_form class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\form;

use tool_userautodelete\workflow;

/**
 * Tests for the workflow_enable_form class
 */
final class workflow_enable_form_test extends \advanced_testcase {
    /**
     * Tests that the form definition path can be executed without errors.
     *
     * @covers \tool_userautodelete\form\workflow_enable_form
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_definition_runs(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Prepare referenced workflow and request parameters.
        $workflow = workflow::create('Workflow', 'Description');
        /** @var \tool_userautodelete_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_userautodelete');
        $generator->prepare_form_environment('/admin/tool/userautodelete/manageworkflow.php', [
            'id' => $workflow->id,
            'action' => 'enable',
            'returnurl' => '/admin/tool/userautodelete/workflows.php',
        ]);

        // Walk the form definition() path.
        $form = new workflow_enable_form();
        $this->assertInstanceOf(workflow_enable_form::class, $form, 'Form could not be instantiated');
    }
}
