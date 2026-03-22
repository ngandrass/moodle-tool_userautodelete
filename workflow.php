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

use tool_userautodelete\local\util\plugin_util;
use tool_userautodelete\process;
use tool_userautodelete\userdeleteaction;
use tool_userautodelete\userdeletefilter;
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
$PAGE->set_url(new moodle_url('/admin/tool/userautodelete/workflow.php', ['id' => $workflowid, 'action' => $action]));
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
$globalprocessmeta = array_reduce(
    $processesmeta,
    function ($carry, $item) {
        $carry['active'] += $item->active;
        $carry['finished'] += $item->finished;
        return $carry;
    },
    ['active' => 0, 'finished' => 0]
);

foreach ($workflow->steps as $step) {
    $stepsmeta[] = [
        'id' => $step->id,
        'title' => $step->title,
        'description' => $step->description,
        'sort' => $step->sort,
        'isfirst' => $step->sort === 1,
        'islast' => $step->sort === count($workflow->steps),
        'isvalid' => $step->is_valid(),
        'processes' => $processesmeta[$step->id],
        'filters' => array_map(fn ($filter) => [
            'id' => $filter->id,
            'name' => $filter->get_plugin_name(),
            'title' => $filter->get_instance_title(),
            'details' => $filter->get_instance_details(),
            'isvalid' => $filter->is_valid(),
            'iconclass' => $filter::get_icon_class(),
            'urls' => [
                'edit' => (new moodle_url('/admin/tool/userautodelete/managefilter.php', [
                    'id' => $filter->id,
                    'returnurl' => $PAGE->url->out_as_local_url(true),
                ]))->out(false),
                'delete' => (new moodle_url('/admin/tool/userautodelete/managefilter.php', [
                    'id' => $filter->id,
                    'action' => 'delete',
                    'returnurl' => $PAGE->url->out_as_local_url(true),
                ]))->out(false),
            ],
        ], $step->filters),
        'actions' => array_map(fn ($action) => [
            'id' => $action->id,
            'name' => $action->get_plugin_name(),
            'title' => $action->get_instance_title(),
            'details' => $action->get_instance_details(),
            'isvalid' => $action->is_valid(),
            'iconclass' => $action::get_icon_class(),
            'urls' => [
                'edit' => (new moodle_url('/admin/tool/userautodelete/manageaction.php', [
                    'id' => $action->id,
                    'returnurl' => $PAGE->url->out_as_local_url(true),
                ]))->out(false),
                'delete' => (new moodle_url('/admin/tool/userautodelete/manageaction.php', [
                    'id' => $action->id,
                    'action' => 'delete',
                    'returnurl' => $PAGE->url->out_as_local_url(true),
                ]))->out(false),
            ],
        ], $step->actions),
        'urls' => [
            'moveup' => (new moodle_url('/admin/tool/userautodelete/managestep.php', [
                'id' => $step->id,
                'action' => 'moveup',
                'returnurl' => $PAGE->url->out_as_local_url(true),
            ]))->out(false),
            'movedown' => (new moodle_url('/admin/tool/userautodelete/managestep.php', [
                'id' => $step->id,
                'action' => 'movedown',
                'returnurl' => $PAGE->url->out_as_local_url(true),
            ]))->out(false),
            'edit' => (new moodle_url('/admin/tool/userautodelete/managestep.php', [
                'id' => $step->id,
                'action' => 'edit',
                'returnurl' => $PAGE->url->out_as_local_url(true),
            ]))->out(false),
            'delete' => (new moodle_url('/admin/tool/userautodelete/managestep.php', [
                'id' => $step->id,
                'action' => 'delete',
                'returnurl' => $PAGE->url->out_as_local_url(true),
            ]))->out(false),
        ],
    ];
}

