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
 * Lists users that would be ingested next by a given workflow.
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_userautodelete\local\util\adminpage_util;
use tool_userautodelete\output\dryrun_users_table;
use tool_userautodelete\workflow;

require_once(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

global $CFG, $DB, $OUTPUT, $PAGE, $SITE, $USER;

require_admin();

// Request parameters.
$workflowid = required_param('workflowid', PARAM_INT);

// Setup page as sub-admin page of workflows overview.
// This does not use admin_externalpage_setup as we do not want these detail
// pages to be accessible via the default admin navigation tree since these
// always require valid parameters.
adminpage_util::admin_hidden_externalpage_setup(
    section: 'tool_userautodelete_workflow_dryrun',
    title: get_string('dry_run', 'tool_userautodelete'),
    url: new moodle_url('/admin/tool/userautodelete/dryrun.php', ['workflowid' => $workflowid]),
    parentsection: 'tool_userautodelete_workflows'
);

// Get requested workflow and prepare table.
$workflow = workflow::get_by_id($workflowid);

if (!$workflow->is_valid()) {
    throw new \moodle_exception('can_not_dryrun_invalid_workflow', 'tool_userautodelete');
}

$ingressfilters = $workflow->steps[0]->filters;

$dryruntable = new dryrun_users_table("dryrun-{$workflowid}", $workflow);
$dryruntable->define_baseurl($PAGE->url);
ob_start();
$dryruntable->out(25, true);
$dryruntablehtml = ob_get_contents();
ob_end_clean();

// Render main output.
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('tool_userautodelete/dryrun', [
    'id' => $workflow->id,
    'title' => $workflow->title,
    'description' => $workflow->description,
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
    'ingressfilters' => array_map(fn ($filter) => $filter->get_instance_title(), $ingressfilters),
    'ingressfilterscount' => count($ingressfilters),
    'affecteduserscount' => $workflow->get_applicable_users_count(),
    'dryruntablehtml' => $dryruntablehtml,
    'urls' => [
        'back' => (new moodle_url(
            '/admin/tool/userautodelete/workflow.php',
            ['id' => $workflowid, 'action' => 'edit']
        ))->out(false),
    ],
]);
echo $OUTPUT->footer();
