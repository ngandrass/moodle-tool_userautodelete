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
 * Tests for the manager class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;


/**
 * Tests for the manager class
 */
final class manager_test extends \advanced_testcase {
    /**
     * This method is called before each test.
     */
    protected function setUp(): void {
        parent::setUp();

        // Disable logger by default.
        logger::disable();

        // Enable plugin by default.
        set_config('enable', true, 'tool_userautodelete');
    }

    /**
     * Tests that no workflows are executed when the plugin is disabled
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_prevent_run_if_disabled(): void {
        $this->resetAfterTest();
        logger::enable();
        set_config('enable', false, 'tool_userautodelete');

        $manager = new manager();
        $res = $manager->execute();

        $this->expectOutputString('[INFO] ' . get_string('plugin_disabled_skipping_execution', 'tool_userautodelete') . "\n");
        $this->assertSame(false, $res, 'Execution was not prevented');
    }
}
