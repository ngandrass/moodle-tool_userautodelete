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
 * Tests for the manager class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;


/**
 * Tests for the manager class
 */
final class manager_test extends \advanced_testcase {
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
     * This method is called before each test.
     */
    protected function setUp(): void {
        parent::setUp();

        // Disable logger by default.
        logger::disable();

        // Enable plugin by default.
        set_config('enable', true, 'tool_userautodelete');
    }

    /**
     * Tests that no workflows are executed when the plugin is disabled
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_prevent_run_if_disabled(): void {
        $this->resetAfterTest();
        logger::enable();
        set_config('enable', false, 'tool_userautodelete');

        $manager = new manager();
        $res = $manager->execute();

        $this->expectOutputString('[INFO] ' . get_string('plugin_disabled_skipping_execution', 'tool_userautodelete') . "\n");
        $this->assertSame(false, $res, 'Execution was not prevented');
    }

    /**
     * Tests that manager::execute() ingests a user only into the first matching workflow.
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_ingests_user_into_first_matching_workflow_only(): void {
        $this->resetAfterTest();

        // Create two active workflows with identical first-step filter criteria.
        $generator = $this->get_userautodelete_generator();
        $workflow1 = $generator->create_simple_suspend_workflow('Workflow 1', 'Description', true);
        $workflow2 = $generator->create_simple_suspend_workflow('Workflow 2', 'Description', true);

        // Sanity-check that the generator produced the expected sort order.
        $this->assertLessThan(
            $workflow2->sort,
            $workflow1->sort,
            'Workflow 1 should precede Workflow 2 in sort order'
        );

        // Create a user that satisfies both workflows' ingestion filter.
        $user = $this->getDataGenerator()->create_user(['suspended' => 1]);

        $manager = new manager();
        $this->assertTrue($manager->execute(), 'Manager execution should succeed with plugin enabled');

        // The user must appear in exactly one process, belonging to the first workflow.
        $allprocesses = process::get_user_processes((int) $user->id);
        $this->assertCount(
            1,
            $allprocesses,
            'User should be ingested into exactly one workflow, not duplicated across both'
        );
        $this->assertSame(
            $workflow1->id,
            reset($allprocesses)->workflowid,
            'User should be ingested into the first workflow by sort order, not the second'
        );
    }
}
