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
 * Log inspection page
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

global $OUTPUT, $PAGE;

// Login and capability checks as well as $PAGE setup are performed by admin_externalpage_setup.
admin_externalpage_setup('tool_userautodelete_log');

// Allow to supply filters via $_GET without form submission.
$workflowid = optional_param('workflowid', null, PARAM_INT);
$stepid = optional_param('stepid', null, PARAM_INT);

// Build the filter form and set current values.
$filterform = new \tool_userautodelete\form\log_filter_form($PAGE->url);

if ($filterform->is_submitted()) {
    $data = $filterform->get_data();
    if ($data) {
        // Reset button clears all filters and redirects to clean URL.
        if (!empty($data->resetbutton)) {
            redirect($PAGE->url);
        }
        $workflowid = !empty($data->workflowid) ? (int)$data->workflowid : null;
        $stepid = !empty($data->stepid) ? (int)$data->stepid : null;
    }
}

// Restore current filter selection into the form.
$filterform->set_data([
    'workflowid' => $workflowid ?? 0,
    'stepid' => $stepid ?? 0,
]);

// Build table URL including active filter params so table paging/sorting preserves them.
$tableurl = new \moodle_url($PAGE->url, [
    'workflowid' => $workflowid ?? 0,
    'stepid' => $stepid ?? 0,
]);

$logtbl = new \tool_userautodelete\output\log_table('logtable', $workflowid, $stepid);
$logtbl->define_baseurl($tableurl);

// Render main output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('logs', 'tool_userautodelete'));

$filterform->display();

$logtbl->out(50, false);
echo $OUTPUT->footer();
