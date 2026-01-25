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
 * This file defines the workflow step class
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
 * A single step that is part of a workflow.
 *
 * Each step consists of incoming filters that decide which users will transition
 * to this step and a set of actions to be performed when users enter this step.
 *
 * @property-read int $id ID of this workflow step
 * @property-read workflow $workflow The workflow this step belongs to
 * @property-read userdeletefilter[] $filters The user filters linked to this step
 * @property-read userdeleteaction[] $actions The user actions linked to this step
 * @property-read int $sort Position of this step in relation to the steps of the same workflow
 * @property-read string|null $title Optional custom title for this step
 * @property-read string|null $description Optional custom description for this step
 */
class step {
    /** @var userdeletefilter[]|null Lazy-loaded user filter instances linked to this step */
    protected ?array $filters = null;

    /** @var userdeleteaction[]|null Lazy-loaded user action instances linked to this step */
    protected ?array $actions = null;

    /**
     * Internal constructor to create an actual workflow step object. Used only
     * by the public static factory methods.
     *
     * @param int $id ID of this workflow step
     * @param workflow $workflow The workflow this step belongs to
     * @param int $sort Position of this step in relation to the steps of the same workflow
     * @param string|null $title Optional custom title for this step
     * @param string|null $description Optional custom description for this step
     */
    protected function __construct(
        /** @var int $id ID of this workflow step */
        protected readonly int $id,
        /** @var workflow $workflow The workflow this step belongs to */
        protected workflow $workflow,
        /** @var int $sort Position of this step in relation to the steps of the same workflow */
        protected int $sort,
        /** @var string|null $title Optional custom title for this step */
        protected ?string $title,
        /** @var string|null $description Optional custom description for this step */
        protected ?string $description
    ) {
        $this->filters = null;
        $this->actions = null;
    }

    /**
     * Allows read-only access to object properties
     *
     * @param string $name Name of the property to access
     * @return mixed Value of the requested property
     * @throws \coding_exception
     */
    public function __get(string $name): mixed {
        // Handle lazy-loaded props.
        switch ($name) {
            case 'filters':
                return $this->get_filters();
            case 'actions':
                return $this->get_actions();
        }

        // Generic fallback.
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        throw new \coding_exception('Invalid property: ' . $name);
    }

    /**
     * Retrieves the user filters linked to this workflow step.
     *
     * @return userdeletefilter[] An array of user filter instances
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function get_filters(): array {
        global $DB;

        if ($this->filters === null) {
            $this->filters = array_map(
                fn($filterid) => userdeletefilter::get_instance_by_id($filterid),
                $DB->get_fieldset(
                    db_table::WORKFLOW_FILTER->value,
                    'id',
                    ['stepid' => $this->id]
                )
            );
        }

        return $this->filters;
    }

    /**
     * Retrieves the user actions linked to this workflow step.
     *
     * @return userdeleteaction[] An array of user action instances
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function get_actions(): array {
        global $DB;

        if ($this->actions === null) {
            $this->actions = array_map(
                fn($actionid) => userdeleteaction::get_instance_by_id($actionid),
                $DB->get_fieldset(
                    db_table::WORKFLOW_ACTION->value,
                    'id',
                    ['stepid' => $this->id]
                )
            );
        }

        return $this->actions;
    }

    /**
     * Marks this step and its parent workflow as modified.
     *
     * This method also clears any cached filter and action instances.
     *
     * @return void
     * @throws \dml_exception
     */
    public function touch(): void {
        $this->actions = null;
        $this->filters = null;
        $this->workflow->touch();
    }

