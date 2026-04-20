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

use tool_userautodelete\step;
use tool_userautodelete\userdeleteaction;
use tool_userautodelete\userdeletefilter;
use tool_userautodelete\workflow;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Tests generator for the tool_userautodelete plugin
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_userautodelete_generator extends \testing_data_generator {
    /**
     * Prepares request and page globals for form instantiation.
     *
     * @param string $path Page path for the form
     * @param array $params Request parameters
     * @return void
     * @throws \coding_exception
     * @throws \moodle_exception
     * @throws \dml_exception
     */
    public function prepare_form_environment(string $path, array $params): void {
        global $PAGE;

        $_GET = $params;
        $_POST = [];
        $_REQUEST = $params;

        $PAGE->set_context(\context_system::instance());
        $PAGE->set_url(new \moodle_url($path, $params));
    }

    /**
     * Creates a new workflow and loads the default workflow steps
     *
     * @param string|null $title Custom title for the workflow
     * @param string|null $description Custom description for the workflow
     * @param bool $active If true, the workflow will be active after creation
     * @return workflow
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function create_default_workflow(
        ?string $title = null,
        ?string $description = null,
        bool $active = true
    ): workflow {
        $workflow = workflow::create(
            $title ?? 'Default Workflow',
            $description ?? 'This is the default workflow created by the generator.',
        );
        $workflow->load_default_workflow();

        if ($active) {
            $workflow->activate();
        }

        return $workflow;
    }

    /**
     * Creates a new workflow with a single step containing a suspension state
     * filter and an unsuspend action used for unit tests.
     *
     * @param string|null $title Custom title for the workflow
     * @param string|null $description Custom description for the workflow
     * @param bool $active If true, the workflow will be active after creation
     * @return workflow
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function create_simple_suspend_workflow(
        ?string $title,
        ?string $description,
        bool $active = true
    ): workflow {
        $workflow = workflow::create(
            $title ?? 'Suspend Test Workflow',
            $description ?? 'Simple workflow used for unit tests.',
        );
        $step = step::create(workflow: $workflow, title: 'Step 1', description: '');
        userdeletefilter::create_instance($step, 'suspension', ['suspended' => true]);
        userdeleteaction::create_instance($step, 'unsuspend');

        if ($active) {
            $workflow->activate();
        }

        return $workflow;
    }

    /**
     * Creates a new workflow with two steps where the first step unsuspends a
     * previously suspended user and the second step re-suspends the previously
     * unsuspended user.
     *
     * @param string|null $title Custom title for the workflow
     * @param string|null $description Custom title for the workflow
     * @param bool $active If true, the workflow will be active after creation
     * @return workflow
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function create_multistep_suspend_workflow(
        ?string $title,
        ?string $description,
        bool $active = true
    ): workflow {
        $workflow = $this->create_simple_suspend_workflow($title, $description, $active);

        $step = step::create(workflow: $workflow, title: 'Step 2', description: '');
        userdeletefilter::create_instance($step, 'suspension', ['suspended' => false]);
        userdeleteaction::create_instance($step, 'suspend');

        return $workflow;
    }

    /**
     * Creates a new workflow with two steps where the first step unsuspends a
     * previously suspended user and the second step deletes the user.
     *
     * @param string|null $title Custom title for the workflow
     * @param string|null $description Custom title for the workflow
     * @param bool $active If true, the workflow will be active after creation
     * @return workflow
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function create_multistep_suspend_delete_workflow(
        ?string $title,
        ?string $description,
        bool $active = true
    ): workflow {
        $workflow = $this->create_simple_suspend_workflow($title, $description, $active);

        $step = step::create(workflow: $workflow, title: 'Step 2', description: '');
        userdeletefilter::create_instance($step, 'suspension', ['suspended' => false]);
        userdeleteaction::create_instance($step, 'delete');

        return $workflow;
    }

    /**
     * Creates a new workflow with a single empty step and returns both objects.
     *
     * This is a lightweight fixture helper used by base test cases that need a
     * step to attach sub-plugin instances to without any pre-configured filters
     * or actions.
     *
     * @param string|null $workflowtitle Optional workflow title (defaults to 'Test Workflow')
     * @param string|null $steptitle     Optional step title (defaults to 'Step 1')
     * @return array{0: workflow, 1: step} Tuple of [$workflow, $step]
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function create_workflow_with_empty_step(
        ?string $workflowtitle = null,
        ?string $steptitle = null
    ): array {
        $workflow = workflow::create($workflowtitle ?? 'Test Workflow', '');
        $step = step::create(workflow: $workflow, title: $steptitle ?? 'Step 1', description: '');
        return [$workflow, $step];
    }

    /**
     * Creates a two-step lastaccess lifecycle workflow.
     *
     * Step 1 (ingestion): Matches users whose last access is older than
     * $lastaccessthresholdsec seconds. On entry the user is suspended.
     * The step timeout equals the delay configured in step 2 so that the
     * cleanup task can abort stale processes automatically.
     *
     * Step 2 (deletion): Matches users who have been in step 1 for at least
     * $delaysec seconds (via the delay filter). On entry the user is deleted.
     *
     * @param string|null $title Optional title for the workflow.
     * @param int $lastaccessthresholdsec Seconds of inactivity before a user is considered stale (default: YEARSECS).
     * @param int $delaysec Seconds a user must wait in step 1 before being deleted (default: 30 days).
     * @param bool $active Whether to activate the workflow after creation.
     * @return workflow The newly created workflow object.
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function create_lastaccess_lifecycle_workflow(
        ?string $title = null,
        int $lastaccessthresholdsec = YEARSECS,
        int $delaysec = DAYSECS * 30,
        bool $active = true
    ): workflow {
        $workflow = workflow::create(
            $title ?? 'Lastaccess Lifecycle Workflow',
            'Suspends stale users and deletes them after a delay.'
        );

        // Step 1: suspend users who have not logged in for a long time.
        $step1 = step::create(workflow: $workflow, title: 'Step 1: Suspend stale users', description: '');
        userdeletefilter::create_instance($step1, 'lastaccess', ['thresholdsec' => $lastaccessthresholdsec]);
        userdeleteaction::create_instance($step1, 'suspend');

        // Step 2: delete after delay.
        $step2 = step::create(workflow: $workflow, title: 'Step 2: Delete after delay', description: '');
        userdeletefilter::create_instance($step2, 'delay', ['delaysec' => $delaysec]);
        userdeleteaction::create_instance($step2, 'delete');

        if ($active) {
            $workflow->activate();
        }

        return $workflow;
    }

    /**
     * Creates a comprehensive lastaccess-based lifecycle scenario suitable for
     * end-to-end integration tests.
     *
     * The scenario provides a single two-step workflow (suspend → delete after
     * delay) together with six users whose last-access times cover every
     * relevant boundary condition:
     *
     * Workflows:
     *  - lastaccess_lifecycle (active): Step 1 ingests users inactive for
     *    more than YEARSECS and suspends them. Step 2 deletes them after a
     *    30-day delay (DAYSECS * 30). Step 1 timeout equals the step-2 delay.
     *
     * Users:
     *  - recent:         Last access = now. Never matched by the ingestion filter.
     *  - borderline:     Last access = YEARSECS - 10 seconds ago. Ten seconds
     *                    short of the threshold; must NOT be ingested.
     *  - stale:          Last access = YEARSECS + DAYSECS ago. Clearly over
     *                    the threshold; must be ingested in run 1.
     *  - very_stale:     Last access = 2 * YEARSECS ago. Must be ingested in
     *                    run 1 alongside 'stale'.
     *  - never_accessed: lastaccess = 0, timecreated = YEARSECS + DAYSECS ago.
     *                    The lastaccess filter falls back to timecreated for users
     *                    who have never logged in; this user exceeds the threshold
     *                    and must be ingested.
     *  - fresh:          lastaccess = 0, timecreated = now. A freshly registered
     *                    user who has never logged in; timecreated is within the
     *                    threshold so this user must NOT be ingested.
     *  - returned:       Last access = YEARSECS + DAYSECS ago at fixture
     *                    creation time (stale → ingested in run 1). Tests
     *                    simulate a user returning by updating their lastaccess
     *                    after ingestion, then verifying that the cleanup task
     *                    aborts the timed-out process.
     *
     * @return array{
     *     workflow: workflow,
     *     users: array{
     *         recent:         \stdClass,
     *         borderline:     \stdClass,
     *         stale:          \stdClass,
     *         very_stale:     \stdClass,
     *         never_accessed: \stdClass,
     *         fresh:          \stdClass,
     *         returned:       \stdClass,
     *     },
     * } Associative array containing a 'workflow' entry and a 'users' sub-array.
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function create_lastaccess_scenario(): array {
        $workflow = $this->create_lastaccess_lifecycle_workflow(active: true);

        $now = time();

        // Active user: last access right now, must never be ingested.
        $userrecent = $this->create_user(['lastaccess' => $now]);

        // Borderline user: one second short of the threshold, must NOT be ingested.
        $userborderline = $this->create_user(['lastaccess' => $now - YEARSECS + 1]);

        // Stale user: clearly over the threshold, must be ingested in run 1.
        $userstale = $this->create_user(['lastaccess' => $now - YEARSECS - DAYSECS]);

        // Very stale user: inactive for two full years, also ingested in run 1.
        $userverystale = $this->create_user(['lastaccess' => $now - 2 * YEARSECS]);

        // Never-accessed user: lastaccess = 0, timecreated far in the past.
        // The lastaccess filter falls back to timecreated for users who have
        // never logged in, so the account must be ingested because its
        // timecreated exceeds the YEARSECS threshold.
        $userneveraccessed = $this->create_user([
            'lastaccess'  => 0,
            'timecreated' => $now - YEARSECS - DAYSECS,
        ]);

        // Returned user: starts as stale so the fixture ingests them when
        // manager::execute() is first called. Tests then update lastaccess to
        // simulate the user logging back in and verify cleanup behaviour.
        $userreturned = $this->create_user(['lastaccess' => $now - YEARSECS - DAYSECS]);

        // Fresh user: never logged in and registered just now. The lastaccess
        // filter falls back to timecreated for users with lastaccess = 0; because
        // timecreated is within the threshold this user must NOT be ingested.
        $userfresh = $this->create_user(['lastaccess' => 0, 'timecreated' => $now]);

        return [
            'workflow' => $workflow,
            'users' => [
                'recent'         => $userrecent,
                'borderline'     => $userborderline,
                'stale'          => $userstale,
                'very_stale'     => $userverystale,
                'never_accessed' => $userneveraccessed,
                'fresh'          => $userfresh,
                'returned'       => $userreturned,
            ],
        ];
    }
}
