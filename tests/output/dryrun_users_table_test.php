<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for the dryrun users table renderer
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\output;

/**
 * Tests for the dryrun users table renderer
 */
final class dryrun_users_table_test extends \advanced_testcase {
    /**
     * Tests dryrun table rendering output end-to-end.
     *
     * @covers \tool_userautodelete\output\dryrun_users_table
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_rendered_table_html_contains_expected_output(): void {
        global $DB;

        $this->resetAfterTest();

        // Prepare workflow fixture and one matching user row for the dryrun query.
        /** @var \tool_userautodelete_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_userautodelete');
        $workflow = $generator->create_simple_suspend_workflow('Workflow', 'Description', true);

        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'username' => 'jdoe',
            'suspended' => 1,
            'auth' => 'manual',
        ]);
        $DB->set_field('user', 'lastaccess', 1700000000, ['id' => $user->id]);

        // Render table and collect HTML output.
        $table = new dryrun_users_table('dryrun-users-render', $workflow);
        $table->define_baseurl(new \moodle_url('/admin/tool/userautodelete/dryrun.php', ['id' => $workflow->id]));

        ob_start();
        $table->out(20, false);
        $html = (string) ob_get_clean();

        // Validate that key table headers and user row values are present.
        $this->assertStringContainsString(get_string('id', 'tool_userautodelete'), $html, 'ID header is missing');
        $this->assertStringContainsString(get_string('user'), $html, 'User header is missing');
        $this->assertStringContainsString(get_string('lastaccess'), $html, 'Last access header is missing');
        $this->assertStringContainsString(get_string('status'), $html, 'Status header is missing');
        $this->assertStringContainsString(get_string('type_auth', 'plugin'), $html, 'Auth header is missing');

        $this->assertStringContainsString('/user/profile.php?id=' . $user->id, $html, 'Profile URL is missing');
        $this->assertStringContainsString('jdoe', $html, 'Username is missing from rendered table');
        $this->assertStringContainsString(userdate(1700000000), $html, 'Last access value is missing or incorrectly formatted');
        $this->assertStringContainsString('badge-danger', $html, 'Suspended badge class is missing');
        $this->assertStringContainsString(get_string('suspended'), $html, 'Suspended status label is missing');
        $this->assertStringContainsString(get_string('pluginname', 'auth_manual'), $html, 'Auth plugin label is missing');
    }
}
