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
 * This file defines the log table renderer
 *
 * @package   tool_userautodelete
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\output;

use html_writer;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

// @codeCoverageIgnoreStart
global $CFG;
require_once($CFG->libdir . '/tablelib.php');
// @codeCoverageIgnoreEnd


/**
 * Table renderer for the log table
 */
class log_table extends \table_sql {
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
            'runtime',
            'recovered',
            'warned',
            'deleted',
        ]);

        $this->define_headers([
            get_string('time'),
            get_string('recovered', 'tool_userautodelete'),
            get_string('warned', 'tool_userautodelete'),
            get_string('deleted', 'tool_userautodelete'),
        ]);

        $this->set_sql('*', '{tool_userautodelete_log}', '1=1', []);

        $this->sortable(true, 'runtime', SORT_DESC);
        $this->collapsible(false);
    }

    /**
     * Column renderer for the runtime column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     * @throws \coding_exception
     */
    public function col_runtime($values) {
        return userdate($values->runtime, get_string('strftimedatetimeaccurate', 'langconfig'));
    }

    /**
     * Column renderer for the recovered column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     * @throws \coding_exception
     */
    public function col_recovered($values) {
        $color = $values->recovered > 0 ? 'success' : 'secondary';
        return html_writer::span($values->recovered, "badge badge-{$color} text-bg-{$color} p-2");
    }

    /**
     * Column renderer for the recovered column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     * @throws \coding_exception
     */
    public function col_warned($values) {
        $color = $values->warned > 0 ? 'warning' : 'secondary';
        return html_writer::span($values->warned, "badge badge-{$color} text-bg-{$color} p-2");
    }

    /**
     * Column renderer for the deleted column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     * @throws \coding_exception
     */
    public function col_deleted($values) {
        $color = $values->deleted > 0 ? 'danger' : 'secondary';
        return html_writer::span($values->deleted, "badge badge-{$color} text-bg-{$color} p-2");
    }
}
