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
 * Tests for the log_table class
 *
 * @package   tool_userautodelete
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\output;


/**
 * Tests for the log_table class
 *
 * This suite basically just runs through all methods of the log_table class
 * to check if they throw warnings or errors on specific PHP / Moodle versions.
 */
final class log_table_test extends \advanced_testcase {

    /**
     * Tests that the log table displays a message when no log data is present.
     *
     * @covers \tool_userautodelete\output\log_table
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_render_empty_table(): void {
        // Generate table without any log data being present.
        $table = new log_table('testlogtable');
        $table->define_baseurl(new \moodle_url('/'));
        ob_start();
        $table->out(10, true);
        $html = ob_get_contents();
        ob_end_clean();

        // Check that the table is rendered correctly.
        $this->assertStringContainsString(get_string('nothingtodisplay'), $html);
    }

    /**
     * Tests that the log table displays data when log entries are present.
     *
     * @covers \tool_userautodelete\output\log_table
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_render_table_with_data(): void {
        global $DB;
        $this->resetAfterTest();

        // Create some dummy log data.
        $DB->insert_records('tool_userautodelete_log', [
            [
                'runtime' => time(),
                'recovered' => 5,
                'warned' => 3,
                'deleted' => 2,
            ],
            [
                'runtime' => time() - 3600,
                'recovered' => 1234567890,
                'warned' => 1,
                'deleted' => 0,
            ],
        ]);

        // Generate table with some log data.
        $table = new log_table('testlogtable');
        $table->define_baseurl(new \moodle_url('/'));
        ob_start();
        $table->out(10, true);
        $html = ob_get_contents();
        ob_end_clean();

        // Check that the table is rendered correctly.
        $this->assertStringNotContainsString(get_string('nothingtodisplay'), $html);
        $this->assertStringContainsString(1234567890, $html);
    }

}