// Load available sub-plugins if editing is enabled.
if ($isediting) {
    $plugman = core_plugin_manager::instance();
    $availablefilters = array_reduce(
        array_keys($plugman->get_installed_plugins('userdeletefilter')),
        function ($res, $filter): array {
            global $PAGE;

            /** @var userdeletefilter $subplugincls */
            $subplugincls = plugin_util::get_subplugin_class('userdeletefilter', $filter);
            $res[] = [
                'pluginname' => $filter,
                'displayname' => get_string('pluginname', 'userdeletefilter_' . $filter),
                'iconclass' => $subplugincls::get_icon_class(),
                'addurlbase' => (new moodle_url('/admin/tool/userautodelete/managefilter.php', [
                    'action' => 'add',
                    'pluginname' => $filter,
                    'returnurl' => $PAGE->url->out_as_local_url(true),
                ]))->out(false),
            ];
            return $res;
        },
        []
    );

    $availableactions = array_reduce(
        array_keys($plugman->get_installed_plugins('userdeleteaction')),
        function ($res, $action): array {
            global $PAGE;

            /** @var userdeleteaction $subplugincls */
            $subplugincls = plugin_util::get_subplugin_class('userdeleteaction', $action);
            $res[] = [
                'pluginname' => $action,
                'displayname' => get_string('pluginname', 'userdeleteaction_' . $action),
                'iconclass' => $subplugincls::get_icon_class(),
                'addurlbase' => (new moodle_url('/admin/tool/userautodelete/manageaction.php', [
                    'action' => 'add',
                    'pluginname' => $action,
                    'returnurl' => $PAGE->url->out_as_local_url(true),
                ]))->out(false),
            ];
            return $res;
        },
        []
    );
}

// Render main output.
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('tool_userautodelete/workflow', [
    'id' => $workflow->id,
    'title' => $workflow->title,
    'description' => $workflow->description,
    'sort' => $workflow->sort,
    'active' => $workflow->active,
    'isvalid' => $workflow->is_valid(),
    'timecreated' => $workflow->timecreated,
    'timemodified' => $workflow->timemodified,
    'createdby' => [
        'id' => $workflow->createdby,
        'fullname' => fullname(core_user::get_user($workflow->createdby)),
        'profileurl' => (new moodle_url('/user/profile.php', ['id' => $workflow->createdby]))->out(false),
    ],
    'modifiedby' => [
        'id' => $workflow->modifiedby,
        'fullname' => fullname(core_user::get_user($workflow->modifiedby)),
        'profileurl' => (new moodle_url('/user/profile.php', ['id' => $workflow->modifiedby]))->out(false),
    ],
    'stepcount' => count($stepsmeta),
    'steps' => $stepsmeta,
    'processes' => $globalprocessmeta,
    'availablefilters' => isset($availablefilters) ? base64_encode(json_encode($availablefilters)) : null,
    'availableactions' => isset($availableactions) ? base64_encode(json_encode($availableactions)) : null,
    'isediting' => $isediting,
    'canbeactivated' => false, // TODO (MDL-0): Create check if a workflow is valid.
    'urls' => [
        'activate' => (new moodle_url(
            '/admin/tool/userautodelete/manageworkflow.php',
            [
                'id' => $workflow->id,
                'action' => $workflow->active ? 'disable' : 'enable',
            ]
        ))->out(false),
        'addstep' => (new moodle_url(
            '/admin/tool/userautodelete/manageworkflow.php',
            ['id' => $workflow->id, 'action' => 'addstep', 'returnurl' => $PAGE->url->out_as_local_url(true)]
        ))->out(false),
        'delete' => (new moodle_url(
            '/admin/tool/userautodelete/workflow.php',
            ['id' => $workflow->id, 'action' => 'TODO'] // TODO (MDL-0): Implement.
        ))->out(false),
        'edit' => (new moodle_url(
            '/admin/tool/userautodelete/workflow.php',
            [
                'id' => $workflow->id,
                'action' => $isediting ? '' : 'edit',
            ]
        ))->out(false),
        'back' => (new moodle_url('/admin/tool/userautodelete/workflows.php'))->out(false),
    ],
]);
echo $OUTPUT->footer();
