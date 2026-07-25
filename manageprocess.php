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
 * Collection of user process management operations.
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_userautodelete\form\process_abort_form;
use tool_userautodelete\local\type\process_state;
use tool_userautodelete\local\util\adminpage_util;
use tool_userautodelete\process;

require_once(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

global $CFG, $DB, $OUTPUT, $PAGE, $SITE, $USER;

require_admin();

// Request parameters.
$processid = required_param('id', PARAM_INT);
$returnurl = required_param('returnurl', PARAM_LOCALURL);
$action = required_param('action', PARAM_ALPHA);

// Setup page as sub-admin page of workflows overview.
// This does not use admin_externalpage_setup as we do not want these detail
// pages to be accessible via the default admin navigation tree since these
// always require valid parameters.
adminpage_util::admin_hidden_externalpage_setup(
    section: 'tool_userautodelete_workflow',
    title: get_string('manage_workflow', 'tool_userautodelete'),
    url: new moodle_url('/admin/tool/userautodelete/manageprocess.php', [
        'id' => $processid,
        'action' => $action,
    ]),
    parentsection: 'tool_userautodelete_workflows'
);

$process = process::get_by_id($processid);

// Handle actions.
$output = '';
if ($action == 'abort') {
    if ($process->state !== process_state::ACTIVE) {
        throw new moodle_exception('cannot_abort_inactive_process', 'tool_userautodelete');
    }

    $form = new process_abort_form();
    if ($form->is_submitted()) {
        if (!$form->is_cancelled()) {
            $process->abort();
        }
    } else {
        $output = $form->render();
    }
} else {
    throw new moodle_exception('invalid_action', 'tool_userautodelete');
}

// Handle redirects.
if (!$output) {
    redirect(new moodle_url($returnurl));
}

// Output page if not redirected.
echo $OUTPUT->header();
echo $output;
echo $OUTPUT->footer();
