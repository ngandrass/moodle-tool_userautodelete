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
 * Tests for the userdeletefilter base class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\subplugin_type;

/**
 * Tests for the userdeletefilter base class
 */
final class userdeletefilter_test extends \advanced_testcase {
    /**
     * Tests shared static contract methods.
     *
     * @covers \tool_userautodelete\userdeletefilter
     *
     * @return void
     */
    public function test_base_static_contract_methods(): void {
        $this->assertSame(
            subplugin_type::FILTER,
            userdeletefilter::get_plugin_type(),
            'Unexpected plugin type for filter base class'
        );
        $this->assertSame(
            'fa-solid fa-filter',
            userdeletefilter::get_icon_class(),
            'Unexpected default icon class for filter base class'
        );
    }

    /**
     * Tests create/get/delete lifecycle of filter instances via base class methods.
     *
     * @covers \tool_userautodelete\userdeletefilter
     * @covers \tool_userautodelete\step_subplugin
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_instance_lifecycle_via_base_methods(): void {
        global $DB;

        $this->resetAfterTest();

        $workflow = workflow::create('Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step', description: '');

        $filter = userdeletefilter::create_instance($step, 'suspension');
        $this->assertInstanceOf(
            \userdeletefilter_suspension\userdeletefilter::class,
            $filter,
            'Unexpected filter instance class'
        );
        $this->assertTrue(
            $DB->record_exists(db_table::WORKFLOW_FILTER->value, ['id' => $filter->id]),
            'Filter record was not created'
        );

        // Validate inherited base methods from step_subplugin.
        $this->assertSame($filter->id, $filter->get_instance_id(), 'get_instance_id() returned incorrect value');
        $this->assertSame($step->id, $filter->get_step()->id, 'get_step() returned incorrect step');
        $this->assertSame(
            get_string('pluginname', 'userdeletefilter_suspension'),
            $filter->get_instance_title(),
            'get_instance_title() returned incorrect value'
        );
        $this->assertSame(
            get_string('suspended'),
            $filter->get_instance_details(),
            'get_instance_details() returned unexpected default suspension state label'
        );
        $this->assertTrue($filter->is_valid(), 'Filter should be valid with default required settings');

        $loaded = userdeletefilter::get_instance_by_id($filter->id);
        $this->assertInstanceOf(
            \userdeletefilter_suspension\userdeletefilter::class,
            $loaded,
            'Loaded filter instance class is incorrect'
        );
        $this->assertSame($step->id, $loaded->stepid, 'Loaded filter step relation is incorrect');

        $loaded->delete();
        $this->assertFalse(
            $DB->record_exists(db_table::WORKFLOW_FILTER->value, ['id' => $filter->id]),
            'Filter record still exists after delete()'
        );
    }

    /**
     * Tests that instance settings are deleted when a filter instance is deleted.
     *
     * @covers \tool_userautodelete\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_instance_settings_deleted_on_delete(): void {
        global $DB;

        $this->resetAfterTest();

        $workflow = workflow::create('Workflow', 'Description');
        $step = step::create(workflow: $workflow, title: 'Step', description: '');

        // Create a suspension filter instance, which stores a setting (suspension state).
        $filter = userdeletefilter::create_instance($step, 'suspension');

        // Verify that settings were persisted to the database.
        $this->assertGreaterThan(
            0,
            $DB->count_records('tool_userautodelete_instance_settings', ['instanceid' => $filter->id]),
            'Filter instance settings were not created'
        );

        // Delete the filter instance and assert that all settings are removed.
        $filter->delete();
        $this->assertSame(
            0,
            $DB->count_records('tool_userautodelete_instance_settings', ['instanceid' => $filter->id]),
            'Filter instance settings still exist after delete()'
        );
    }

    /**
     * Tests that creating a filter instance with invalid plugin name fails.
     *
     * @covers \tool_userautodelete\userdeletefilter
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
        $before = $DB->count_records(db_table::WORKFLOW_FILTER->value);

        try {
            userdeletefilter::create_instance($step, 'does_not_exist');
            $this->fail('Expected moodle_exception for invalid filter plugin name');
        } catch (\moodle_exception $e) { // phpcs:ignore
        }

        $this->assertSame(
            $before,
            $DB->count_records(db_table::WORKFLOW_FILTER->value),
            'No filter record should be created when plugin validation fails'
        );
    }
}
