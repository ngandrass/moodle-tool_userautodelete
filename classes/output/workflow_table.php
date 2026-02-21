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
 * This file defines the workflow table renderer
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\output;

use html_writer;
use tool_userautodelete\local\type\db_table;
use tool_userautodelete\process;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

// @codeCoverageIgnoreStart
global $CFG;
require_once($CFG->libdir . '/tablelib.php');
// @codeCoverageIgnoreEnd


/**
 * Table renderer for the workflow table
 */
class workflow_table extends \table_sql {
    /**
     * Constructor
     *
     * @param string $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     *
     * @throws \coding_exception
     */
    public function __construct(string $uniqueid) {
        parent::__construct($uniqueid);
        $this->define_columns([
            'workflow',
            'status',
            'processes',
            'modified',
            'actions',
        ]);

        $this->define_headers([
            get_string('workflow', 'tool_userautodelete'),
            get_string('status'),
            get_string('processes', 'tool_userautodelete'),
            get_string('lastmodified'),
            '',
        ]);

        $this->set_sql(
            fields: '*',
            from: '{' . db_table::WORKFLOW->value . '}',
            where: '1=1',
            params: []
        );

        $this->sortable(false, 'sort', 'ASC');
        $this->collapsible(false);
    }

    /**
     * Column renderer for the workflow column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     * @throws \coding_exception
     */
    public function col_workflow($values) {
        return "<b>{$values->title}</b><br><span>{$values->description}</span>";
    }

    /**
     * Column renderer for the status column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     * @throws \coding_exception
     */
    public function col_status($values) {
        if ($values->active) {
            return '<span class="badge badge-primary">' . get_string('active') . '</span>';
        } else {
            return '<span class="badge badge-secondary">' . get_string('disabled', 'admin') . '</span>';
        }
    }

    /**
     * Column renderer for the processes column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     * @throws \coding_exception
     */
    public function col_processes($values) {
        $processmeta = process::get_process_stats_for_workflow($values->id);
        $globalstats = array_reduce($processmeta, function ($carry, $item) {
            $carry['active'] += $item->active;
            $carry['finished'] += $item->finished;
            $carry['total'] += $item->total;
            return $carry;
        }, ['active' => 0, 'finished' => 0, 'total' => 0]);

        $html  = count($processmeta) . ' ' . get_string('steps', 'tool_userautodelete') . '<br>';
        $html .= $globalstats['active'] . ' ' . get_string('active') . '<br>';
        $html .= $globalstats['finished'] . ' ' . get_string('finished', 'tool_userautodelete') . '<br>';
        $html .= $globalstats['total'] . ' ' . get_string('total');
        return $html;
    }

    /**
     * Column renderer for the modified column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     * @throws \coding_exception
     */
    public function col_modified($values) {
        $user = \core_user::get_user($values->modifiedby, '*', MUST_EXIST);

        return html_writer::link(
            url: new \moodle_url('/user/profile.php', ['id' => $values->modifiedby]),
            text: fullname($user)
        ) . html_writer::empty_tag('br') . html_writer::span(
            userdate($values->timemodified, get_string('strftimedatetime', 'langconfig'))
        );
    }

    /**
     * Column renderer for the actions column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     * @throws \coding_exception
     */
    public function col_actions($values) {
        return "TODO";
    }
}
