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
require_once("{$CFG->libdir}/adminlib.php");

global $OUTPUT, $PAGE;

// Login and capability checks as well as $PAGE setup are performed by admin_externalpage_setup.
admin_externalpage_setup('tool_userautodelete_log');

// TODO: Add filter bar
$logtbl = new \tool_userautodelete\output\log_table('logtable', null, null);
$logtbl->define_baseurl($PAGE->url);

// Render main output.
echo $OUTPUT->header();
// TODO: Add heading and description
$logtbl->out(50, false);
echo $OUTPUT->footer();
