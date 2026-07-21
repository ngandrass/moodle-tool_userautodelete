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
 * Tests for the instance_setting_descriptor class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\local\type;

use core\lang_string;

/**
 * Tests for the instance_setting_descriptor class
 */
final class instance_setting_descriptor_test extends \advanced_testcase {
    /**
     * Tests that a simple text descriptor is constructed without exceptions and
     * exposes the correct property values.
     *
     * @covers \tool_userautodelete\local\type\instance_setting_descriptor
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_constructor_accepts_simple_text_descriptor(): void {
        $descriptor = new instance_setting_descriptor(
            key: 'mykey',
            title: new lang_string('pluginname', 'tool_userautodelete'),
            type: PARAM_TEXT,
        );

        $this->assertSame('mykey', $descriptor->key);
        $this->assertSame('text', $descriptor->mformtype);
        $this->assertNull($descriptor->ajax);
        $this->assertNull($descriptor->choicesresolver);
        $this->assertNull($descriptor->choices);
    }

    /**
     * Tests that an AJAX-backed autocomplete descriptor is constructed without exceptions.
     *
     * @covers \tool_userautodelete\local\type\instance_setting_descriptor
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_constructor_accepts_ajax_autocomplete_descriptor(): void {
        $resolver = fn(array $ids): array => [];
        $descriptor = new instance_setting_descriptor(
            key: 'mykey',
            title: new lang_string('pluginname', 'tool_userautodelete'),
            type: PARAM_TEXT,
            mformtype: 'autocomplete',
            ajax: 'myplugin/myselector',
            choicesresolver: $resolver,
        );

        $this->assertSame('autocomplete', $descriptor->mformtype);
        $this->assertSame('myplugin/myselector', $descriptor->ajax);
        $this->assertSame($resolver, $descriptor->choicesresolver);
    }

    /**
     * Tests that an AJAX-backed autocomplete-multi descriptor is constructed without exceptions.
     *
     * @covers \tool_userautodelete\local\type\instance_setting_descriptor
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_constructor_accepts_ajax_autocomplete_multi_descriptor(): void {
        $resolver = fn(array $ids): array => [];
        $descriptor = new instance_setting_descriptor(
            key: 'mykey',
            title: new lang_string('pluginname', 'tool_userautodelete'),
            type: PARAM_TEXT,
            mformtype: 'autocomplete-multi',
            ajax: 'myplugin/myselector',
            choicesresolver: $resolver,
        );

        $this->assertSame('autocomplete-multi', $descriptor->mformtype);
        $this->assertSame('myplugin/myselector', $descriptor->ajax);
        $this->assertSame($resolver, $descriptor->choicesresolver);
    }

    /**
     * Tests that a non-AJAX autocomplete descriptor with a choices list is
     * constructed without exceptions.
     *
     * @covers \tool_userautodelete\local\type\instance_setting_descriptor
     *
     * @return void
     * @throws \coding_exception
     */
    public function test_constructor_accepts_non_ajax_autocomplete_with_choices(): void {
        $descriptor = new instance_setting_descriptor(
            key: 'mykey',
            title: new lang_string('pluginname', 'tool_userautodelete'),
            type: PARAM_TEXT,
            choices: ['a' => 'Option A', 'b' => 'Option B'],
            mformtype: 'autocomplete',
        );

        $this->assertSame(['a' => 'Option A', 'b' => 'Option B'], $descriptor->choices);
        $this->assertNull($descriptor->ajax);
        $this->assertNull($descriptor->choicesresolver);
    }

    /**
     * Tests that passing ajax with a non-autocomplete mformtype throws a coding_exception.
     *
     * @covers \tool_userautodelete\local\type\instance_setting_descriptor
     *
     * @return void
     */
    public function test_constructor_throws_for_ajax_on_non_autocomplete_mformtype(): void {
        $this->expectException(\coding_exception::class);

        new instance_setting_descriptor(
            key: 'mykey',
            title: new lang_string('pluginname', 'tool_userautodelete'),
            type: PARAM_TEXT,
            mformtype: 'text',
            ajax: 'myplugin/myselector',
            choicesresolver: fn(array $ids): array => [],
        );
    }

    /**
     * Tests that setting ajax without a choicesresolver throws a coding_exception.
     *
     * @covers \tool_userautodelete\local\type\instance_setting_descriptor
     *
     * @return void
     */
    public function test_constructor_throws_for_ajax_without_choicesresolver(): void {
        $this->expectException(\coding_exception::class);

        new instance_setting_descriptor(
            key: 'mykey',
            title: new lang_string('pluginname', 'tool_userautodelete'),
            type: PARAM_TEXT,
            mformtype: 'autocomplete',
            ajax: 'myplugin/myselector',
            choicesresolver: null,
        );
    }

    /**
     * Tests that passing a choicesresolver without ajax throws a coding_exception.
     *
     * @covers \tool_userautodelete\local\type\instance_setting_descriptor
     *
     * @return void
     */
    public function test_constructor_throws_for_choicesresolver_without_ajax(): void {
        $this->expectException(\coding_exception::class);

        new instance_setting_descriptor(
            key: 'mykey',
            title: new lang_string('pluginname', 'tool_userautodelete'),
            type: PARAM_TEXT,
            choices: ['a' => 'A'],
            mformtype: 'autocomplete',
            ajax: null,
            choicesresolver: fn(array $ids): array => [],
        );
    }

    /**
     * Tests that an autocomplete descriptor without choices and without ajax throws
     * a coding_exception.
     *
     * @covers \tool_userautodelete\local\type\instance_setting_descriptor
     *
     * @return void
     */
    public function test_constructor_throws_for_autocomplete_without_choices_and_ajax(): void {
        $this->expectException(\coding_exception::class);

        new instance_setting_descriptor(
            key: 'mykey',
            title: new lang_string('pluginname', 'tool_userautodelete'),
            type: PARAM_TEXT,
            choices: null,
            mformtype: 'autocomplete',
        );
    }
}
