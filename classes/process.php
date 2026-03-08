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
 * This file defines the user process class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\process_state;
use tool_userautodelete\local\type\sort_move_direction;
use tool_userautodelete\local\type\userfilter_clause;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * A process that represents the state of a single user within a given workflow
 *
 * Each user can only have a single process within a workflow at any given time.
 * The process tracks which step the user is currently in and holds other
 * metadata.
 *
 * @property-read int $id ID of this user process
 * @property-read int $userid ID of the user this process if for
 * @property-read int $workflowid ID of the workflow this process belongs to
 * @property-read int $stepid ID of the step this process currently is in
 * @property-read process_state $state State this process currently is in
 * @property-read int $timecreated Time this process entered the workflow for the first time
 * @property-read int $timemodified Time this process was last updated
 */
class process {
    /**
     * Internal constructor to create an actual user process object. Used only
     * by the public static factory methods.
     *
     * @param int $id ID of this user process
     * @param int $userid ID of the user this process if for
     * @param int $workflowid ID of the workflow this process belongs to
     * @param int $stepid ID of the step this process currently is in
     * @param process_state $state State this process currently is in
     * @param int $timecreated Time this process entered the workflow for the first time
     * @param int $timemodified Time this process was last updated
     */
    protected function __construct(
        /** @var int $id ID of this user process */
        protected readonly int $id,
        /** @var int $userid ID of the user this process if for */
        protected readonly int $userid,
        /** @var int $workflowid ID of the workflow this process belongs to */
        protected readonly int $workflowid,
        /** @var int $stepid ID of the step this process currently is in */
        protected int $stepid,
        /** @var process_state $state State this process currently is in */
        protected process_state $state,
        /** @var int $timecreated Time this process entered the workflow for the first time */
        protected readonly int $timecreated,
        /** @var int $timemodified Time this process was last updated */
        protected int $timemodified
    ) {
    }

    /**
     * Allows read-only access to object properties
     *
     * @param string $name Name of the property to access
     * @return mixed Value of the requested property
     * @throws \coding_exception
     */
    public function __get(string $name): mixed {
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        throw new \coding_exception('Invalid property: ' . $name);
    }

    /**
     * Retrieves a user process by its ID.
     *
     * @param int $processid The ID of the process to retrieve
     * @return process The user process object
     * @throws \dml_exception
     */
    public static function get_by_id(int $processid): process {
        global $DB;

        $record = $DB->get_record_sql(
            'SELECT proc.*, step.workflowid ' .
            'FROM {' . db_table::USER_PROCESS->value . '} proc ' .
            'JOIN {' . db_table::WORKFLOW_STEP->value . '} step ON proc.stepid = step.id ' .
            'WHERE proc.id = :processid',
            ['processid' => $processid],
            MUST_EXIST
        );

        return new process(
            id: $record->id,
            userid: $record->userid,
            workflowid: $record->workflowid,
            stepid: $record->stepid,
            state: process_state::from($record->state),
            timecreated: $record->timecreated,
            timemodified: $record->timemodified
        );
    }

