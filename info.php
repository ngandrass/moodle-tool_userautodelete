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
 * Info page
 *
 * @package     tool_userautodelete
 * @copyright   2025 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_userautodelete\manager;

require_once(__DIR__ . '/../../../config.php');

global $DB, $OUTPUT, $PAGE, $USER;

// Only allow site admins to access this page.
require_login();
$ctx = context_system::instance();
require_capability('moodle/site:config', $ctx);

// Setup page.
$PAGE->set_context($ctx);
$PAGE->set_title(get_string('page_title_dryrun', 'tool_userautodelete'));
$PAGE->set_heading(get_string('pluginname', 'tool_userautodelete'));
$PAGE->set_url(new moodle_url('/admin/tool/userautodelete/info.php'));

// Prepare template context and manager.
$tplctx = [];
$manager = new manager();
if (!$manager->validate_config()) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('error_config', 'tool_userautodelete'), 'error');
    echo $OUTPUT->footer();
    exit;
}

// Prepare: Users to warn.
if ($manager->get_config('warning_email_enable')) {
    $userstowarn = ['users' => array_values($manager->get_users_to_warn())];
    $tplctx['userstowarn'] = [
        'users' => $userstowarn['users'],
        'count' => count($userstowarn['users']),
    ];
} else {
    $tplctx['userstowarn'] = null;
}

// Prepare: Users to delete.
$userstodelete = ['users' => array_values($manager->get_users_to_delete())];
$tplctx['userstodelete'] = [
    'users' => $userstodelete['users'],
    'count' => count($userstodelete['users']),
];

// Config: Plugin enabled.
$tplctx['pluginenabled'] = $manager->get_config('enable');

// Config: Deletion threshold.
$tplctx['deletethreshold'] = [
    'days' => $manager->get_config('delete_threshold_days'),
    'cutoff' => time() - ($manager->get_config('delete_threshold_days') * DAYSECS),
];

// Config: Warning threshold.
if ($manager->get_config('warning_email_enable')) {
    $tplctx['warningthreshold'] = [
        'days' => $manager->get_config('warning_threshold_days'),
        'cutoff' => time() - (
            $manager->get_config('delete_threshold_days') - $manager->get_config('warning_threshold_days')
        ) * DAYSECS,
    ];
} else {
    $tplctx['warningthreshold'] = null;
}

// Config: Next task execution.
$checktask = \core\task\manager::get_scheduled_task('tool_userautodelete\task\check_and_delete_users');
if ($checktask->is_enabled()) {
    $tplctx['nextcheck'] = [
        'absolute' => $checktask->get_next_run_time(),
        'relative' => format_time($checktask->get_next_run_time() - time()),
    ];
} else {
    $tplctx['nextcheck'] = null;
}

// Config: Ignored roles.
$tplctx['ignoredroles'] = [
    get_string('siteadministrators', 'role'),
    get_string('guestuser'),
];
if ($ignoredroleids = $manager->get_ignored_role_ids()) {
    $roles = get_all_roles();
    $ignoredroles = array_filter($roles, fn ($role) => in_array($role->id, $ignoredroleids));
    $rolenames = role_fix_names($ignoredroles, null, ROLENAME_ORIGINAL, true);
    $tplctx['ignoredroles'] = array_values(array_merge($tplctx['ignoredroles'], $rolenames));
}

// Config: Ignored auths.
$tplctx['ignoredauths'] = array_map(
    fn($auth) => get_string('pluginname', "auth_{$auth}"),
    $manager->get_ignored_auths()
);

// Render main output.
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('tool_userautodelete/dryrun', array_merge($tplctx, [
    'url' => [
        'back' => new moodle_url('/admin/settings.php', ['section' => 'tool_userautodelete_settings']),
        'userprofilebase' => new moodle_url('/user/profile.php'),
    ],
]));
echo $OUTPUT->footer();
