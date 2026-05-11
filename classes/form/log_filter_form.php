<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Log filter form
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\form;

use tool_userautodelete\workflow;

// @codingStandardsIgnoreLine
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

// @codeCoverageIgnoreStart
global $CFG;
require_once($CFG->libdir . '/formslib.php');
// @codeCoverageIgnoreEnd

/**
 * Form for filtering the log table by workflow and step.
 */
class log_filter_form extends \moodleform {
    /**
     * Form definition
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function definition(): void {
        $mform = $this->_form;
        $selectedworkflowid = optional_param('workflowid', null, PARAM_INT) ?: null;
        $selectedworkflow = null;

        // Build workflow options.
        $workflowoptions = [0 => get_string('log_filter_all_workflows', 'tool_userautodelete')];
        foreach (workflow::get_all() as $workflow) {
            $workflowoptions[$workflow->id] = "{$workflow->title} (ID: {$workflow->id})";

            // Memorize curently selected workflow if given.
            if ($workflow->id === $selectedworkflowid) {
                $selectedworkflow = $workflow;
            }
        }

        // Build step options if workflow is given.
        if ($selectedworkflow) {
            $stepoptions = [0 => get_string('log_filter_all_steps', 'tool_userautodelete')];
            foreach ($selectedworkflow->steps as $step) {
                $stepoptions[$step->id] = get_string('step', 'tool_userautodelete') . " {$step->sort}: " .
                    ($step->title ?: get_string('unnamed', 'tool_userautodelete'));
            }
            $stepattrs = [];
        } else {
            $stepoptions = [0 => ''];
            $stepattrs = ['disabled' => 'disabled'];
        }

        // Keep selects and action buttons in a single group so they render in one row.
        $elementgroup = [];
        $elementgroup[] = $mform->createElement('html', '<span>' . get_string('workflow', 'tool_userautodelete') . ':</span>');
        $elementgroup[] = $mform->createElement('select', 'workflowid', '', $workflowoptions);
        $elementgroup[] = $mform->createElement('html', '<span>' . get_string('step', 'tool_userautodelete') . ':</span>');
        $elementgroup[] = $mform->createElement('select', 'stepid', '', $stepoptions, $stepattrs);
        $elementgroup[] = $mform->createElement(
            'submit',
            'submitbutton',
            get_string('log_filter_apply', 'tool_userautodelete')
        );
        $elementgroup[] = $mform->createElement(
            'submit',
            'resetbutton',
            get_string('log_filter_reset', 'tool_userautodelete')
        );
        $mform->addGroup(
            $elementgroup,
            'logfilters',
            get_string('filter_log_entries', 'tool_userautodelete'),
            ['<span class="mx-1"></span>'],
            false
        );

        $mform->setType('workflowid', PARAM_INT);
        $mform->setType('stepid', PARAM_INT);
    }
}
