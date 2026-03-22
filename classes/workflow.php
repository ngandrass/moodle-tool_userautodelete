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
 * This file defines the workflow class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\sort_move_direction;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Workflows are a series of steps that users can go through. Each step is made
 * of a filer (transaction) and an action. The filter determines which users
 * are eligible to proceed enter the actual step and get the associated action
 * executed on them.
 *
 * @property-read int $id Internal ID of the workflow
 * @property-read string $title Title of the workflow
 * @property-read string|null $description Optional description of the workflow
 * @property-read step[] $steps Sorted array of steps that belong to this workflow
 * @property-read int $sort Sort index of this workflow in relation to other workflows
 * @property-read bool $active If true, the workflow will actively be processed
 * @property-read int $createdby ID of the user that created this workflow
 * @property-read int $modifiedby ID of the user that last modified this workflow
 * @property-read int $timecreated Unix timestamp when this workflow was created
 * @property-read int $timemodified Unix timestamp when this workflow was last modified
 */
class workflow {
    /** @var step[]|null Lazy-loaded sorted array of steps that belong to this workflow */
    protected ?array $steps;

    /**
     * Internal constructor to create the actual workflow object based on data
     * that was fetched by one of the static CRUD methods.
     *
     * @param int $id Internal ID of the workflow
     * @param string $title Title of the workflow
     * @param string|null $description Optional description of the workflow
     * @param int $sort Sort index of this workflow in relation to other workflows
     * @param bool $active If true, the workflow will actively be processed
     * @param int $createdby ID of the user that created this workflow
     * @param int $modifiedby ID of the user that last modified this workflow
     * @param int $timecreated Unix timestamp when this workflow was created
     * @param int $timemodified Unix timestamp when this workflow was last modified
     */
    protected function __construct(
        /** @var int $id Internal ID of the workflow */
        protected readonly int $id,
        /** @var string $title Title of the workflow */
        protected string $title,
        /** @var string|null $description Optional description of the workflow */
        protected ?string $description,
        /** @var int $sort Sort index of this workflow in relation to other workflows */
        protected int $sort,
        /** @var bool $active If true, the workflow will actively be processed */
        protected bool $active,
        /** @var int $createdby ID of the user that created this workflow */
        protected readonly int $createdby,
        /** @var int $modifiedby ID of the user that last modified this workflow */
        protected int $modifiedby,
        /** @var int $timecreated Unix timestamp when this workflow was created */
        protected readonly int $timecreated,
        /** @var int $timemodified Unix timestamp when this workflow was last modified */
        protected int $timemodified,
    ) {
        $this->steps = null;
    }

    /**
     * Allows read-only access to object properties
     *
     * @param string $name Name of the property to access
     * @return mixed Value of the requested property
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function __get(string $name): mixed {
        // Handle lazy-loaded properties.
        switch ($name) {
            case 'steps':
                return $this->get_steps();
        }

        // Handle regular properties.
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        throw new \coding_exception('Invalid property: ' . $name);
    }

    /**
     * Retrieves all steps that belong to this workflow, sorted by their sort
     * index.
     *
     * @return step[] Sorted array of steps that belong to this workflow
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function get_steps(): array {
        if ($this->steps === null) {
            $this->steps = step::get_all_workflow_steps($this);
        }

        return $this->steps;
    }

    /**
     * Retrieves all workflows from the database, sorted by their sort index.
     *
     * @return self[] Sorted array of all workflow objects
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_all(): array {
        global $DB;

        $records = $DB->get_records(db_table::WORKFLOW->value, null, 'sort ASC');

        $workflows = [];
        foreach ($records as $record) {
            $workflows[] = new self(
                id: $record->id,
                title: $record->title,
                description: $record->description,
                sort: $record->sort,
                active: $record->active == 1,
                createdby: $record->createdby,
                modifiedby: $record->modifiedby,
                timecreated: $record->timecreated,
                timemodified: $record->timemodified,
            );
        }

        return $workflows;
    }

    /**
     * Retrieves an existing workflow from the database, identified by its internal ID.
     *
     * @param int $workflowid Internal ID of the workflow to retrieve
     * @return self The workflow object
     * @throws \dml_exception
     */
    public static function get_by_id(int $workflowid): self {
        global $DB;

        $record = $DB->get_record(db_table::WORKFLOW->value, ['id' => $workflowid], '*', MUST_EXIST);

        return new self(
            id: $record->id,
            title: $record->title,
            description: $record->description,
            sort: $record->sort,
            active: $record->active == 1,
            createdby: $record->createdby,
            modifiedby: $record->modifiedby,
            timecreated: $record->timecreated,
            timemodified: $record->timemodified,
        );
    }

