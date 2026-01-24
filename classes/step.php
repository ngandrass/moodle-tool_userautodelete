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
 * Each step consists of an incoming filter transition and an action to be
 * performed when users enter the step.
 *
 * @property-read int $id ID of this workflow step
 * @property-read workflow $workflow The workflow this step belongs to
 * @property-read userdeletefilter|null $filter The user filter linked to this step
 * @property-read userdeleteaction|null $action The user action linked to this step
 * @property-read int $sort Position of this step in relation to the steps of the same workflow
 * @property-read string|null $title Optional custom title for this step
 * @property-read string|null $description Optional custom description for this step
 */
class step {
    /**
     * Internal constructor to create an actual workflow step object. Used only
     * by the public static factory methods.
     *
     * @param int $id ID of this workflow step
     * @param workflow $workflow The workflow this step belongs to
     * @param userdeletefilter|null $filter The user filter linked to this step
     * @param userdeleteaction|null $action The user action linked to this step
     * @param int $sort Position of this step in relation to the steps of the same workflow
     * @param string|null $title Optional custom title for this step
     * @param string|null $description Optional custom description for this step
     */
    protected function __construct(
        /** @var int $id ID of this workflow step */
        protected readonly int $id,
        /** @var workflow $workflow The workflow this step belongs to */
        protected workflow $workflow,
        /** @var userdeletefilter|null $filter The user filter linked to this step */
        protected ?userdeletefilter $filter,
        /** @var userdeleteaction|null $action The user action linked to this step */
        protected ?userdeleteaction $action,
        /** @var int $sort Position of this step in relation to the steps of the same workflow */
        protected int $sort,
        /** @var string|null $title Optional custom title for this step */
        protected ?string $title,
        /** @var string|null $description Optional custom description for this step */
        protected ?string $description
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
            filter: $record->filterid ? userdeletefilter::get_instance_by_id($record->filterid) : null,
            action: $record->actionid ? userdeleteaction::get_instance_by_id($record->actionid) : null,
            sort: $record->sort,
            title: $record->title,
            description: $record->description
        );
    }

    /**
     * Retrieves all workflow steps for a given workflow.
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
                filter: $record->filterid ? userdeletefilter::get_instance_by_id($record->filterid) : null,
                action: $record->actionid ? userdeleteaction::get_instance_by_id($record->actionid) : null,
                sort: $record->sort,
                title: $record->title,
                description: $record->description
            );
        }

        return $steps;
    }

}
