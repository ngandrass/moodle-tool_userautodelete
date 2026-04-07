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
 * Basic tests for plugininfo userdeleteaction class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\plugininfo;

use tool_userautodelete\step;
use tool_userautodelete\workflow;

/**
 * Basic tests for plugininfo userdeleteaction class
 */
final class userdeleteaction_test extends \advanced_testcase {
    /**
     * Tests that this plugintype does not support disabling.
     *
     * @covers \tool_userautodelete\plugininfo\userdeleteaction
     *
     * @return void
     */
    public function test_basic_contract(): void {
        $this->assertFalse(
            userdeleteaction::plugintype_supports_disabling(),
            'Action plugintype should not support disabling'
        );
    }

    /**
     * Tests uninstall eligibility for action subplugins.
     *
     * @covers \tool_userautodelete\plugininfo\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_is_uninstall_allowed(): void {
        $this->resetAfterTest();

        // Uninstall must be allowed as long as the action subplugin is unused.
        $plugininfo = \core_plugin_manager::instance()->get_plugin_info('userdeleteaction_suspend');
        $this->assertInstanceOf(userdeleteaction::class, $plugininfo);
        $this->assertTrue($plugininfo->is_uninstall_allowed(), 'Uninstall without instances should be allowed');

        // Once an action instance exists, uninstall must be blocked.
        $workflow = workflow::create('Action workflow', 'Test workflow');
        $step = step::create($workflow, 'Step 1', '');
        \tool_userautodelete\userdeleteaction::create_instance($step, 'suspend');

        $plugininfo = \core_plugin_manager::instance()->get_plugin_info('userdeleteaction_suspend');
        $this->assertInstanceOf(userdeleteaction::class, $plugininfo);
        $this->assertFalse($plugininfo->is_uninstall_allowed(), 'Uninstall with instances should be prohibited');
    }
}
