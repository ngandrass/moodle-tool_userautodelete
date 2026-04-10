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

use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\process_state;


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
     * @throws \dml_exception
     * @throws \moodle_exception
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

    /**
     * Tests that manager::cleanup() triggers process cleanup for all workflows.
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \moodle_exception
     */
    public function test_cleanup_aborts_timed_out_processes_across_workflows(): void {
        global $DB;

        $this->resetAfterTest();

        // Create two active workflows so both can own real process fixtures.
        $generator = $this->get_userautodelete_generator();
        $workflow1 = $generator->create_multistep_suspend_workflow('Workflow 1', 'Description', true);
        $workflow2 = $generator->create_multistep_suspend_workflow('Workflow 2', 'Description', true);

        $timedoutuser1 = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $freshuser1 = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $finisheduser1 = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $timedoutuser2 = $this->getDataGenerator()->create_user(['suspended' => 1]);

        $timedoutprocess1 = process::create((int) $timedoutuser1->id, $workflow1);
        $freshprocess1 = process::create((int) $freshuser1->id, $workflow1);
        $finishedprocess1 = process::create((int) $finisheduser1->id, $workflow1);
        $timedoutprocess2 = process::create((int) $timedoutuser2->id, $workflow2);

        // Backdate selected active processes beyond the first-step timeout threshold.
        $oldtimestamp1 = time() - $workflow1->steps[0]->timeoutsec - 30;
        $oldtimestamp2 = time() - $workflow2->steps[0]->timeoutsec - 30;
        $DB->update_record(db_table::USER_PROCESS->value, [
            'id' => $timedoutprocess1->id,
            'timemodified' => $oldtimestamp1,
        ]);
        $DB->update_record(db_table::USER_PROCESS->value, [
            'id' => $finishedprocess1->id,
            'state' => process_state::FINISHED->value,
            'timemodified' => $oldtimestamp1,
        ]);
        $DB->update_record(db_table::USER_PROCESS->value, [
            'id' => $timedoutprocess2->id,
            'timemodified' => $oldtimestamp2,
        ]);

        $manager = new manager();
        $this->assertTrue($manager->cleanup(), 'Manager cleanup should succeed with plugin enabled');

        // Timed-out active processes should be aborted in both workflows.
        $this->assertSame(
            process_state::ABORTED,
            process::get_by_id($timedoutprocess1->id)->state,
            'Timed-out active process in first workflow should be aborted'
        );
        $this->assertSame(
            process_state::ABORTED,
            process::get_by_id($timedoutprocess2->id)->state,
            'Timed-out active process in second workflow should be aborted'
        );

        // Fresh and finished processes must remain unchanged.
        $this->assertSame(
            process_state::ACTIVE,
            process::get_by_id($freshprocess1->id)->state,
            'Fresh active process should remain active after cleanup'
        );
        $this->assertSame(
            process_state::FINISHED,
            process::get_by_id($finishedprocess1->id)->state,
            'Finished process should remain finished after cleanup'
        );
    }
}
