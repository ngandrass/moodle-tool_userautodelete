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
 * @property-read int $sort Sort index of this workflow in relation to other workflows
 * @property-read bool $active If true, the workflow will actively be processed
 * @property-read int $createdby ID of the user that created this workflow
 * @property-read int $modifiedby ID of the user that last modified this workflow
 * @property-read int $timecreated Unix timestamp when this workflow was created
 * @property-read int $timemodified Unix timestamp when this workflow was last modified
 */
class workflow {
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
        $sort = 1;
        $lastworkflowrecord = $DB->get_records(db_table::WORKFLOW->value, null, 'sort DESC', 'id, sort', 0, 1);
        if ($lastworkflowrecord) {
            $lastworkflow = reset($lastworkflowrecord);
            $sort = $lastworkflow->sort + 1;
        }

        // Create actual new workflow record.
        $id = $DB->insert_record(db_table::WORKFLOW->value, [
            'title' => $title,
            'description' => $description,
            'sort' => $sort,
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
     */
    public function get_step_count(): int {
        global $DB;

        return $DB->count_records(db_table::WORKFLOW_STEP->value, ['workflowid' => $this->id]);
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
     * @throws \dml_exception
     */
    public function activate(): void {
        global $DB, $USER;

        $DB->update_record(db_table::WORKFLOW->value, [
            'id' => $this->id,
            'active' => 1,
            'modifiedby' => $USER->id,
            'timemodified' => time(),
        ]);

        $this->active = true;
    }

    /**
     * Deactivates this workflow.
     *
     * @return void
     * @throws \dml_exception
     */
    public function deactivate(): void {
        global $DB, $USER;

        $DB->update_record(db_table::WORKFLOW->value, [
            'id' => $this->id,
            'active' => 0,
            'modifiedby' => $USER->id,
            'timemodified' => time(),
        ]);

        $this->active = false;
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
}
