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
 * Tests for the get_step_user_processes external function
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\external;

use core_external\external_api;
use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\process_state;
use tool_userautodelete\process;

/**
 * Tests for the get_step_user_processes external function
 */
final class get_step_user_processes_test extends \advanced_testcase {
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
     * Tests that execute() requires the admin capability declared by the web service.
     *
     * @covers \tool_userautodelete\external\get_step_user_processes
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_requires_admin_capability(): void {
        $this->resetAfterTest();

        // Prepare workflow fixture and a non-admin user.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_workflow('Workflow', 'Description', true);
        $step = $workflow->steps[0];
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        get_step_user_processes::execute($step->id, true);
    }

    /**
     * Tests execute() returns only active processes when requested.
     *
     * @covers \tool_userautodelete\external\get_step_user_processes
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_returns_only_active_processes(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Prepare workflow step and users with deterministic metadata.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_workflow('Workflow', 'Description', true);
        $step = $workflow->steps[0];

        $activeuser = $this->getDataGenerator()->create_user([
            'suspended' => 1,
            'firstname' => 'Active',
            'lastname' => 'User',
        ]);
        $finisheduser = $this->getDataGenerator()->create_user([
            'suspended' => 1,
            'firstname' => 'Finished',
            'lastname' => 'User',
        ]);
        $aborteduser = $this->getDataGenerator()->create_user([
            'suspended' => 1,
            'firstname' => 'Aborted',
            'lastname' => 'User',
        ]);

        $activelastaccess = time() - DAYSECS;
        $finishedlastaccess = time() - (2 * DAYSECS);
        $abortedlastaccess = time() - (3 * DAYSECS);

        $DB->update_record('user', ['id' => $activeuser->id, 'lastaccess' => $activelastaccess]);
        $DB->update_record('user', ['id' => $finisheduser->id, 'lastaccess' => $finishedlastaccess]);
        $DB->update_record('user', ['id' => $aborteduser->id, 'lastaccess' => $abortedlastaccess]);

        // Create three processes in the same step but different states.
        $activeprocess = process::create((int) $activeuser->id, $workflow, $step);
        $finishedprocess = process::create((int) $finisheduser->id, $workflow, $step);
        $abortedprocess = process::create((int) $aborteduser->id, $workflow, $step);

        $finishedtime = time() - (2 * DAYSECS);
        $activetime = time() - (3 * DAYSECS);
        $abortedtime = time() - DAYSECS;

        $DB->update_record(db_table::USER_PROCESS->value, [
            'id' => $finishedprocess->id,
            'state' => process_state::FINISHED->value,
            'timemodified' => $finishedtime,
        ]);
        $DB->update_record(db_table::USER_PROCESS->value, [
            'id' => $activeprocess->id,
            'timemodified' => $activetime,
        ]);
        $DB->update_record(db_table::USER_PROCESS->value, [
            'id' => $abortedprocess->id,
            'state' => process_state::ABORTED->value,
            'timemodified' => $abortedtime,
        ]);

        $result = get_step_user_processes::execute($step->id, true);
        $result = external_api::clean_returnvalue(get_step_user_processes::execute_returns(), $result);

        $this->assertCount(1, $result, 'Only the active process should be returned when activeonly is true');
        $row = reset($result);

        $this->assertSame($activeprocess->id, $row['processid'], 'Returned process ID mismatch');
        $this->assertSame((int) $activeuser->id, $row['userid'], 'Returned user ID mismatch');
        $this->assertSame((string) $activeuser->username, $row['username'], 'Returned username mismatch');
        $this->assertSame("{$activeuser->firstname} {$activeuser->lastname}", $row['fullname'], 'Returned fullname mismatch');
        $this->assertSame(
            (new \moodle_url('/user/profile.php', ['id' => $activeuser->id]))->out(false),
            $row['profileurl'],
            'Returned profile URL mismatch'
        );
        $this->assertTrue($row['isactive'], 'Active process should be marked active');
        $this->assertFalse($row['isfinished'], 'Active process should not be marked finished');
        $this->assertFalse($row['isaborted'], 'Active process should not be marked aborted');
        $this->assertSame($activelastaccess, $row['lastaccess'], 'Returned lastaccess mismatch');
        $this->assertSame($activeprocess->timecreated, $row['timecreated'], 'Returned timecreated mismatch');
        $this->assertSame($activetime, $row['timemodified'], 'Returned timemodified mismatch');
        $this->assertSame(format_time(time() - $activetime), $row['timemodifiedrel'], 'Returned relative time mismatch');
    }

    /**
     * Tests execute() returns all process states in descending modification order.
     *
     * @covers \tool_userautodelete\external\get_step_user_processes
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_returns_all_process_states_in_descending_modification_order(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Prepare workflow step and three processes in different states.
        $generator = $this->get_userautodelete_generator();
        $workflow = $generator->create_multistep_suspend_workflow('Workflow', 'Description', true);
        $step = $workflow->steps[0];

        $user1 = $this->getDataGenerator()->create_user(['suspended' => 1, 'firstname' => 'Newest', 'lastname' => 'User']);
        $user2 = $this->getDataGenerator()->create_user(['suspended' => 1, 'firstname' => 'Middle', 'lastname' => 'User']);
        $user3 = $this->getDataGenerator()->create_user(['suspended' => 1, 'firstname' => 'Oldest', 'lastname' => 'User']);

        $process1 = process::create((int) $user1->id, $workflow, $step);
        $process2 = process::create((int) $user2->id, $workflow, $step);
        $process3 = process::create((int) $user3->id, $workflow, $step);

        $newesttime = time() - DAYSECS;
        $middletime = time() - (2 * DAYSECS);
        $oldesttime = time() - (3 * DAYSECS);

        $DB->update_record(db_table::USER_PROCESS->value, [
            'id' => $process1->id,
            'state' => process_state::ABORTED->value,
            'timemodified' => $newesttime,
        ]);
        $DB->update_record(db_table::USER_PROCESS->value, [
            'id' => $process2->id,
            'state' => process_state::FINISHED->value,
            'timemodified' => $middletime,
        ]);
        $DB->update_record(db_table::USER_PROCESS->value, [
            'id' => $process3->id,
            'state' => process_state::ACTIVE->value,
            'timemodified' => $oldesttime,
        ]);

        $result = get_step_user_processes::execute($step->id, false);
        $result = external_api::clean_returnvalue(get_step_user_processes::execute_returns(), $result);

        $this->assertCount(3, $result, 'All process states should be returned when activeonly is false');
        $this->assertSame($process1->id, $result[0]['processid'], 'Newest process should be returned first');
        $this->assertSame($process2->id, $result[1]['processid'], 'Middle process should be returned second');
        $this->assertSame($process3->id, $result[2]['processid'], 'Oldest process should be returned last');

        $this->assertTrue($result[0]['isaborted'], 'First process should be marked aborted');
        $this->assertTrue($result[1]['isfinished'], 'Second process should be marked finished');
        $this->assertTrue($result[2]['isactive'], 'Third process should be marked active');
    }
}
