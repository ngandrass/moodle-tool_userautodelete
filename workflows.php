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
 * Workflows overview page.
 *
 * Lists all existing workflows with some metadata.
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_userautodelete\process;
use tool_userautodelete\workflow;

require_once(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

global $CFG, $DB, $OUTPUT, $PAGE, $USER;

// Login and capability checks as well as $PAGE setup are performed by admin_externalpage_setup.
admin_externalpage_setup('tool_userautodelete_workflows');

// Generate context data for page template.
$workflows = workflow::get_all();
$workflowmeta = [];
$activeworkflowsexist = false;
foreach ($workflows as $workflow) {
    $activeworkflowsexist |= $workflow->active;

    // Calculate process stats for this workflow.
    $processmeta = array_reduce(
        process::get_process_stats_for_workflow($workflow->id),
        function ($carry, $item) {
            $carry['active'] += $item->active;
            $carry['finished'] += $item->finished;
            $carry['steps'] += 1;
            return $carry;
        },
        ['active' => 0, 'finished' => 0, 'steps' => 0]
    );

    // Ingest metadata for current workflow.
    $workflowmeta[] = [
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
        'processes' => $processmeta,
        'canmoveup' => count($workflows) > 1 && $workflow->sort > 1,
        'canmovedown' => count($workflows) > 1 && $workflow->sort < count($workflows),
        'urls' => [
            'show' => new moodle_url(
                '/admin/tool/userautodelete/workflow.php',
                ['id' => $workflow->id]
            ),
            'delete' => new moodle_url(
                '/admin/tool/userautodelete/manageworkflow.php',
                [
                    'id' => $workflow->id,
                    'action' => 'delete',
                    'returnurl' => $PAGE->url->out_as_local_url(true),
                ]
            ),
            'moveup' => new moodle_url(
                '/admin/tool/userautodelete/manageworkflow.php',
                ['id' => $workflow->id, 'action' => 'moveup', 'returnurl' => $PAGE->url->out_as_local_url(true)],
            ),
            'movedown' => new moodle_url(
                '/admin/tool/userautodelete/manageworkflow.php',
                ['id' => $workflow->id, 'action' => 'movedown', 'returnurl' => $PAGE->url->out_as_local_url(true)],
            ),
        ],
    ];
}

$tplctx = [
    'workflows' => $workflowmeta,
    'urls' => [
        'add' => new moodle_url(
            '/admin/tool/userautodelete/manageworkflow.php',
            ['action' => 'add']
        ),
        'adddefault' => new moodle_url(
            '/admin/tool/userautodelete/manageworkflow.php',
            ['action' => 'add', 'loaddefault' => 1]
        ),
    ],
];

// Add warning about globally disabled plugin.
$notificationshtml = '';
if ($activeworkflowsexist && !get_config('tool_userautodelete', 'enable')) {
    $settingsurl = new moodle_url('/admin/settings.php', ['section' => 'tool_userautodelete_settings']);
    $notificationshtml = $OUTPUT->notification(
        get_string('workflows_plugin_disabled_warning', 'tool_userautodelete', $settingsurl->out(false)),
        \core\output\notification::NOTIFY_WARNING,
        false,
    );
}

// Render main output.
echo $OUTPUT->header();
echo $notificationshtml;
echo $OUTPUT->render_from_template(
    count($workflows) > 0 ? 'tool_userautodelete/workflows' : 'tool_userautodelete/workflows_empty',
    $tplctx
);
echo $OUTPUT->footer();
