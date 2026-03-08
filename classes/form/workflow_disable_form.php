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
 * Defines the disable form for workflows
 *
 * @package    tool_userautodelete
 * @copyright  2026 Niels Gandraß <niels@gandrass.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\form;

use tool_userautodelete\workflow;

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

require_once("$CFG->libdir/formslib.php"); // @codeCoverageIgnore


/**
 * Form to trigger disabling of a workflow
 */
class workflow_disable_form extends \moodleform {
    /**
     * Form definition.
     *
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function definition() {
        global $OUTPUT;
        $mform = $this->_form;

        $workflow = workflow::get_by_id($this->optional_param('id', null, PARAM_INT));

        // Generic warning message.
        $warnhead = get_string('areyousure');
        $warnmsg = get_string('disable_workflow_warning', 'tool_userautodelete');
        $warndetails  = '<b>' . $workflow->title . ' (ID: ' . $workflow->id . ')</b><br>';
        $warndetails .= '<span>' . $workflow->description . '</span>';

        // Print warning element.
        $mform->addElement('html', $OUTPUT->notification(
            "<h4>$warnhead</h4> $warnmsg <hr/> $warndetails",
            \core\output\notification::NOTIFY_ERROR,
            false,
        ));

        // Preserve internal information of the management page.
        $mform->addElement('hidden', 'id', $this->optional_param('id', null, PARAM_INT));
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'action', $this->optional_param('action', null, PARAM_TEXT));
        $mform->setType('action', PARAM_TEXT);
        $mform->addElement('hidden', 'returnurl', $this->optional_param('returnurl', null, PARAM_RAW));
        $mform->setType('returnurl', PARAM_RAW);

        // Action buttons.
        $this->add_action_buttons(true, get_string('disable_workflow', 'tool_userautodelete'));
    }
}
