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
 * End-to-end scenario tests for tool_userautodelete.
 *
 * These tests exercise the full production execution path
 * (manager -> workflow -> step -> filter/action) with real users that have
 * different lastaccess times and cover every relevant boundary condition and
 * lifecycle phase including cleanup of timed-out processes.
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\process_state;


/**
 * End-to-end scenario tests covering the full lastaccess-driven user lifecycle
 * across multiple users and multiple manager/cleanup runs.
 */
final class endtoend_scenario_test extends \advanced_testcase {
    /**
     * Runs before every test: enables the plugin and silences the logger so
     * that mtrace() calls do not produce unexpected output warnings.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        logger::disable();
        set_config('enable', true, 'tool_userautodelete');
    }

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
     * Returns the single active process for a user, or null if none exists.
     *
     * @param \stdClass $user User object.
     * @return process|null Active process or null.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function get_active_process(\stdClass $user): ?process {
        $procs = process::get_user_processes((int) $user->id);
        return !empty($procs) ? reset($procs) : null;
    }

    /**
     * Backdates the timemodified field of a process record to simulate
     * time passing without a manager run.
     *
     * @param process $proc Process to backdate.
     * @param int $secondsago How many seconds in the past the record should appear.
     * @return void
     * @throws \dml_exception
     */
    private function backdate_process(process $proc, int $secondsago): void {
        global $DB;
        $DB->update_record(db_table::USER_PROCESS->value, [
            'id'           => $proc->id,
            'timemodified' => time() - $secondsago,
        ]);
    }

