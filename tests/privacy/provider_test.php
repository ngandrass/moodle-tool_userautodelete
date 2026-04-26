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
 * Tests for the privacy provider.
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandrass <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\process_state;
use tool_userautodelete\process;

/**
 * Tests for the privacy provider.
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
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
     * Tests privacy metadata declaration.
     *
     * @covers \tool_userautodelete\privacy\provider
     * @return void
     */
    public function test_get_metadata(): void {
        $collection = new collection('tool_userautodelete');
        $newcollection = provider::get_metadata($collection);
        $tables = $newcollection->get_collection();

        $this->assertCount(1, $tables);

        $table = reset($tables);
        $this->assertEquals(db_table::USER_PROCESS->value, $table->get_name());
        $this->assertEquals('privacy:metadata:tool_userautodelete_process', $table->get_summary());

        $fields = $table->get_privacy_fields();
        $this->assertArrayHasKey('userid', $fields);
        $this->assertArrayHasKey('stepid', $fields);
        $this->assertArrayHasKey('state', $fields);
        $this->assertArrayHasKey('timecreated', $fields);
        $this->assertArrayHasKey('timemodified', $fields);
    }

    /**
     * Tests context discovery for users with and without process data.
     *
     * @covers \tool_userautodelete\privacy\provider
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_contexts_for_userid(): void {
        $this->resetAfterTest();

        // Prepare a user with and a user without a process.
        $workflow = $this->get_userautodelete_generator()->create_simple_suspend_workflow(null, null, true);
        $userwithdata = $this->getDataGenerator()->create_user();
        $userwithoutdata = $this->getDataGenerator()->create_user();
        process::create((int) $userwithdata->id, $workflow);

        // Assert that the contexts match.
        $contextlistwithdata = provider::get_contexts_for_userid($userwithdata->id);
        $contextlistwithoutdata = provider::get_contexts_for_userid($userwithoutdata->id);

        $contextids = array_values($contextlistwithdata->get_contextids());
        $this->assertCount(1, $contextids);
        $this->assertEquals(\context_system::instance()->id, $contextids[0]);

        $this->assertCount(0, $contextlistwithoutdata->get_contextids());
    }

    /**
     * Tests export of user process data.
     *
     * @covers \tool_userautodelete\privacy\provider
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_export_user_data(): void {
        $this->resetAfterTest();

        $workflow = $this->get_userautodelete_generator()->create_multistep_suspend_workflow(null, null, true);
        $step = $workflow->steps[0];

        // Create two process records for the same user with different states.
        $user = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $finishedprocess = process::create((int) $user->id, $workflow);
        $finishedprocess->transition();
        $activeprocess = process::create((int) $user->id, $workflow);

        // Create additional data for another user to verify filtering.
        $otheruser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        process::create((int) $otheruser->id, $workflow);

        // Export user data.
        $systemcontext = \context_system::instance();
        /** @var \core_privacy\tests\request\content_writer $writer */
        $writer = writer::with_context($systemcontext);
        $this->assertFalse($writer->has_any_data());

        $contextlist = new approved_contextlist($user, 'tool_userautodelete', [$systemcontext->id]);
        provider::export_user_data($contextlist);

        // Validate user data export.
        $this->assertTrue($writer->has_any_data());
        $basepath = [get_string('pluginname', 'tool_userautodelete')];

        $finished = $writer->get_data([
            ...$basepath,
            get_string('user_processes', 'tool_userautodelete') . " #{$finishedprocess->id}",
        ]);
        $this->assertSame((int) $user->id, (int) $finished->userid);
        $this->assertSame((int) $workflow->id, (int) $finished->workflowid);
        $this->assertSame((int) $workflow->steps[1]->id, (int) $finished->stepid);
        $this->assertSame(process_state::FINISHED->value, (int) $finished->state);

        $active = $writer->get_data([
            ...$basepath,
            get_string('user_processes', 'tool_userautodelete') . " #{$activeprocess->id}",
        ]);
        $this->assertSame((int) $user->id, (int) $active->userid);
        $this->assertSame((int) $workflow->id, (int) $active->workflowid);
        $this->assertSame((int) $step->id, (int) $active->stepid);
        $this->assertSame(process_state::ACTIVE->value, (int) $active->state);

        $other = $writer->get_data([
            ...$basepath,
            get_string('user_processes', 'tool_userautodelete') . ' #999999',
        ]);
        $this->assertSame([], $other);
    }

    /**
     * Tests deleting all data in context.
     *
     * @covers \tool_userautodelete\privacy\provider
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        $this->resetAfterTest();

        // Prepare two users with processes.
        $workflow = $this->get_userautodelete_generator()->create_simple_suspend_workflow(null, null, true);
        $userone = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $usertwo = $this->getDataGenerator()->create_user(['suspended' => 1]);
        process::create((int) $userone->id, $workflow);
        process::create((int) $usertwo->id, $workflow);

        // Request deletion of user data and assert deletion.
        provider::delete_data_for_all_users_in_context(\context_user::instance($userone->id));
        $this->assertSame(2, $DB->count_records(db_table::USER_PROCESS->value));

        provider::delete_data_for_all_users_in_context(\context_system::instance());
        $this->assertSame(0, $DB->count_records(db_table::USER_PROCESS->value));
    }

    /**
     * Tests deleting user data for a single approved user.
     *
     * @covers \tool_userautodelete\privacy\provider
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $this->resetAfterTest();

        // Prepare two user processes.
        $workflow = $this->get_userautodelete_generator()->create_simple_suspend_workflow(null, null, true);
        $targetuser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $otheruser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        process::create((int) $targetuser->id, $workflow);
        process::create((int) $otheruser->id, $workflow);

        // Generate approved contextlist and request data deletion for one user.
        $approvedcontexts = new approved_contextlist(
            $targetuser,
            'tool_userautodelete',
            [\context_user::instance($targetuser->id)->id, \context_system::instance()->id]
        );
        provider::delete_data_for_user($approvedcontexts);

        // Assert.
        $this->assertSame(0, $DB->count_records(db_table::USER_PROCESS->value, ['userid' => $targetuser->id]));
        $this->assertSame(1, $DB->count_records(db_table::USER_PROCESS->value, ['userid' => $otheruser->id]));
    }

    /**
     * Tests user discovery in context.
     *
     * @covers \tool_userautodelete\privacy\provider
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_users_in_context(): void {
        $this->resetAfterTest();

        // Create duplicate process rows for user one and a separate row for user two.
        $workflow = $this->get_userautodelete_generator()->create_multistep_suspend_workflow(null, null, true);
        $userone = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $usertwo = $this->getDataGenerator()->create_user(['suspended' => 1]);

        $finished = process::create((int) $userone->id, $workflow);
        $finished->transition();
        process::create((int) $userone->id, $workflow);
        $aborted = process::create((int) $usertwo->id, $workflow);
        $aborted->abort();

        // Ensure processes were created as expected.
        $this->assertNotNull($finished->id);
        $this->assertNotNull($aborted->id);

        // Try retrieval of userids for different contexts.
        $nonsystemuserlist = new userlist(\context_user::instance($userone->id), 'tool_userautodelete');
        provider::get_users_in_context($nonsystemuserlist);
        $this->assertSame([], $nonsystemuserlist->get_userids());

        $systemuserlist = new userlist(\context_system::instance(), 'tool_userautodelete');
        provider::get_users_in_context($systemuserlist);
        $this->assertEqualsCanonicalizing([$userone->id, $usertwo->id], $systemuserlist->get_userids());
    }

    /**
     * Tests deleting data for an approved user list.
     *
     * @covers \tool_userautodelete\privacy\provider
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        $this->resetAfterTest();

        // Prepare three users with processes.
        $workflow = $this->get_userautodelete_generator()->create_simple_suspend_workflow(null, null, true);
        $userone = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $usertwo = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $userthree = $this->getDataGenerator()->create_user(['suspended' => 1]);

        foreach ([$userone->id, $usertwo->id, $userthree->id] as $userid) {
            process::create((int) $userid, $workflow);
        }

        // Try deletion with wrong context.
        provider::delete_data_for_users(new approved_userlist(
            \context_user::instance($userone->id),
            'tool_userautodelete',
            [$userone->id]
        ));
        $this->assertSame(3, $DB->count_records(db_table::USER_PROCESS->value));

        // Try deletion with correct context but without approced users.
        provider::delete_data_for_users(new approved_userlist(
            \context_system::instance(),
            'tool_userautodelete',
            []
        ));
        $this->assertSame(3, $DB->count_records(db_table::USER_PROCESS->value));

        // Delete two users from the correct context.
        provider::delete_data_for_users(new approved_userlist(
            \context_system::instance(),
            'tool_userautodelete',
            [$userone->id, $userthree->id]
        ));

        $remaininguserids = $DB->get_fieldset(db_table::USER_PROCESS->value, 'userid', []);
        $this->assertEqualsCanonicalizing([$usertwo->id], $remaininguserids);
    }
}
