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
 * Renders a single workflow.
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_userautodelete\process;
use tool_userautodelete\workflow;

require_once(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

global $CFG, $DB, $OUTPUT, $PAGE, $SITE, $USER;

require_admin();

// Request parameters.
$workflowid = required_param('id', PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);
$returnurl = optional_param('returnurl', null, PARAM_RAW);
$isediting = false;

// Setup page as sub-admin page of workflows overview.
// This does not use admin_externalpage_setup as we do not want these detail
// pages to be accessible via the default admin navigation tree since these
// always require valid parameters.
$PAGE->set_url(new moodle_url('/admin/tool/userautodelete/workflow.php', ['id' => $workflowid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('manage_workflow', 'tool_userautodelete'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('admin');

navigation_node::require_admin_tree();
$parentnavnode = $PAGE->settingsnav->find('tool_userautodelete_workflows', navigation_node::TYPE_SETTING);
$navnode = $parentnavnode->add(
    get_string('manage_workflow', 'tool_userautodelete'),
    new moodle_url('/admin/tool/userautodelete/workflow.php', ['id' => $workflowid])
);
$navnode->make_active();
$PAGE->navigation->clear_cache();

$PAGE->add_header_action($OUTPUT->render_from_template('core_admin/header_search_input', [
    'action' => new moodle_url('/admin/search.php'),
    'query' => $PAGE->url->get_param('query'),
]));

// Get requested workflow.
$workflow = workflow::get_by_id($workflowid);

// Handle actions.
if ($action) {
    switch ($action) {
        case 'edit':
            if ($workflow->active) {
                throw new moodle_exception('cannot_edit_active_workflow', 'tool_userautodelete');
            }
            $isediting = true;
            break;
        default:
            throw new moodle_exception('invalid_action', 'tool_userautodelete');
    }

    if ($returnurl) {
        redirect($returnurl);
    }
}

// Prepare step data.
$stepsmeta = [];
$processesmeta = process::get_process_stats_for_workflow(workflowid: $workflow->id, indexbystepid: true);
foreach ($workflow->steps as $step) {
    $stepsmeta[] = [
        'id' => $step->id,
        'title' => $step->title,
        'description' => $step->description,
        'sort' => $step->sort,
        'isfirst' => $step->sort === 1,
        'islast' => $step->sort === count($workflow->steps),
        'processes' => $processesmeta[$step->id],
        'filters' => array_map(fn ($filter) => [
            'id' => $filter->id,
            'name' => $filter->get_plugin_name(),
            'title' => $filter->get_instance_title(),
            'details' => $filter->get_instance_details(),
        ], $step->filters),
        'actions' => array_map(fn ($action) => [
            'id' => $action->id,
            'name' => $action->get_plugin_name(),
            'title' => $action->get_instance_title(),
            'details' => $action->get_instance_details(),
        ], $step->actions),
    ];
}

// Render main output.
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('tool_userautodelete/workflow', [
    'id' => $workflow->id,
    'title' => $workflow->title,
    'description' => $workflow->description,
    'sort' => $workflow->sort,
    'active' => $workflow->active,
    'timecreated' => $workflow->timecreated,
    'timemodified' => $workflow->timemodified,
    'createdby' => [
        'id' => $workflow->createdby,
        'fullname' => fullname(core_user::get_user($workflow->createdby)),
        'profileurl' => new moodle_url('/user/profile.php', ['id' => $workflow->createdby]),
    ],
    'modifiedby' => [
        'id' => $workflow->modifiedby,
        'fullname' => fullname(core_user::get_user($workflow->modifiedby)),
        'profileurl' => new moodle_url('/user/profile.php', ['id' => $workflow->modifiedby]),
    ],
    'stepcount' => count($stepsmeta),
    'steps' => $stepsmeta,
    'isediting' => $isediting,
    'canbeactivated' => false, // TODO (MDL-0): Create check if a workflow is valid.
    'actionurls' => [
        'activate' => new moodle_url(
            '/admin/tool/userautodelete/manageworkflow.php',
            [
                'id' => $workflow->id,
                'action' => $workflow->active ? 'disable' : 'enable',
            ]
        ),
        'delete' => new moodle_url(
            '/admin/tool/userautodelete/workflow.php',
            ['id' => $workflow->id, 'action' => 'TODO'] // TODO (MDL-0): Implement.
        ),
        'edit' => new moodle_url(
            '/admin/tool/userautodelete/workflow.php',
            [
                'id' => $workflow->id,
                'action' => $isediting ? '' : 'edit',
            ]
        ),
    ],
]);
echo $OUTPUT->footer();
