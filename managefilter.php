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
 * Collection of filter management operations
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_userautodelete\step;
use tool_userautodelete\userdeletefilter;

require_once(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

global $CFG, $DB, $OUTPUT, $PAGE, $SITE, $USER;

require_admin();

// Request parameters.
$action = required_param('action', PARAM_ALPHA);
$returnurl = required_param('returnurl', PARAM_RAW);
$filterid = optional_param('id', null, PARAM_INT);
$stepid = optional_param('stepid', null, PARAM_INT);

// Setup page as sub-admin page of workflows overview.
// This does not use admin_externalpage_setup as we do not want these detail
// pages to be accessible via the default admin navigation tree since these
// always require valid parameters.
$PAGE->set_url(new moodle_url('/admin/tool/userautodelete/managefilter.php', [
    'action' => $action,
    'id' => $filterid,
    'stepid' => $stepid,
]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('manage_workflow', 'tool_userautodelete'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('admin');

navigation_node::require_admin_tree();
$parentnavnode = $PAGE->settingsnav->find('tool_userautodelete_workflows', navigation_node::TYPE_SETTING);
$navnode = $parentnavnode->add(
    get_string('manage_workflow', 'tool_userautodelete'),
    new moodle_url('/admin/tool/userautodelete/managefilter.php', ['id' => $filterid, 'stepid' => $stepid])
);
$navnode->make_active();
$PAGE->navigation->clear_cache();

$PAGE->add_header_action($OUTPUT->render_from_template('core_admin/header_search_input', [
    'action' => new moodle_url('/admin/search.php'),
    'query' => $PAGE->url->get_param('query'),
]));

// Handle actions.
$output = '';
if ($action == 'add') {
    $step = step::get_by_id($stepid);
    userdeletefilter::create_instance(
        step: $step,
        pluginname: required_param('pluginname', PARAM_TEXT),
    );
} else if ($action == 'delete') {
    $filter = userdeletefilter::get_instance_by_id($filterid);
    $filter->delete();
} else {
    throw new moodle_exception('invalid_action', 'tool_userautodelete');
}

// Handle redirects.
if (!$output) {
    redirect($returnurl);
}

// Output page if not redirected.
echo $OUTPUT->header();
echo $output;
echo $OUTPUT->footer();
