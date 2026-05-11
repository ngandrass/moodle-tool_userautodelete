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
 * Tests for plugin_util class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\local\util;

/**
 * Tests for plugin_util class
 */
final class plugin_util_test extends \advanced_testcase {
    /**
     * Tests that known sub-plugins resolve to their expected class names.
     *
     * @covers \tool_userautodelete\local\util\plugin_util
     *
     * @return void
     * @throws \moodle_exception
     */
    public function test_get_subplugin_class_for_known_plugins(): void {
        $this->assertSame(
            '\\userdeletefilter_suspension\\userdeletefilter',
            plugin_util::get_subplugin_class('userdeletefilter', 'suspension'),
            'Unexpected class name returned for suspension filter sub-plugin'
        );

        $this->assertSame(
            '\\userdeleteaction_suspend\\userdeleteaction',
            plugin_util::get_subplugin_class('userdeleteaction', 'suspend'),
            'Unexpected class name returned for suspend action sub-plugin'
        );
    }

    /**
     * Tests that requesting an unknown sub-plugin throws an exception.
     *
     * @covers \tool_userautodelete\local\util\plugin_util
     *
     * @return void
     */
    public function test_get_subplugin_class_unknown_plugin_throws(): void {
        $this->expectException(\moodle_exception::class);
        plugin_util::get_subplugin_class('userdeletefilter', 'does_not_exist');
    }

    /**
     * Tests that installed plugins without the expected class trigger an error.
     *
     * @covers \tool_userautodelete\local\util\plugin_util
     *
     * @return void
     */
    public function test_get_subplugin_class_missing_expected_class_throws(): void {
        $this->expectException(\moodle_exception::class);
        plugin_util::get_subplugin_class('mod', 'assign');
    }
}
