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
 * This file defines the dry-run users table renderer
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\output;

use core\exception\moodle_exception;
use moodle_url;
use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\process_state;
use tool_userautodelete\workflow;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

// @codeCoverageIgnoreStart
global $CFG;
require_once($CFG->libdir . '/tablelib.php');
// @codeCoverageIgnoreEnd


/**
 * Table renderer for the dry-run users table
 */
class dryrun_users_table extends \table_sql {
    /**
     * Constructor
     *
     * @param string $uniqueid all tables have to have a unique id, this is used
     *      as a key when storing table properties like sort order in the session.
     * @param workflow $workflow Workflow object this dry-run table is for
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function __construct(string $uniqueid, workflow $workflow) {
        parent::__construct($uniqueid);
        $this->define_columns([
            'id',
            'user',
            'lastaccess',
            'status',
            'auth',
        ]);

        $this->define_headers([
            get_string('id', 'tool_userautodelete'),
            get_string('user'),
            get_string('lastaccess'),
            get_string('status'),
            get_string('type_auth', 'plugin'),
        ]);

        $userfilterclause = $workflow->steps[0]->generate_user_filter_clause();
        $this->set_sql(
            fields: 'u.*',
            from: '{user} u',
            where: 'u.deleted = 0
                AND NOT EXISTS (
                    SELECT 1 FROM {' . db_table::USER_PROCESS->value . '} p
                    WHERE p.userid = u.id AND p.state = :activestate
                )
                AND ' . $userfilterclause->sql,
            params: array_merge(['activestate' => process_state::ACTIVE->value], $userfilterclause->params)
        );

        $this->sortable(true, 'lastaccess', SORT_DESC);
        $this->collapsible(false);
    }

    /**
     * Column renderer for the user column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     * @throws moodle_exception
     */
    public function col_user($values) {
        $userurl = new moodle_url('/user/profile.php', ['id' => $values->id]);
        return '<a href="' . $userurl . '">' . fullname($values) . ' (' . $values->username . ')</a>';
    }

    /**
     * Column renderer for the lastaccess column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     */
    public function col_lastaccess($values) {
        return userdate($values->lastaccess);
    }

    /**
     * Column renderer for the status column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     * @throws \coding_exception
     */
    public function col_status($values) {
        if ($values->suspended) {
            return '<span class="badge badge-danger rounded-pill text-bg-danger">' . get_string('suspended') . '</span>';
        } else {
            return '<span class="badge badge-primary rounded-pill text-bg-primary">' . get_string('active') . '</span>';
        }
    }

    /**
     * Column renderer for the auth column
     *
     * @param \stdClass $values Values of the current row
     * @return string HTML code to be displayed
     * @throws \coding_exception
     */
    public function col_auth($values) {
        return get_string('pluginname', 'auth_' . $values->auth);
    }
}
