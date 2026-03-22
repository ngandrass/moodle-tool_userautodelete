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
 * Collection of workflow management operations
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_userautodelete\form\workflow_disable_form;
use tool_userautodelete\form\workflow_enable_form;
use tool_userautodelete\local\type\sort_move_direction;
use tool_userautodelete\local\util\adminpage_util;
use tool_userautodelete\step;
use tool_userautodelete\workflow;

require_once(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

global $CFG, $DB, $OUTPUT, $PAGE, $SITE, $USER;

require_admin();

// Request parameters.
$workflowid = required_param('id', PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);
$returnurl = optional_param('returnurl', null, PARAM_RAW);

// Setup page as sub-admin page of workflows overview.
// This does not use admin_externalpage_setup as we do not want these detail
// pages to be accessible via the default admin navigation tree since these
// always require valid parameters.
adminpage_util::admin_hidden_externalpage_setup(
    section: 'tool_userautodelete_workflow',
    title: get_string('manage_workflow', 'tool_userautodelete'),
    url: new moodle_url('/admin/tool/userautodelete/manageworkflow.php', ['id' => $workflowid, 'action' => $action]),
    parentsection: 'tool_userautodelete_workflows'
);

// Get requested workflow.
$workflow = workflow::get_by_id($workflowid);

// Handle actions.
$output = '';
if ($action == 'enable') {
    if (!$workflow->is_valid()) {
        throw new moodle_exception('can_not_activate_invalid_workflow', 'tool_userautodelete');
    }

    $enableform = new workflow_enable_form();
    if ($enableform->is_submitted()) {
        if (!$enableform->is_cancelled()) {
            $workflow->activate();
        }
    } else {
        $output = $enableform->render();
    }
} else if ($action == 'disable') {
    $disableform = new workflow_disable_form();
    if ($disableform->is_submitted()) {
        if (!$disableform->is_cancelled()) {
            $workflow->deactivate();
        }
    } else {
        $output = $disableform->render();
    }
} else if ($action == 'moveup') {
    $workflow->move(sort_move_direction::UP);
} else if ($action == 'movedown') {
    $workflow->move(sort_move_direction::DOWN);
} else if ($action == 'addstep') {
    step::create($workflow);
} else {
    throw new moodle_exception('invalid_action', 'tool_userautodelete');
}

// Handle redirects.
if (!$output) {
    if ($returnurl) {
        redirect($returnurl);
    } else {
        redirect(new moodle_url('/admin/tool/userautodelete/workflow.php', ['id' => $workflowid]));
    }
}

// Output page if not redirected.
echo $OUTPUT->header();
echo $output;
echo $OUTPUT->footer();
