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
 * This file defines the action log table renderer
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\output;

use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\util\plugin_util;
use tool_userautodelete\step;
use tool_userautodelete\userdeleteaction;
use tool_userautodelete\workflow;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

// @codeCoverageIgnoreStart
global $CFG;
require_once($CFG->libdir . '/tablelib.php');
// @codeCoverageIgnoreEnd


/**
 * Table renderer for the action log
 */
class log_table extends \table_sql {
    /** @var workflow[] Cached workflow objects */
    protected array $workflows = [];

    /** @var step[] Cached step objects */
    protected array $steps = [];

    /**
     * Constructor
     *
     * @param string $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     *
     * @param int|null $workflowid If given, only log entries matching the given workflow id are displayed
     * @param int|null $stepid If given, only log entries matching the given step id are displayed
     * @throws \coding_exception
     */
    public function __construct(string $uniqueid, ?int $workflowid = null, ?int $stepid = null) {
        parent::__construct($uniqueid);
        $this->define_columns([
            'timestamp',
            'affectedusers',
            'action',
            'workflow',
            'step',
        ]);

        $this->define_headers([
            get_string('date'),
            get_string('users'),
            get_string('action', 'tool_userautodelete'),
            get_string('workflow', 'tool_userautodelete'),
            get_string('step', 'tool_userautodelete'),
        ]);

        // Build query.
        $wheresql = [];
        $params = [];
        if ($workflowid) {
            $wheresql[] = 'workflowid = :workflowid';
            $params['workflowid'] = $workflowid;
        }
        if ($stepid) {
            $wheresql[] = 'stepid = :stepid';
            $params['stepid'] = $stepid;
        }

        $this->set_sql(
            fields: '*',
            from: '{' . db_table::ACTIONLOG->value . '}',
            where: implode(' AND ', $wheresql) ?: '1=1',
            params: $params
        );

        $this->sortable(true, 'timestamp', SORT_DESC);
        $this->collapsible(false);
    }

    /**
     * Column renderer for the timestamp column
     *
     * @param mixed $values Current data row
     * @return string Rendered field content
     * @throws \coding_exception
     */
    public function col_timestamp($values) {
        return userdate($values->timestamp, get_string('strftimedatetimeaccurate', 'langconfig'));
    }

    /**
     * Column renderer for the actions column
     *
     * @param mixed $values Current data row
     * @return string Rendered field content
     */
    public function col_action($values) {
        try {
            /** @var userdeleteaction $action */
            $action = plugin_util::get_subplugin_class('userdeleteaction', $values->action);
            $title = '<i class="me-2 ' . $action::get_icon_class() . '"></i>&nbsp;';
            $title .= get_string('pluginname', 'userdeleteaction_' . $values->action);
        } catch (\moodle_exception $e) {
            $title = '<i class="me-2 ' . userdeleteaction::get_icon_class() . '"></i>';
            $title .= $values->action;
        }

        return $title;
    }

    /**
     * Column renderer for the workflow column
     *
     * @param mixed $values Current data row
     * @return string Rendered field content
     * @throws \coding_exception
     */
    public function col_workflow($values) {
        if (!$values->workflowid) {
            return '<i class="text-muted">' . get_string('none') . '</i>';
        }

        $workflow = $this->get_workflow($values->workflowid);

        // Catch non-existing workflows gracefully.
        if ($workflow === null) {
            return '<i class="text-muted">' . get_string('deleted') . " (ID: {$values->workflowid})</i>";
        }

        // Render existing workflow.
        $workflowurl = new \moodle_url('/admin/tool/userautodelete/workflow.php', ['id' => $workflow->id]);
        $title = "{$workflow->title} (ID: {$workflow->id})";

        return '<a href="' . $workflowurl . '">' . s($title) . '</a>';
    }

    /**
     * Column renderer for the step column
     *
     * @param mixed $values Current data row
     * @return string Rendered field content
     * @throws \coding_exception
     */
    public function col_step($values) {
        if (!$values->stepid) {
            return '<i class="text-muted">' . get_string('none') . '</i>';
        }

        $step = $this->get_step($values->stepid);

        // Catch non-existing steps gracefully.
        if ($step === null) {
            return '<i class="text-muted">' . get_string('deleted') . " (ID: {$values->stepid})</i>";
        }

        // Render existing step.
        $workflowurl = new \moodle_url('/admin/tool/userautodelete/workflow.php', ['id' => $step->workflow->id]);
        $title = get_string('step', 'tool_userautodelete') . " {$step->sort}: " .
            ($step->title ? s($step->title) : '<i>' . get_string('unnamed', 'tool_userautodelete') . '</i>');

        return '<a href="' . $workflowurl . '#step-' . $step->id . '">' . $title . '</a>';
    }

    /**
     * Retrieves a workflow instance by its ID. Uses an internal cache.
     *
     * @param int $workflowid ID of the workflow to retrieve
     * @return workflow Requested workflow object
     * @throws \dml_exception
     */
    protected function get_workflow(int $workflowid) {
        if (!array_key_exists($workflowid, $this->workflows)) {
            try {
                $this->workflows[$workflowid] = workflow::get_by_id($workflowid);
            } catch (\dml_exception $e) {
                // Workflow was deleted, mark as unavailable.
                $this->workflows[$workflowid] = null;
            }
        }

        return $this->workflows[$workflowid];
    }

    /**
     * Retrieves a step instance by its ID. Uses an internal cache.
     *
     * @param int $stepid ID of the step to retrieve
     * @return step Requested step object
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function get_step(int $stepid) {
        if (!array_key_exists($stepid, $this->steps)) {
            try {
                $this->steps[$stepid] = step::get_by_id($stepid);
            } catch (\dml_exception $e) {
                // Step was deleted, mark as unavailable.
                $this->steps[$stepid] = null;
            }
        }

        return $this->steps[$stepid];
    }
}
