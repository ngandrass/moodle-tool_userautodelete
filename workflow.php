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
 * Manages a single workflow.
 *
 * @package     tool_userautodelete
 * @copyright   2025 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_userautodelete\local\type\sort_move_direction;
use tool_userautodelete\workflow;

require_once(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

global $CFG, $DB, $OUTPUT, $PAGE, $USER;

// Login and capability checks as well as $PAGE setup are performed by admin_externalpage_setup.
admin_externalpage_setup('tool_userautodelete_workflows');

// Request parameters.
$workflowid = required_param('id', PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);
$returnurl = optional_param('returnurl', null, PARAM_RAW);

// Get requested workflow.
$workflow = workflow::get_by_id($workflowid);

// Handle actions.
if ($action === 'moveup') {
    $workflow->move(sort_move_direction::UP);
    if ($returnurl) {
        redirect($returnurl);
    }
}
if ($action === 'movedown') {
    $workflow->move(sort_move_direction::DOWN);
    if ($returnurl) {
        redirect($returnurl);
    }
}

// Render main output.
echo $OUTPUT->header();
echo "nothing to see here yet ...";
echo $OUTPUT->footer();