    /**
     * Tests that after the first manager run only users whose lastaccess exceeds
     * the threshold are ingested, while recently-active and borderline users are
     * left completely untouched.
     *
     * @coversNothing
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_ingestion_respects_lastaccess_threshold(): void {
        global $DB;

        $this->resetAfterTest();

        $scenario = $this->get_generator()->create_lastaccess_scenario();
        $workflow  = $scenario['workflow'];
        $step1     = $workflow->steps[0];

        (new manager())->execute();

        // Users that MUST be ingested.
        foreach (['stale', 'very_stale', 'never_accessed'] as $key) {
            $user = $scenario['users'][$key];
            $proc = $this->get_active_process($user);

            $this->assertNotNull($proc, "User '{$key}' should have an active process after ingestion.");
            $this->assertSame(process_state::ACTIVE, $proc->state, "Process for '{$key}' must be ACTIVE.");
            $this->assertSame($step1->id, $proc->stepid, "Process for '{$key}' must reside in step 1.");
            $this->assertSame(
                $workflow->id,
                $proc->workflowid,
                "Process for '{$key}' must belong to the lastaccess lifecycle workflow."
            );

            // Step-1 action is 'suspend': account must now be suspended.
            $reloaded = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
            $this->assertTrue(
                (bool) $reloaded->suspended,
                "User '{$key}' must be suspended after step-1 action."
            );
            $this->assertFalse(
                (bool) $reloaded->deleted,
                "User '{$key}' must not be deleted after run 1."
            );
        }

        // Users that must NOT be ingested.
        foreach (['recent', 'borderline', 'fresh'] as $key) {
            $user = $scenario['users'][$key];

            $this->assertNull(
                $this->get_active_process($user),
                "User '{$key}' must not have a process (lastaccess is within threshold)."
            );

            $reloaded = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
            $this->assertFalse(
                (bool) $reloaded->suspended,
                "User '{$key}' must not be suspended."
            );
            $this->assertFalse(
                (bool) $reloaded->deleted,
                "User '{$key}' must not be deleted."
            );
        }
    }

    /**
     * Tests the exact ingestion boundary: users whose lastaccess is safely inside
     * the threshold are not ingested, while a user who is clearly past the
     * threshold is ingested on the same run.
     *
     * A 10-second buffer is used instead of a 1-second gap to prevent flaky
     * failures caused by the filter evaluating time() a few seconds after $now
     * was captured in the test.
     *
     * @coversNothing
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_ingestion_boundary_is_exact(): void {
        $this->resetAfterTest();

        $now = time();
        $generator = $this->get_generator();

        // Workflow with a 1-hour threshold to keep timestamps manageable.
        $workflow = $generator->create_lastaccess_lifecycle_workflow(
            title: 'Boundary Test Workflow',
            lastaccessthresholdsec: HOURSECS,
        );

        // Ten seconds inside the threshold: must NOT be ingested.
        $userinside = $this->getDataGenerator()->create_user(['lastaccess' => $now - HOURSECS + 10]);
        // Ten seconds past the threshold: MUST be ingested.
        $useroutside = $this->getDataGenerator()->create_user(['lastaccess' => $now - HOURSECS - 10]);

        (new manager())->execute();

        $this->assertNull(
            $this->get_active_process($userinside),
            'User inside the threshold must not be ingested.'
        );
        $this->assertNotNull(
            $this->get_active_process($useroutside),
            'User past the threshold must be ingested.'
        );
    }

    /**
     * Tests the complete two-step lifecycle from ingestion to deletion.
     *
     * @coversNothing
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_full_lifecycle_suspend_then_delete(): void {
        global $DB;

        $this->resetAfterTest();
        $manager = new manager();

        $scenario = $this->get_generator()->create_lastaccess_scenario();
        $workflow = $scenario['workflow'];
        $step1 = $workflow->steps[0];
        $step2 = $workflow->steps[1];
        $delaysec = $step1->timeoutsec; // Equals step-2 delay filter value.

        // Run 1: stale users are ingested into step 1 and suspended.
        $manager->execute();

        $trackedusers = [
            'stale'          => $scenario['users']['stale'],
            'very_stale'     => $scenario['users']['very_stale'],
            'never_accessed' => $scenario['users']['never_accessed'],
        ];

        foreach ($trackedusers as $key => $user) {
            $proc = $this->get_active_process($user);
            $this->assertNotNull($proc, "After run 1: '{$key}' must have a process in step 1.");
            $this->assertSame($step1->id, $proc->stepid, "After run 1: '{$key}' must be in step 1.");

            // Backdate the process to satisfy the step-2 delay filter.
            $this->backdate_process($proc, $delaysec + YEARSECS);
        }

        // Run 2: processes transition to step 2 and users are deleted (FINISHED).
        $manager->execute();

        foreach ($trackedusers as $key => $user) {
            // Process must be FINISHED – no active process left.
            $this->assertNull(
                $this->get_active_process($user),
                "After run 2: '{$key}' must not have an active process."
            );

            $allprocs = process::get_user_processes(
                (int) $user->id,
                includefinished: true,
                includeaborted: false
            );
            $this->assertCount(1, $allprocs, "After run 2: '{$key}' must have exactly one process record.");
            $finished = reset($allprocs);
            $this->assertSame(
                process_state::FINISHED,
                $finished->state,
                "After run 2: process for '{$key}' must be FINISHED."
            );
            $this->assertSame(
                $step2->id,
                $finished->stepid,
                "After run 2: finished process for '{$key}' must reference step 2."
            );

            // Step-2 action is 'delete': account must be marked as deleted.
            $record = $DB->get_record('user', ['id' => $user->id], 'id, deleted', MUST_EXIST);
            $this->assertTrue(
                (bool) $record->deleted,
                "After run 2: '{$key}' must be deleted (deleted = 1)."
            );
        }
    }

    /**
     * Tests that a user who becomes active again after being ingested into step 1
     * has their process cleaned up by the cleanup task once it times out.
     *
     * @coversNothing
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_cleanup_aborts_process_of_returned_user(): void {
        global $DB;

        $this->resetAfterTest();
        $manager = new manager();

        $scenario = $this->get_generator()->create_lastaccess_scenario();
        $workflow = $scenario['workflow'];
        $step1 = $workflow->steps[0];
        $user = $scenario['users']['returned'];

        // Step 1: ingest the user (their lastaccess is stale).
        $manager->execute();

        $proc = $this->get_active_process($user);
        $this->assertNotNull($proc, 'Returned user must be ingested into step 1.');
        $this->assertSame($step1->id, $proc->stepid, 'Returned user must be in step 1.');

        // Step 2: simulate the user logging back in.
        $DB->update_record('user', ['id' => $user->id, 'lastaccess' => time()]);

        // Step 3: backdate the process beyond step-1's timeout.
        $this->backdate_process($proc, $step1->timeoutsec + HOURSECS);

        // Step 4: run cleanup – the timed-out process must be aborted.
        $manager->cleanup();

        $abortedproc = process::get_by_id($proc->id);
        $this->assertSame(
            process_state::ABORTED,
            $abortedproc->state,
            'Cleanup must abort the timed-out process of the returned user.'
        );

        // Step 5: run manager again – lastaccess is fresh, so the filter no
        // longer matches and no new process must be created.
        $manager->execute();

        $this->assertNull(
            $this->get_active_process($user),
            'Returned user must not be re-ingested after updating their lastaccess to now.'
        );
    }

    /**
     * Tests that the lastaccess filter uses timecreated as a fallback for users
     * who have never logged in (lastaccess = 0).
     *
     * A never-logged-in user whose account was created long ago (past the
     * threshold) must be ingested; one whose account was created recently must
     * not.
     *
     * @coversNothing
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_never_logged_in_user_uses_timecreated_as_fallback(): void {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->get_generator();

        // Workflow with a 1-hour threshold to keep timestamps manageable.
        $generator->create_lastaccess_lifecycle_workflow(
            title: 'Fallback timecreated workflow',
            lastaccessthresholdsec: HOURSECS,
        );

        // Never logged in, registered 2 hours ago: timecreated exceeds threshold, must be ingested.
        $userold = $this->getDataGenerator()->create_user(['lastaccess' => 0]);
        $DB->update_record('user', ['id' => $userold->id, 'timecreated' => time() - 2 * HOURSECS]);

        // Never logged in, registered 30 minutes ago: timecreated within threshold, must NOT be ingested.
        $usernew = $this->getDataGenerator()->create_user(['lastaccess' => 0]);
        $DB->update_record('user', ['id' => $usernew->id, 'timecreated' => time() - MINSECS * 30]);

        (new manager())->execute();

        $this->assertNotNull(
            $this->get_active_process($userold),
            'Never-logged-in user with old timecreated must be ingested.'
        );
        $this->assertNull(
            $this->get_active_process($usernew),
            'Never-logged-in user with recent timecreated must not be ingested.'
        );
    }

    /**
     * Tests incremental ingestion: users with progressively older lastaccess
     * times are only ingested when the configured threshold is met.
     *
     * Two concurrent workflows with different thresholds model the situation
     * where different user populations are processed at different lifecycle
     * stages. Since workflows are processed in sort order and a user can only
     * be in one active process at a time, the first matching workflow wins.
     *
     * @coversNothing
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_users_ingested_progressively_by_lastaccess(): void {
        $this->resetAfterTest();

        $generator = $this->get_generator();
        $now = time();

        // Workflow A: 1-year threshold (processed first).
        $wf1yr = $generator->create_lastaccess_lifecycle_workflow(
            title: 'Workflow A – 1 year',
            lastaccessthresholdsec: YEARSECS,
        );

        // Workflow B: 2-year threshold (processed second).
        $wf2yr = $generator->create_lastaccess_lifecycle_workflow(
            title: 'Workflow B – 2 years',
            lastaccessthresholdsec: 2 * YEARSECS,
        );

        // 1.5-year inactive user: over 1-year threshold, under 2-year threshold.
        $user15yr = $this->getDataGenerator()->create_user(['lastaccess' => $now - intval(YEARSECS * 1.5)]);

        // 3-year inactive user: over both thresholds; wf1yr claims them first.
        $user3yr  = $this->getDataGenerator()->create_user(['lastaccess' => $now - 3 * YEARSECS]);

        // 6-month inactive user: under both thresholds; must not be ingested.
        $user6mo  = $this->getDataGenerator()->create_user(['lastaccess' => $now - intval(YEARSECS * 0.5)]);

        (new manager())->execute();

        // User15yr: matched by wf1yr (1.5yr > 1yr), must land in wf1yr.
        $proc15yr = $this->get_active_process($user15yr);
        $this->assertNotNull($proc15yr, 'user15yr must be ingested (1.5yr > 1yr threshold).');
        $this->assertSame(
            $wf1yr->id,
            $proc15yr->workflowid,
            'user15yr must belong to the 1-year workflow (first in sort order).'
        );

        // User3yr: matched by both, but wf1yr runs first and claims them.
        $proc3yr = $this->get_active_process($user3yr);
        $this->assertNotNull($proc3yr, 'user3yr must be ingested.');
        $this->assertSame(
            $wf1yr->id,
            $proc3yr->workflowid,
            'user3yr must belong to the 1-year workflow (first-workflow-wins).'
        );

        // User6mo: under both thresholds.
        $this->assertNull(
            $this->get_active_process($user6mo),
            'user6mo must not be ingested (6 months < any threshold).'
        );
    }

    /**
     * Tests that the site administrator and the guest user are always excluded
     * from workflow processing, regardless of their lastaccess time.
     *
     * @coversNothing
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_admin_and_guest_are_never_ingested(): void {
        global $CFG, $DB;

        $this->resetAfterTest();

        // Workflow that would match every user with lastaccess = 0.
        $this->get_generator()->create_lastaccess_lifecycle_workflow(
            title: 'Admin-exclusion test',
            lastaccessthresholdsec: YEARSECS,
        );

        // Force admin and guest lastaccess to 0 so they trivially match the filter.
        $adminids = array_map('intval', explode(',', $CFG->siteadmins));
        foreach ($adminids as $adminid) {
            $DB->update_record('user', ['id' => $adminid, 'lastaccess' => 0]);
        }
        $DB->update_record('user', ['id' => (int) $CFG->siteguest, 'lastaccess' => 0]);

        (new manager())->execute();

        foreach ($adminids as $adminid) {
            $procs = process::get_user_processes($adminid, includefinished: true, includeaborted: true);
            $this->assertEmpty($procs, "Admin user (id={$adminid}) must never receive a process record.");
        }

        $guestprocs = process::get_user_processes(
            (int) $CFG->siteguest,
            includefinished: true,
            includeaborted: true
        );
        $this->assertEmpty($guestprocs, 'Guest user must never receive a process record.');
    }

    /**
     * Tests that deactivating a workflow immediately aborts all open processes
     * and that re-activating and re-running the manager re-ingests eligible users.
     *
     * @coversNothing
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_workflow_deactivation_aborts_and_reactivation_reingests(): void {
        $this->resetAfterTest();

        $scenario = $this->get_generator()->create_lastaccess_scenario();
        $workflow  = $scenario['workflow'];

        $manager = new manager();

        // Ingest stale users.
        $manager->execute();

        $stale = $scenario['users']['stale'];
        $proc  = $this->get_active_process($stale);
        $this->assertNotNull($proc, 'User stale must be ingested before deactivation.');

        // Deactivate – must abort all active processes.
        $workflow->deactivate();

        $aborted = process::get_by_id($proc->id);
        $this->assertSame(
            process_state::ABORTED,
            $aborted->state,
            'Deactivating the workflow must abort all open processes.'
        );

        // Re-activate and run again.
        // Users are now suspended (step-1 action from run 1) but their
        // lastaccess is still stale, so they must be re-ingested.
        $workflow->activate();
        $manager->execute();

        $reproc = $this->get_active_process($stale);
        $this->assertNotNull($reproc, 'Stale user must be re-ingested after workflow re-activation.');
        $this->assertSame(
            $workflow->id,
            $reproc->workflowid,
            'Re-ingested process must belong to the same workflow.'
        );
    }
}
