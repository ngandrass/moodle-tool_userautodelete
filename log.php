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
 * Action log inspection page
 *
 * @package     tool_userautodelete
 * @copyright   2025 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_userautodelete\output\log_table;

require_once(__DIR__ . '/../../../config.php');

global $DB, $OUTPUT, $PAGE, $USER;

// Only allow site admins to access this page.
require_login();
$ctx = context_system::instance();
require_capability('moodle/site:config', $ctx);

// Setup page.
$url = new moodle_url('/admin/tool/userautodelete/log.php');

$PAGE->set_context($ctx);
$PAGE->set_title(get_string('page_title_action_log', 'tool_userautodelete'));
$PAGE->set_heading(get_string('pluginname', 'tool_userautodelete'));
$PAGE->set_url($url);

// Prepate log table.
$logtable = new log_table('logtable');
$logtable->define_baseurl($url);

ob_start();
$logtable->out(50, true);
$logtablehtml = ob_get_contents();
ob_end_clean();

// Build template context.
$manager = new \tool_userautodelete\manager();
$tplctx = [
    'logtablehtml' => $logtablehtml,
    'pluginenabled' => $manager->get_config('enable'),
    'url' => [
        'back' => new \moodle_url('/admin/settings.php', [
            'section' => 'tool_userautodelete_settings',
        ]),
        'tasklogs' => new moodle_url('/admin/tasklogs.php', [
            'filter' => 'tool_userautodelete\task\check_and_delete_users',
        ]),
    ],
];

$checktask = \core\task\manager::get_scheduled_task('tool_userautodelete\task\check_and_delete_users');
if ($checktask->is_enabled()) {
    $tplctx['nextcheck'] = [
        'absolute' => $checktask->get_next_run_time(),
        'relative' => format_time($checktask->get_next_run_time() - time()),
    ];
} else {
    $tplctx['nextcheck'] = null;
}

if ($lastcheck = $checktask->get_last_run_time()) {
    $tplctx['lastcheck'] = [
        'absolute' => $lastcheck,
        'relative' => format_time(time() - $lastcheck),
    ];
} else {
    $tplctx['lastcheck'] = null;
}


// Render main output.
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('tool_userautodelete/log', $tplctx);
echo $OUTPUT->footer();
