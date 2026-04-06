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
 * Tests for subplugin_instance_settings trait
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete\local\trait;

use core\lang_string;
use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\local\type\subplugin_type;

/**
 * Tests for subplugin_instance_settings trait
 */
final class subplugin_instance_settings_test extends \advanced_testcase {
    /**
     * Creates a trait fixture instance used by all tests.
     *
     * @param int $instanceid ID to expose via get_instance_id()
     * @return object
     */
    private function create_fixture_instance(int $instanceid = 1001): object {
        return new class ($instanceid) {
            use subplugin_instance_settings;

            /**
             * Creates a new fixture instance.
             *
             * @param int $instanceid ID of the related sub-plugin instance
             */
            public function __construct(
                /** @var int $instanceid ID of the related sub-plugin instance */
                protected int $instanceid
            ) {
            }

            /**
             * Returns the fixture setting descriptors.
             *
             * @return instance_setting_descriptor[]
             */
            public static function instance_setting_descriptors(): array {
                return [
                    new instance_setting_descriptor(
                        key: 'serializedlist',
                        title: new lang_string('pluginname', 'tool_userautodelete'),
                        type: PARAM_TEXT,
                        required: false,
                        default: ['manual'],
                        serialize: true,
                    ),
                    new instance_setting_descriptor(
                        key: 'requiredtext',
                        title: new lang_string('pluginname', 'tool_userautodelete'),
                        type: PARAM_TEXT,
                        required: true,
                        default: 'default text',
                    ),
                    new instance_setting_descriptor(
                        key: 'requiredbool',
                        title: new lang_string('pluginname', 'tool_userautodelete'),
                        type: PARAM_BOOL,
                        required: true,
                        default: false,
                    ),
                    new instance_setting_descriptor(
                        key: 'optionalnull',
                        title: new lang_string('pluginname', 'tool_userautodelete'),
                        type: PARAM_TEXT,
                        required: false,
                        default: null,
                    ),
                ];
            }

            /**
             * Returns the fixture plugin type.
             *
             * @return subplugin_type
             */
            public static function get_plugin_type(): subplugin_type {
                return subplugin_type::FILTER;
            }

            /**
             * Returns the fixture instance id.
             *
             * @return int
             */
            public function get_instance_id(): int {
                return $this->instanceid;
            }
        };
    }

    /**
     * Tests get_serialized_settings().
     *
     * @covers \tool_userautodelete\local\trait\subplugin_instance_settings
     *
     * @return void
     */
    public function test_get_serialized_settings(): void {
        $fixture = $this->create_fixture_instance();

        $this->assertSame(['serializedlist'], $fixture->get_serialized_settings(), 'Serialized settings are incorrect');
    }