    /**
     * Returns all processes for a given user.
     *
     * By default, only active processes are returned. Finished and aborted
     * processes can be included by setting the respective parameters to true.
     *
     * @param int $userid The ID of the user to retrieve processes for
     * @param bool $includefinished Whether to include finished processes as well
     * @param bool $includeaborted Whether to include aborted processes as well
     * @return array An associative array of user processes, indexed by process ID
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function get_user_processes(
        int $userid,
        bool $includefinished = false,
        bool $includeaborted = false
    ): array {
        global $DB;

        // Fetch all process records for the given user.
        [$insql, $inparams] = $DB->get_in_or_equal(
            items: array_merge(
                [process_state::ACTIVE->value],
                $includefinished ? [process_state::FINISHED->value] : [],
                $includeaborted ? [process_state::ABORTED->value] : []
            ),
            type: SQL_PARAMS_NAMED,
            prefix: 'state'
        );

        $records = $DB->get_records_sql(
            'SELECT proc.*, step.workflowid ' .
            'FROM {' . db_table::USER_PROCESS->value . '} proc ' .
            'JOIN {' . db_table::WORKFLOW_STEP->value . '} step ON proc.stepid = step.id ' .
            'WHERE proc.userid = :userid AND proc.state ' . $insql,
            array_merge(['userid' => $userid], $inparams),
            IGNORE_MISSING
        );

        // Build process objects as an associative array, indexed by process ID.
        $userprocesses = [];
        foreach ($records as $record) {
            $userprocesses[$record->id] = new process(
                id: $record->id,
                userid: $record->userid,
                workflowid: $record->workflowid,
                stepid: $record->stepid,
                state: process_state::from($record->state),
                timecreated: $record->timecreated,
                timemodified: $record->timemodified
            );
        }

        return $userprocesses;
    }

    /**
     * Calculates number of total, active, finished, and aborted processes
     * within each step of a given workflow
     *
     * @param int $workflowid ID of the workflow to retrieve process stats for
     * @param bool $indexbystepid If true, the returned array will be associative
     * and indexed by step ID.
     * @return array An array of objects containing step ID and corresponding
     * process stats. Steps are ordered by their sort order within the workflow.
     * @throws \dml_exception
     */
    public static function get_process_stats_for_workflow(int $workflowid, bool $indexbystepid = false): array {
        global $DB;

        $stepstats = $DB->get_records_sql(
            'SELECT ' .
            '    s.id AS stepid, ' .
            '    COUNT(p.id) AS total, ' .
            '    SUM(CASE WHEN p.state = :stateactive   THEN 1 ELSE 0 END) AS active, ' .
            '    SUM(CASE WHEN p.state = :statefinished THEN 1 ELSE 0 END) AS finished, ' .
            '    SUM(CASE WHEN p.state = :stateaborted  THEN 1 ELSE 0 END) AS aborted ' .
            'FROM {' . db_table::WORKFLOW_STEP->value . '} s ' .
            '    JOIN {' . db_table::WORKFLOW->value . '} w ON s.workflowid = w.id ' .
            '    LEFT JOIN {' . db_table::USER_PROCESS->value . '} p ON s.id = p.stepid ' .
            'WHERE w.id = :workflowid ' .
            'GROUP BY (s.id) ' .
            'ORDER BY s.sort ASC',
            [
                'workflowid' => $workflowid,
                'stateactive' => process_state::ACTIVE->value,
                'statefinished' => process_state::FINISHED->value,
                'stateaborted' => process_state::ABORTED->value,
            ],
            IGNORE_MISSING
        );

        $res = [];
        foreach ($stepstats as $stepstat) {
            $res[] = (object) [
                'stepid' => $stepstat->stepid,
                'total' => $stepstat->total,
                'finished' => $stepstat->finished,
                'active' => $stepstat->active,
                'aborted' => $stepstat->aborted,
            ];
        }

        if ($indexbystepid) {
            $res = array_combine(
                array_map(fn($item) => $item->stepid, $res),
                $res
            );
        }

        return $res;
    }

