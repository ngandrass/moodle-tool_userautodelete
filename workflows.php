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
 * Workflow overview page.
 *
 * @package     tool_userautodelete
 * @copyright   2025 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_userautodelete\output\workflow_table;
use tool_userautodelete\workflow;

require_once(__DIR__ . '/../../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

global $CFG, $DB, $OUTPUT, $PAGE, $USER;

// Login and capability checks as well as $PAGE setup are performed by admin_externalpage_setup.
admin_externalpage_setup('tool_userautodelete_workflows');

// Render main output.
$workflowtable = new workflow_table('workflowtable');
$workflowtable->define_baseurl(new moodle_url('/admin/tool/userautodelete/workflows.php'));

echo $OUTPUT->header();
$workflowtable->out(9999, false);

$workflows = workflow::get_all();
foreach ($workflows as $workflow) {
    echo html_writer::tag('h2', format_string($workflow->title));
    echo html_writer::tag('p', format_text($workflow->description));

    foreach ($workflow->steps as $id => $step) {
        echo html_writer::tag('h3', "Step {$id}");

        echo "<p><b>Filters: </b><ul>";
        foreach ($step->filters as $filter) {
            echo "<li>" . $filter::get_plugin_name() . " #" . $filter->id . "</li>";
        }
        echo "</ul></p>";

        echo "<p><b>Actions: </b><ul>";
        foreach ($step->actions as $action) {
            echo "<li>" . $action::get_plugin_name() . " #" . $action->id . "</li>";
        }
        echo "</ul></p>";
    }
}

echo $OUTPUT->footer();