    /**
     * Creates a new workflow in the database and returns the corresponding
     * workflow object.
     *
     * Newly created workflows will be inactive by default.
     *
     * @param string $title Title of the new workflow
     * @param string|null $description Optional description of the new workflow
     * @return self
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function create(
        string $title,
        ?string $description
    ): self {
        global $DB, $USER;

        // Validate parameters.
        if (strlen($title) === 0) {
            throw new \moodle_exception('workflow_title_required', 'tool_userautodelete');
        }

        if ($description === '') {
            $description = null;
        }

        // Create the new workflow record.
        $now = time();
        $transaction = $DB->start_delegated_transaction();

        // Determine the next sort index.
        $lastsort = $DB->get_field(
            db_table::WORKFLOW->value,
            'MAX(sort)',
            []
        );

        // Create actual new workflow record.
        $id = $DB->insert_record(db_table::WORKFLOW->value, [
            'title' => $title,
            'description' => $description,
            'sort' => $lastsort ? $lastsort + 1 : 1,
            'active' => 0,
            'createdby' => $USER->id,
            'modifiedby' => $USER->id,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $transaction->allow_commit();

        return self::get_by_id($id);  // Yes, this is another database query. But it's easier to maintain ;).
    }

    /**
     * Deletes this workflow and all associated steps, filters and actions from
     * the database.
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function delete(): void {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        // Delete all steps (including filters and actions) of this workflow.
        foreach ($this->get_steps() as $step) {
            $step->delete();
        }

        // Delete the workflow itself.
        $DB->delete_records(db_table::WORKFLOW->value, ['id' => $this->id]);

        $transaction->allow_commit();
    }

    /**
     * Returns the total number of workflows in the database.
     *
     * @return int Total number of workflows
     * @throws \dml_exception
     */
    public static function get_workflow_count(): int {
        global $DB;

        return $DB->count_records(db_table::WORKFLOW->value);
    }

    /**
     * Returns the total number of steps in this workflow.
     *
     * @return int Total number of steps in this workflow
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function get_step_count(): int {
        return count($this->get_steps());
    }

    /**
     * Updates the last modified data values of this workflow to the current
     * user and timestamp.
     *
     * @return void
     * @throws \dml_exception
     */
    public function touch(): void {
        global $DB, $USER;

        // Invalidate lazy-loaded props.
        $this->steps = null;

        // Update database record.
        $now = time();
        $DB->update_record(db_table::WORKFLOW->value, [
            'id' => $this->id,
            'modifiedby' => $USER->id,
            'timemodified' => $now,
        ]);

        $this->modifiedby = $USER->id;
        $this->timemodified = $now;
    }

    /**
     * Detemines whether this workflow is valid and can be activated or not.
     *
     * A workflow is considered valid if it has at least one step and all of its
     * steps themselves are valid.
     *
     * @return bool True if this workflow is valid, false otherwise
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function is_valid(): bool {
        if (count($this->get_steps()) < 1) {
            return false;
        }

        foreach ($this->get_steps() as $step) {
            if (!$step->is_valid()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Updates the title of this workflow.
     *
     * @param string $title New title of the workflow
     * @return void
     * @throws \dml_exception
     */
    public function set_title(string $title): void {
        global $DB, $USER;

        $DB->update_record(db_table::WORKFLOW->value, [
            'id' => $this->id,
            'title' => $title,
            'modifiedby' => $USER->id,
            'timemodified' => time(),
        ]);

        $this->title = $title;
    }

    /**
     * Updates the description of this workflow.
     *
     * @param string|null $description New description of the workflow
     * @return void
     * @throws \dml_exception
     */
    public function set_description(?string $description): void {
        global $DB, $USER;

        $DB->update_record(db_table::WORKFLOW->value, [
            'id' => $this->id,
            'description' => $description,
            'modifiedby' => $USER->id,
            'timemodified' => time(),
        ]);

        $this->description = $description;
    }

    /**
     * Activates this workflow.
     *
     * @return void
     * @throws \dml_exception On database errors
     * @throws \moodle_exception If the workflow is not valid
     */
    public function activate(): void {
        global $DB, $USER;

        if (!$this->is_valid()) {
            throw new \moodle_exception('workflow_invalid', 'tool_userautodelete');
        }

        $now = time();
        $DB->update_record(db_table::WORKFLOW->value, [
            'id' => $this->id,
            'active' => 1,
            'modifiedby' => $USER->id,
            'timemodified' => time(),
        ]);

        $this->active = true;
        $this->modifiedby = $USER->id;
        $this->timemodified = $now;
    }

    /**
     * Deactivates this workflow.
     *
     * @return void
     * @throws \dml_exception
     */
    public function deactivate(): void {
        global $DB, $USER;

        $transaction = $DB->start_delegated_transaction();

        // Abort all active processes.
        foreach ($this->get_steps() as $step) {
            foreach (process::get_active_processes_for_step($step) as $process) {
                $process->abort();
            }
        }

        // Update process metadata entry.
        $now = time();
        $DB->update_record(db_table::WORKFLOW->value, [
            'id' => $this->id,
            'active' => 0,
            'modifiedby' => $USER->id,
            'timemodified' => time(),
        ]);
        $transaction->allow_commit();

        $this->active = false;
        $this->modifiedby = $USER->id;
        $this->timemodified = $now;
    }

