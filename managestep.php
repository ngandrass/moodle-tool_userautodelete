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
 * Collection of step management operations
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_userautodelete\local\type\sort_move_direction;
use tool_userautodelete\step;

require_once(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

global $CFG, $DB, $OUTPUT, $PAGE, $SITE, $USER;

require_admin();

// Request parameters.
$stepid = required_param('id', PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);
$returnurl = optional_param('returnurl', null, PARAM_RAW);

// Setup page as sub-admin page of workflows overview.
// This does not use admin_externalpage_setup as we do not want these detail
// pages to be accessible via the default admin navigation tree since these
// always require valid parameters.
$PAGE->set_url(new moodle_url('/admin/tool/userautodelete/managestep.php', ['id' => $stepid, 'action' => $action]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('manage_workflow', 'tool_userautodelete'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('admin');

navigation_node::require_admin_tree();
$parentnavnode = $PAGE->settingsnav->find('tool_userautodelete_workflows', navigation_node::TYPE_SETTING);
$navnode = $parentnavnode->add(
    get_string('manage_workflow', 'tool_userautodelete'),
    new moodle_url('/admin/tool/userautodelete/managestep.php', ['id' => $stepid])
);
$navnode->make_active();
$PAGE->navigation->clear_cache();

$PAGE->add_header_action($OUTPUT->render_from_template('core_admin/header_search_input', [
    'action' => new moodle_url('/admin/search.php'),
    'query' => $PAGE->url->get_param('query'),
]));

// Get requested step.
$step = step::get_by_id($stepid);

// Handle actions.
$output = '';
if ($action == 'edit') {
    $step->set_title(required_param('title', PARAM_TEXT));
    $step->set_description(required_param('description', PARAM_TEXT));
} else if ($action == 'moveup') {
    $step->move(sort_move_direction::UP);
} else if ($action == 'movedown') {
    $step->move(sort_move_direction::DOWN);
} else if ($action == 'delete') {
    $form = new \tool_userautodelete\form\step_delete_form();
    if ($form->is_submitted()) {
        if (!$form->is_cancelled()) {
            $step->delete();
        }
    } else {
        $output = $form->render();
    }
} else {
    throw new moodle_exception('invalid_action', 'tool_userautodelete');
}

// Handle redirects.
if (!$output) {
    if ($returnurl) {
        redirect($returnurl);
    } else {
        redirect(new moodle_url('/admin/tool/userautodelete/managestep.php', ['id' => $stepid]));
    }
}

// Output page if not redirected.
echo $OUTPUT->header();
echo $output;
echo $OUTPUT->footer();
