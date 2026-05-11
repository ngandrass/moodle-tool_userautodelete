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
 * Abstract base testcase for userdeleteaction sub-plugin unit tests
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\local\type\process_state;
use tool_userautodelete\local\type\subplugin_type;
use tool_userautodelete\local\util\plugin_util;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Abstract base testcase for userdeleteaction sub-plugin unit tests.
 *
 * Provides shared contract tests and helpers that cover the complete
 * userdeleteaction sub-plugin interface. Extend this class in each
 * sub-plugin's tests/ directory and implement the abstract methods.
 */
abstract class userdeleteaction_testcase extends \advanced_testcase {
    /**
     * Returns the short plugin name of the action sub-plugin under test.
     * E.g., 'suspend' for 'userdeleteaction_suspend'.
     *
     * @return string Plugin name
     */
    abstract protected function get_plugin_name(): string;

    /**
     * Returns the expected font-awesome icon CSS class string for the action
     * sub-plugin under test, e.g. 'fa-solid fa-gear'.
     *
     * @return string Expected icon CSS class string
     */
    abstract protected function get_expected_icon_class(): string;

    /**
     * Tests the execute() behavior specific to this action sub-plugin.
     *
     * Use the helper methods create_step(), create_action(), and create_process()
     * to assemble the necessary test fixtures.
     *
     * @return void
     */
    abstract public function test_execute(): void;

    /**
     * Returns the plugin-specific test data generator.
     *
     * @return \tool_userautodelete_generator
     */
    protected function get_generator(): \tool_userautodelete_generator {
        /** @var \tool_userautodelete_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_userautodelete');
        return $generator;
    }

    /**
     * Creates a new workflow with one attached step for fixture purposes.
     *
     * @return step Freshly created empty step
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function create_step(): step {
        [$workflow, $step] = $this->get_generator()->create_workflow_with_empty_step();

        return $step;
    }

    /**
     * Creates and returns an action instance of the sub-plugin under test.
     *
     * @param step  $step     The step to attach the action instance to
     * @param array $settings Optional initial instance settings to apply
     * @return userdeleteaction The created action instance
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function create_action(step $step, array $settings = []): userdeleteaction {
        return userdeleteaction::create_instance($step, $this->get_plugin_name(), $settings);
    }

    /**
     * Inserts a minimal process record into the database and returns the loaded
     * process object.
     *
     * This bypasses process::create() (which executes step actions as a side-
     * effect) so that each test_execute() implementation can call
     * $action->execute($process) in isolation without triggering the full
     * workflow machinery.
     *
     * @param int  $userid ID of the user this process belongs to
     * @param step $step   The step this process is placed in
     * @return process The created process object
     * @throws \dml_exception
     */
    protected function create_process(int $userid, step $step): process {
        global $DB;

        $now = time();
        $processid = $DB->insert_record(db_table::USER_PROCESS->value, [
            'userid'       => $userid,
            'stepid'       => $step->id,
            'state'        => process_state::ACTIVE->value,
            'timecreated'  => $now,
            'timemodified' => $now,
        ]);

        return process::get_by_id($processid);
    }

    /**
     * Tests that get_plugin_name() returns the expected plugin name.
     *
     * @return void
     * @throws \moodle_exception
     */
    public function test_plugin_name_is_correct(): void {
        /** @var userdeleteaction $cls */
        $cls = plugin_util::get_subplugin_class('userdeleteaction', $this->get_plugin_name());
        $this->assertSame(
            $this->get_plugin_name(),
            $cls::get_plugin_name(),
            'get_plugin_name() returned an unexpected value'
        );
    }

    /**
     * Tests that get_plugin_type() reports subplugin_type::ACTION.
     *
     * @return void
     * @throws \moodle_exception
     */
    public function test_plugin_type_is_action(): void {
        /** @var userdeleteaction $cls */
        $cls = plugin_util::get_subplugin_class('userdeleteaction', $this->get_plugin_name());
        $this->assertSame(
            subplugin_type::ACTION,
            $cls::get_plugin_type(),
            'Action sub-plugin must report subplugin_type::ACTION as its plugin type'
        );
    }

    /**
     * Tests that get_icon_class() returns the expected icon CSS class string.
     *
     * @return void
     * @throws \moodle_exception
     */
    public function test_icon_class_is_correct(): void {
        /** @var userdeleteaction $cls */
        $cls = plugin_util::get_subplugin_class('userdeleteaction', $this->get_plugin_name());
        $this->assertSame(
            $this->get_expected_icon_class(),
            $cls::get_icon_class(),
            'get_icon_class() returned an unexpected value'
        );
    }

    /**
     * Tests that instance_setting_descriptors() returns an array whose entries
     * are all instance_setting_descriptor objects.
     *
     * @return void
     * @throws \moodle_exception
     */
    public function test_instance_setting_descriptors_returns_array(): void {
        /** @var userdeleteaction $cls */
        $cls = plugin_util::get_subplugin_class('userdeleteaction', $this->get_plugin_name());
        $descriptors = $cls::instance_setting_descriptors();

        $this->assertIsArray($descriptors, 'instance_setting_descriptors() must return an array');
        foreach ($descriptors as $descriptor) {
            $this->assertInstanceOf(
                instance_setting_descriptor::class,
                $descriptor,
                'Every entry returned by instance_setting_descriptors() must be an instance_setting_descriptor'
            );
        }
    }

    /**
     * Tests that creating an action instance via userdeleteaction::create_instance()
     * returns an object of the correct concrete sub-plugin class, and that
     * reloading by ID preserves the class.
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_resolves_to_correct_class(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $expectedclass = plugin_util::get_subplugin_class('userdeleteaction', $this->get_plugin_name());
        $this->assertInstanceOf(
            $expectedclass,
            $action,
            'Created action instance does not match the expected sub-plugin class'
        );

        $loaded = userdeleteaction::get_instance_by_id($action->id);
        $this->assertInstanceOf(
            $expectedclass,
            $loaded,
            'Reloaded action instance does not match the expected sub-plugin class'
        );
    }

    /**
     * Tests that the instance's get_plugin_name() matches the expected plugin name.
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_plugin_name_matches_expected(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertSame(
            $this->get_plugin_name(),
            $action::get_plugin_name(),
            'Instance get_plugin_name() must return the expected plugin name'
        );
    }

    /**
     * Tests that delete() removes the action instance record from the database.
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_can_be_deleted(): void {
        global $DB;

        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertTrue(
            $DB->record_exists(db_table::WORKFLOW_ACTION->value, ['id' => $action->id]),
            'Action record must exist in the database after creation'
        );

        $action->delete();

        $this->assertFalse(
            $DB->record_exists(db_table::WORKFLOW_ACTION->value, ['id' => $action->id]),
            'Action record must be removed from the database after delete()'
        );
    }
}