    /**
     * Tests get_all_instance_settings() with scalar and serialized values.
     *
     * @covers \tool_userautodelete\local\trait\subplugin_instance_settings
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_get_all_instance_settings(): void {
        global $DB;

        $this->resetAfterTest();
        $fixture = $this->create_fixture_instance();

        $DB->insert_record(db_table::INSTANCE_SETTINGS->value, [
            'plugintype' => subplugin_type::FILTER->value,
            'instanceid' => $fixture->get_instance_id(),
            'datakey' => 'serializedlist',
            'datavalue' => json_encode(['manual', 'ldap']),
        ]);
        $DB->insert_record(db_table::INSTANCE_SETTINGS->value, [
            'plugintype' => subplugin_type::FILTER->value,
            'instanceid' => $fixture->get_instance_id(),
            'datakey' => 'requiredtext',
            'datavalue' => 'hello',
        ]);

        $settings = $fixture->get_all_instance_settings();

        $this->assertSame(['manual', 'ldap'], $settings['serializedlist'], 'Serialized setting was not decoded');
        $this->assertSame('hello', $settings['requiredtext'], 'Scalar setting is incorrect');
    }

    /**
     * Tests get_instance_setting() for existing and missing keys.
     *
     * @covers \tool_userautodelete\local\trait\subplugin_instance_settings
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_get_instance_setting(): void {
        global $DB;

        $this->resetAfterTest();
        $fixture = $this->create_fixture_instance();

        $DB->insert_record(db_table::INSTANCE_SETTINGS->value, [
            'plugintype' => subplugin_type::FILTER->value,
            'instanceid' => $fixture->get_instance_id(),
            'datakey' => 'serializedlist',
            'datavalue' => json_encode(['oauth2']),
        ]);
        $DB->insert_record(db_table::INSTANCE_SETTINGS->value, [
            'plugintype' => subplugin_type::FILTER->value,
            'instanceid' => $fixture->get_instance_id(),
            'datakey' => 'requiredtext',
            'datavalue' => 'text value',
        ]);

        $this->assertSame(['oauth2'], $fixture->get_instance_setting('serializedlist'), 'Serialized setting is incorrect');
        $this->assertSame('text value', $fixture->get_instance_setting('requiredtext'), 'Scalar setting is incorrect');
        $this->assertNull($fixture->get_instance_setting('unknownkey'), 'Missing setting should return null');
    }

    /**
     * Tests set_instance_setting() for insert, update, delete and serialization.
     *
     * @covers \tool_userautodelete\local\trait\subplugin_instance_settings
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_set_instance_setting(): void {
        global $DB;

        $this->resetAfterTest();
        $fixture = $this->create_fixture_instance();

        // Insert new setting.
        $fixture->set_instance_setting('requiredtext', 'initial value');
        $this->assertSame('initial value', $fixture->get_instance_setting('requiredtext'), 'Inserted value is incorrect');

        // Update existing setting.
        $fixture->set_instance_setting('requiredtext', 'updated value');
        $this->assertSame('updated value', $fixture->get_instance_setting('requiredtext'), 'Updated value is incorrect');

        // Insert serialized setting and verify raw DB data.
        $fixture->set_instance_setting('serializedlist', ['manual', 'ldap']);
        $rawserialized = $DB->get_field(
            db_table::INSTANCE_SETTINGS->value,
            'datavalue',
            [
                'plugintype' => subplugin_type::FILTER->value,
                'instanceid' => $fixture->get_instance_id(),
                'datakey' => 'serializedlist',
            ],
            MUST_EXIST,
        );
        $this->assertSame('["manual","ldap"]', $rawserialized, 'Serialized DB data is incorrect');
        $this->assertSame(
            ['manual', 'ldap'],
            $fixture->get_instance_setting('serializedlist'),
            'Serialized value retrieval is incorrect'
        );

        // Null assignment must delete the setting record.
        $fixture->set_instance_setting('requiredtext', null);
        $this->assertNull($fixture->get_instance_setting('requiredtext'), 'Setting should be deleted when value is null');
    }

    /**
     * Tests load_default_instance_settings() including overrides and null defaults.
     *
     * @covers \tool_userautodelete\local\trait\subplugin_instance_settings
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_load_default_instance_settings(): void {
        global $DB;

        $this->resetAfterTest();
        $fixture = $this->create_fixture_instance();

        // Seed stale settings to ensure they are replaced.
        $DB->insert_record(db_table::INSTANCE_SETTINGS->value, [
            'plugintype' => subplugin_type::FILTER->value,
            'instanceid' => $fixture->get_instance_id(),
            'datakey' => 'stale',
            'datavalue' => 'old',
        ]);

        $fixture->load_default_instance_settings([
            'requiredtext' => 'custom text',
            'optionalnull' => 'forced value',
        ]);

        $this->assertNull($fixture->get_instance_setting('stale'), 'Stale setting should be deleted during default load');
        $this->assertSame(['manual'], $fixture->get_instance_setting('serializedlist'), 'Serialized default is incorrect');
        $this->assertSame('custom text', $fixture->get_instance_setting('requiredtext'), 'Override value is incorrect');
        $this->assertSame('forced value', $fixture->get_instance_setting('optionalnull'), 'Override for null default is incorrect');
    }

    /**
     * Tests delete_all_instance_settings().
     *
     * @covers \tool_userautodelete\local\trait\subplugin_instance_settings
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_delete_all_instance_settings(): void {
        $this->resetAfterTest();
        $fixture = $this->create_fixture_instance();

        $fixture->set_instance_setting('requiredtext', 'to be deleted');
        $fixture->set_instance_setting('serializedlist', ['a']);

        $this->assertNotEmpty($fixture->get_all_instance_settings(), 'Fixture setup failed to create settings');

        $fixture->delete_all_instance_settings();

        $this->assertSame([], $fixture->get_all_instance_settings(), 'All settings should be deleted');
    }

    /**
     * Tests is_all_required_instance_settings_set() for text and bool requirements.
     *
     * @covers \tool_userautodelete\local\trait\subplugin_instance_settings
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_is_all_required_instance_settings_set(): void {
        $this->resetAfterTest();
        $fixture = $this->create_fixture_instance();

        // Missing required settings must fail validation.
        $this->assertFalse(
            $fixture->is_all_required_instance_settings_set(),
            'Validation should fail when required settings are missing'
        );

        // Empty required text value must fail validation.
        $fixture->set_instance_setting('requiredtext', '');
        $fixture->set_instance_setting('requiredbool', 0);
        $this->assertFalse($fixture->is_all_required_instance_settings_set(), 'Validation should fail for empty required text');

        // Required bool value 0 is valid for PARAM_BOOL descriptors.
        $fixture->set_instance_setting('requiredtext', 'configured');
        $this->assertTrue(
            $fixture->is_all_required_instance_settings_set(),
            'Validation should pass when required text exists and required bool is false'
        );
    }
}
