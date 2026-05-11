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
 * Tests for the userdeleteaction base class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\subplugin_type;

/**
 * Tests for the userdeleteaction base class
 */
final class userdeleteaction_test extends \advanced_testcase {
    /**
     * Tests shared static contract methods.
     *
     * @covers \tool_userautodelete\userdeleteaction
     *
     * @return void
     */
    public function test_base_static_contract_methods(): void {
        $this->assertSame(
            subplugin_type::ACTION,
            userdeleteaction::get_plugin_type(),
            'Unexpected plugin type for action base class'
        );
        $this->assertSame(
            'fa-solid fa-gear',
            userdeleteaction::get_icon_class(),
            'Unexpected default icon class for action base class'
        );
    }

    /**
     * Tests create/get/delete lifecycle of action instances via base class methods.
     *
     * @covers \tool_userautodelete\userdeleteaction
     * @covers \tool_userautodelete\step_subplugin
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_action_instance_lifecycle_via_base_methods(): void {
        global $DB;

        $this->resetAfterTest();

        $workflow = workflow::create('Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step', description: '');

        $action = userdeleteaction::create_instance($step, 'suspend');
        $this->assertInstanceOf(
            \userdeleteaction_suspend\userdeleteaction::class,
            $action,
            'Unexpected action instance class'
        );
        $this->assertTrue(
            $DB->record_exists(db_table::WORKFLOW_ACTION->value, ['id' => $action->id]),
            'Action record was not created'
        );

        // Validate inherited base methods from step_subplugin.
        $this->assertSame($action->id, $action->get_instance_id(), 'get_instance_id() returned incorrect value');
        $this->assertSame($step->id, $action->get_step()->id, 'get_step() returned incorrect step');
        $this->assertSame(
            get_string('pluginname', 'userdeleteaction_suspend'),
            $action->get_instance_title(),
            'get_instance_title() returned incorrect value'
        );
        $this->assertSame('', $action->get_instance_details(), 'get_instance_details() should be empty by default');
        $this->assertTrue($action->is_valid(), 'Action without required settings should be valid');
        $this->assertNull($action->validate(), 'Valid action should return null from validate()');

        $loaded = userdeleteaction::get_instance_by_id($action->id);
        $this->assertInstanceOf(
            \userdeleteaction_suspend\userdeleteaction::class,
            $loaded,
            'Loaded action instance class is incorrect'
        );
        $this->assertSame($step->id, $loaded->stepid, 'Loaded action step relation is incorrect');

        $loaded->delete();
        $this->assertFalse(
            $DB->record_exists(db_table::WORKFLOW_ACTION->value, ['id' => $action->id]),
            'Action record still exists after delete()'
        );
    }

    /**
     * Tests validity reporting for actions with required settings.
     *
     * @covers \tool_userautodelete\userdeleteaction
     * @covers \tool_userautodelete\step_subplugin
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_action_validation_reports_missing_required_settings(): void {
        $this->resetAfterTest();

        $workflow = workflow::create('Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step', description: '');
        $action = userdeleteaction::create_instance($step, 'mail');

        $this->assertFalse($action->is_valid(), 'Mail action without required settings should be invalid');
        $this->assertSame(
            get_string('required_setting_is_unset', 'tool_userautodelete'),
            $action->validate(),
            'Missing required settings should return the localized invalidity reason'
        );

        $action->set_instance_setting('subject', 'Subject');
        $action->set_instance_setting('message', 'Body');

        $this->assertTrue($action->is_valid(), 'Mail action should be valid after required settings are set');
        $this->assertNull($action->validate(), 'Valid mail action should return null from validate()');
    }

    /**
     * Tests that instance settings are deleted when an action instance is deleted.
     *
     * @covers \tool_userautodelete\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_action_instance_settings_deleted_on_delete(): void {
        global $DB;

        $this->resetAfterTest();

        $workflow = workflow::create('Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step', description: '');

        // Create a mail action instance, which has required settings (subject + message).
        $action = userdeleteaction::create_instance($step, 'mail', [
            'subject' => 'Test subject',
            'message' => 'Test message',
        ]);

        // Verify that settings were persisted to the database.
        $this->assertGreaterThan(
            0,
            $DB->count_records('tool_userautodelete_instance_settings', ['instanceid' => $action->id]),
            'Action instance settings were not created'
        );

        // Delete the action instance and assert that all settings are removed.
        $action->delete();
        $this->assertSame(
            0,
            $DB->count_records('tool_userautodelete_instance_settings', ['instanceid' => $action->id]),
            'Action instance settings still exist after delete()'
        );
    }

    /**
     * Tests that creating an action instance with invalid plugin name fails.
     *
     * @covers \tool_userautodelete\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_create_instance_invalid_plugin_throws(): void {
        global $DB;

        $this->resetAfterTest();

        $workflow = workflow::create('Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step', description: '');
        $before = $DB->count_records(db_table::WORKFLOW_ACTION->value);

        try {
            userdeleteaction::create_instance($step, 'does_not_exist');
            $this->fail('Expected moodle_exception for invalid action plugin name');
        } catch (\moodle_exception $e) { // phpcs:ignore
        }

        $this->assertSame(
            $before,
            $DB->count_records(db_table::WORKFLOW_ACTION->value),
            'No action record should be created when plugin validation fails'
        );
    }
}
