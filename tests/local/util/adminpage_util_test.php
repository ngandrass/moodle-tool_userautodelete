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
 * Tests for adminpage_util class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\local\util;

use navigation_node;

/**
 * Tests for adminpage_util class
 */
final class adminpage_util_test extends \advanced_testcase {
    /**
     * Tests hidden admin page setup without navigation registration.
     *
     * @covers \tool_userautodelete\local\util\adminpage_util
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_admin_hidden_externalpage_setup_without_parent(): void {
        global $PAGE, $SITE;

        $this->resetAfterTest();
        $this->setAdminUser();

        $section = 'tool_userautodelete_hidden_test_without_parent';
        $title = 'Hidden page title';
        $url = new \moodle_url('/admin/tool/userautodelete/workflow.php', ['query' => 'abc']);

        adminpage_util::admin_hidden_externalpage_setup($section, $title, $url, null);

        $this->assertSame(\context_system::instance()->id, $PAGE->context->id, 'Page context should be system context');
        $this->assertSame('admin', $PAGE->pagelayout, 'Page layout should be admin');
        $this->assertStringStartsWith($title, $PAGE->title, 'Page title is incorrect');
        $this->assertSame($SITE->fullname, $PAGE->heading, 'Page heading should match site fullname');
        $this->assertSame($url->out(false), $PAGE->url->out(false), 'Page URL is incorrect');

        $this->assertFalse(
            $PAGE->settingsnav->find($section, navigation_node::TYPE_SETTING),
            'Hidden page should not be added to navigation when parent is null'
        );
    }

    /**
     * Tests hidden admin page setup with invalid parent section.
     *
     * @covers \tool_userautodelete\local\util\adminpage_util
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_admin_hidden_externalpage_setup_invalid_parent_throws(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->expectException(\coding_exception::class);
        adminpage_util::admin_hidden_externalpage_setup(
            'tool_userautodelete_hidden_invalid_parent',
            'Hidden page title',
            new \moodle_url('/admin/tool/userautodelete/workflow.php'),
            'non_existing_parent_section'
        );
    }

    /**
     * Tests hidden admin page setup with valid parent section registration.
     *
     * @covers \tool_userautodelete\local\util\adminpage_util
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_admin_hidden_externalpage_setup_registers_navigation_node(): void {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();

        $section = 'tool_userautodelete_hidden_test_with_parent';
        $title = 'Hidden page with parent';
        $url = new \moodle_url('/admin/tool/userautodelete/workflow.php', ['id' => 123]);

        adminpage_util::admin_hidden_externalpage_setup($section, $title, $url, 'tool_userautodelete_workflows');

        $node = $PAGE->settingsnav->find($section, navigation_node::TYPE_SETTING);
        $this->assertInstanceOf(navigation_node::class, $node, 'Navigation node should be registered under the given parent');
        $this->assertSame($title, (string) $node->text, 'Registered navigation node title is incorrect');
    }
}