    /**
     * Returns all active (not finished or aborted) processes for a given step.
     *
     * @param step $step The step to retrieve active processes for
     * @param bool $transitionableonly Whether to include only processes that
     * are ready to transition based on the step's filter criteria
     * @return process[] An associative array of user processes, indexed by process ID
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_active_processes_for_step(
        step $step,
        bool $transitionableonly = false
    ): array {
        global $DB;

        if ($transitionableonly) {
            $userfilterclause = $step->generate_user_filter_clause();

            // Fetch all active process records that match the given user filter
            // clause for the given step.
            $records = $DB->get_records_sql(
                'SELECT proc.*, step.workflowid ' .
                'FROM {' . db_table::USER_PROCESS->value . '} proc ' .
                'JOIN {' . db_table::WORKFLOW_STEP->value . '} step ON proc.stepid = step.id ' .
                'JOIN {user} u ON proc.userid = u.id ' .
                'WHERE proc.stepid = :stepid ' .
                'AND proc.state = :stateactive ' .
                $userfilterclause->sql,
                array_merge(
                    ['stepid' => $step->id, 'stateactive' => process_state::ACTIVE->value],
                    $userfilterclause->params
                ),
                IGNORE_MISSING
            );
        } else {
            // Fetch all active process records for the given step.
            $records = $DB->get_records_sql(
                'SELECT proc.*, step.workflowid ' .
                'FROM {' . db_table::USER_PROCESS->value . '} proc ' .
                'JOIN {' . db_table::WORKFLOW_STEP->value . '} step ON proc.stepid = step.id ' .
                'WHERE proc.stepid = :stepid ' .
                'AND proc.state = :stateactive',
                ['stepid' => $step->id, 'stateactive' => process_state::ACTIVE->value],
                IGNORE_MISSING
            );
        }

        // Build process objects as an associative array, indexed by process ID.
        $userprocesses = [];
        foreach ($records as $record) {
            $userprocesses[$record->id] = new process(
                id: $record->id,
                userid: $record->userid,
                workflowid: $record->workflowid,
                stepid: $record->stepid,
                state: process_state::from($record->state),
                timecreated: $record->timecreated,
                timemodified: $record->timemodified
            );
        }

        return $userprocesses;
    }

    /**
     * Creates a new user process in the given workflow and executes the step
     * action during creation
     *
     * If no specific initial step is given, the first step of the workflow will
     * be used automatically.
     *
     * @param int $userid ID of the user to create the process for
     * @param workflow $workflow The workflow to create the process in
     * @param step|null $initialstep Optional initial step to start the process in
     * @return process The newly created user process
     * @throws \dml_exception
     * @throws \dml_transaction_exception
     * @throws \moodle_exception
     */
    public static function create(int $userid, workflow $workflow, ?step $initialstep = null): process {
        global $DB;

        // Determine and validate initial step if not given.
        if (!$initialstep) {
            $initialstep = $workflow->steps[0] ?? null;
        }

        if (!$initialstep || $initialstep->workflow->id !== $workflow->id) {
            throw new \moodle_exception('invalid_initial_workflow_step', 'tool_userautodelete');
        }

        // Ensure that the target workflow is active.
        if (!$workflow->active) {
            throw new \moodle_exception('workflow_inactive', 'tool_userautodelete');
        }

        // Initialize user process.
        try {
            $transaction = $DB->start_delegated_transaction();

            // Ensure that the user is not already part of a workflow.
            if (!empty(self::get_user_processes($userid))) {
                throw new \moodle_exception('user_already_in_workflow', 'tool_userautodelete');
            }

            // Create process.
            $now = time();
            $processid = $DB->insert_record(db_table::USER_PROCESS->value, [
                'userid' => $userid,
                'stepid' => $initialstep->id,
                'state' => process_state::ACTIVE->value,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
            $process = new process(
                id: $processid,
                userid: $userid,
                workflowid: $workflow->id,
                stepid: $initialstep->id,
                state: process_state::ACTIVE,
                timecreated: $now,
                timemodified: $now
            );

            // Execute initial step actions.
            foreach ($initialstep->actions as $action) {
                if (!$action->execute($process)) {
                    throw new \moodle_exception(
                        'userdeleteaction_execution_failed',
                        'tool_userautodelete',
                        '',
                        "{$action->get_plugin_name()} #{$action->id}"
                    );
                }
            }

            // Commit everything if all the above went well.
            $transaction->allow_commit();
        } catch (\Exception $e) {
            $transaction->rollback($e);
        }

        return $process;
    }

    /**
     * Aborts this user process.
     *
     * @return void
     * @throws \dml_exception
     */
    public function abort(): void {
        global $DB;

        $now = time();
        $DB->update_record(db_table::USER_PROCESS->value, [
            'id' => $this->id,
            'state' => process_state::ABORTED->value,
            'timemodified' => $now,
        ]);
        $this->state = process_state::ABORTED;
        $this->timemodified = $now;
    }

    /**
     * Performs a transition from the current step of this process to another
     * step.
     *
     * If no target step is given, the next step in the workflow sequence
     * will be used automatically.
     *
     * @param step|null $targetstep The target step to transition to
     * @return void
     * @throws \dml_transaction_exception
     * @throws \moodle_exception
     */
    public function transition(?step $targetstep = null): void {
        global $DB;

        // Do not process finished processes.
        if ($this->state != process_state::ACTIVE) {
            throw new \moodle_exception('process_already_terminated', 'tool_userautodelete');
        }

        try {
            $transaction = $DB->start_delegated_transaction();

            // Determine and validate next step if not given.
            if (!$targetstep) {
                $targetstep = step::get_by_id($this->stepid)->next();
            }

            if (!$targetstep) {
                throw new \moodle_exception('process_unfinished_no_next_step', 'tool_userautodelete');
            }

            // Step must belong to the same workflow as the current step.
            if ($this->workflowid !== $targetstep->workflow->id) {
                throw new \moodle_exception('invalid_target_workflow_step', 'tool_userautodelete');
            }

            // Only process active workflows.
            if (!$targetstep->workflow->active) {
                throw new \moodle_exception('workflow_inactive', 'tool_userautodelete');
            }

            // Update process record and internal representation.
            $now = time();
            $DB->update_record(db_table::USER_PROCESS->value, [
                'id' => $this->id,
                'stepid' => $targetstep->id,
                'timemodified' => $now,
            ]);
            $this->stepid = $targetstep->id;
            $this->timemodified = $now;

            // Execute target step actions.
            foreach ($targetstep->actions as $action) {
                if (!$action->execute($this)) {
                    throw new \moodle_exception(
                        'userdeleteaction_execution_failed',
                        'tool_userautodelete',
                        '',
                        "{$action->get_plugin_name()} #{$action->id}"
                    );
                }
            }

            // Mark step as finished if the target step is the last step of the workflow.
            if (!$targetstep->is_final()) {
                $DB->update_record(db_table::USER_PROCESS->value, [
                    'id' => $this->id,
                    'state' => process_state::FINISHED->value,
                    'timemodified' => $now,
                ]);
                $this->state = process_state::FINISHED;
            }

            // Commit everything if all the above went well.
            $transaction->allow_commit();
        } catch (\Exception $e) {
            $transaction->rollback($e);
        }
    }
}