    /**
     * Retrieves a workflow step by its ID.
     *
     * @param int $stepid ID of the workflow step to retrieve
     * @return step The workflow step object
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_by_id(int $stepid): step {
        global $DB;

        $record = $DB->get_record(db_table::WORKFLOW_STEP->value, ['id' => $stepid], '*', MUST_EXIST);

        return new step(
            id: $record->id,
            workflow: workflow::get_by_id($record->workflowid),
            sort: $record->sort,
            title: $record->title,
            description: $record->description
        );
    }

    /**
     * Retrieves all workflow steps for a given workflow.
     *
     * Retrieved steps will be sorted by their order in the workflow, from first
     * step to last step.
     *
     * @param workflow $workflow The workflow to retrieve the steps for
     * @return step[] An array of workflow step objects
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function get_all_workflow_steps(workflow $workflow): array {
        global $DB;

        $records = $DB->get_records(
            db_table::WORKFLOW_STEP->value,
            ['workflowid' => $workflow->id],
            'sort ASC'
        );

        $steps = [];
        foreach ($records as $record) {
            $steps[] = new step(
                id: $record->id,
                workflow: $workflow,
                sort: $record->sort,
                title: $record->title,
                description: $record->description
            );
        }

        return $steps;
    }

    /**
     * Retrieves the next step in the workflow associated workflow
     *
     * @return step|null The next workflow step or null if this is the final step
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function next(): ?step {
        global $DB;

        // Find the next step in the workflow based on the sort order.
        $record = $DB->get_record(
            db_table::WORKFLOW_STEP->value,
            ['workflowid' => $this->workflow->id, 'sort' => $this->sort + 1],
            'id',
            IGNORE_MISSING
        );

        if (!$record) {
            return null;
        }

        return self::get_by_id($record->id);
    }

    /**
     * Retrieves the previous step in the workflow associated workflow
     *
     * @return step|null The previous workflow step or null if this is the first step
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function previous(): ?step {
        global $DB;

        // Fail early if this is the first step.
        if ($this->sort <= 1) {
            return null;
        }

        // Find the previous step in the workflow based on the sort order.
        $record = $DB->get_record(
            db_table::WORKFLOW_STEP->value,
            ['workflowid' => $this->workflow->id, 'sort' => $this->sort - 1],
            'id',
            MUST_EXIST
        );

        return self::get_by_id($record->id);
    }

    /**
     * Checks whether this step is the final step in the workflow.
     *
     * @return bool True if this is the final step, false otherwise
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function is_final(): bool {
        return $this->next() === null;
    }

    /**
     * Checks whether this step is the first step in the workflow.
     *
     * @return bool True if this is the first step, false otherwise
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function is_first(): bool {
        return $this->previous() === null;
    }

    /**
     * Moves this workflow step up or down in the sort order.
     *
     * @param sort_move_direction $direction Direction to move the workflow step in
     * @return void
     * @throws \dml_exception
     */
    public function move(sort_move_direction $direction): void {
        global $DB;

        // Prevent moving beyond upper/lower bounds.
        if ($direction === sort_move_direction::UP && $this->sort <= 1) {
            return;
        }

        if ($direction === sort_move_direction::DOWN && $this->sort >= $this->workflow->get_step_count()) {
            return;
        }

        $transaction = $DB->start_delegated_transaction();

        // Find the workflow step to swap the current sort index with.
        $othersteprecord = $DB->get_record(
            db_table::WORKFLOW_STEP->value,
            ['workflowid' => $this->workflow->id, 'sort' => match ($direction) {
                sort_move_direction::UP => $this->sort - 1,
                sort_move_direction::DOWN => $this->sort + 1,
            }],
            'id, sort',
            MUST_EXIST
        );

        // Swap sort indexes.
        $DB->update_record(db_table::WORKFLOW_STEP->value, [
            'id' => $othersteprecord->id,
            'sort' => $this->sort,
        ]);
        $DB->update_record(db_table::WORKFLOW_STEP->value, [
            'id' => $this->id,
            'sort' => $othersteprecord->sort,
        ]);

        $this->workflow->touch();
        $transaction->allow_commit();

        // Update this object.
        $this->sort = $othersteprecord->sort;
    }
}
