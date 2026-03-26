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
 * Log inspection page
 *
 * @package     tool_userautodelete
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

global $DB, $OUTPUT, $PAGE, $USER;

require_admin();

// Setup page.
$url = new moodle_url('/admin/tool/userautodelete/log.php');
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('page_title_log', 'tool_userautodelete'));
$PAGE->set_heading(get_string('pluginname', 'tool_userautodelete'));
$PAGE->set_url($url);

// Render main output.
echo $OUTPUT->header();
echo "TODO ;)";
echo $OUTPUT->footer();