    /**
     * Moves this workflow up or down in the sort order.
     *
     * @param sort_move_direction $direction Direction to move the workflow in
     * @return void
     * @throws \dml_exception
     */
    public function move(sort_move_direction $direction): void {
        global $DB, $USER;

        // Prevent moving beyond upper/lower bounds.
        if ($direction === sort_move_direction::UP && $this->sort <= 1) {
            return;
        }

        if ($direction === sort_move_direction::DOWN && $this->sort >= self::get_workflow_count()) {
            return;
        }

        $transaction = $DB->start_delegated_transaction();

        // Find the workflow to swap the current sort index with.
        $otherworkflowrecord = $DB->get_record(
            db_table::WORKFLOW->value,
            ['sort' => match ($direction) {
                sort_move_direction::UP => $this->sort - 1,
                sort_move_direction::DOWN => $this->sort + 1,
            }],
            'id, sort',
            MUST_EXIST
        );

        // Swap sort indexes.
        $DB->update_record(db_table::WORKFLOW->value, [
            'id' => $otherworkflowrecord->id,
            'sort' => $this->sort,
            'modifiedby' => $USER->id,
            'timemodified' => time(),
        ]);
        $DB->update_record(db_table::WORKFLOW->value, [
            'id' => $this->id,
            'sort' => $otherworkflowrecord->sort,
            'modifiedby' => $USER->id,
            'timemodified' => time(),
        ]);

        $transaction->allow_commit();

        // Update this object.
        $this->sort = $otherworkflowrecord->sort;
    }

    /**
     * Processes this workflow by progressing existing user deletion processes
     * and ingesting new applicable users into the workflow.
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function process(): void {
        // Only process active workflows.
        if (!$this->active) {
            return;
        }

        // Progress existing processes.
        // Steps are processed in reverse order to ensure that users that
        // progress to the next step within this run are not processed again
        // within the same run.
        foreach (array_reverse($this->steps) as $step) {
            foreach (process::get_active_processes_for_step($step, transitionableonly: true) as $process) {
                $process->transition();
            }
        }

        // Ingest new applicable users.
        $newusers = $this->get_applicable_users();
        foreach ($newusers as $userid) {
            process::create($userid, $this);
        }
    }

    /**
     * Retrieves all users that are applicable for this workflow based on
     * the filters defined in the first step of this workflow.
     *
     * @return int[] Array of user IDs that are applicable for this workflow
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function get_applicable_users(): array {
        global $DB;

        $userfilterclause = $this->steps[0]->generate_user_filter_clause();

        // Get all users this workflow is sensitive to and that are not yet part
        // of any other workflow.
        return $DB->get_fieldset_sql(
            'SELECT u.id ' .
            'FROM {user} u ' .
            'LEFT JOIN {' . db_table::USER_PROCESS->value . '} p ON p.userid = u.id ' .
            'WHERE u.deleted = 0 ' .
            '    AND p.id IS NULL ' .
            '    AND ' . $userfilterclause->sql,
            $userfilterclause->params
        );
    }

    /**
     * Loads the default workflow steps with their respective filters and
     * actions into this workflow.
     *
     * By default this method will throw an exception when called on an already
     * populated workflow. This can be overridden by setting the $force
     * parameter to true, which will delete all existing steps, filters and
     * actions of this workflow before loading the default ones.
     *
     * @param bool $force If true, existing steps, filters and actions of this
     * workflow will be deleted before loading the default ones.
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function load_default_workflow(bool $force = false): void {
        // Prevent accidental workflow wiping.
        if ($this->get_step_count() > 0) {
            if (!$force) {
                throw new \moodle_exception('workflow_not_empty', 'tool_userautodelete');
            } else {
                // Clear existing steps when overwrite is forced.
                foreach ($this->get_steps() as $step) {
                    $step->delete();
                }
            }
        }

        // TODO (MDL-0): Customize filter and step settings.

        // Warning phase.
        $warningstep = step::create(workflow: $this);
        userdeletefilter::create_instance($warningstep, 'lastaccess');
        userdeleteaction::create_instance($warningstep, 'mail', [
            'subject' => get_string('defaultworkflow_warningmail_subject', 'tool_userautodelete'),
            'message' => get_string('defaultworkflow_warningmail_message', 'tool_userautodelete'),
        ]);

        // Deletion phase.
        $deletionstep = step::create(workflow: $this);
        userdeletefilter::create_instance($deletionstep, 'delay');
        userdeleteaction::create_instance($deletionstep, 'mail', [
            'subject' => get_string('defaultworkflow_deletemail_subject', 'tool_userautodelete'),
            'message' => get_string('defaultworkflow_deletemail_message', 'tool_userautodelete'),
        ]);
        userdeleteaction::create_instance($deletionstep, 'delete');
        userdeleteaction::create_instance($deletionstep, 'anonymize');
    }
}
