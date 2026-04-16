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
 * Abstract base testcase for userdeletefilter sub-plugin unit tests
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

use tool_userautodelete\local\type\db_table;
use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\local\type\subplugin_type;
use tool_userautodelete\local\type\userfilter_clause;
use tool_userautodelete\local\util\plugin_util;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Abstract base testcase for userdeletefilter sub-plugin unit tests.
 *
 * Provides shared contract tests and helpers that cover the complete
 * userdeletefilter sub-plugin interface. Extend this class in each
 * sub-plugin's tests/ directory and implement the abstract methods.
 *
 * The test discovery path for running all tests (main plugin + all sub-plugins)
 * is: vendor/bin/phpunit admin/tool/userautodelete
 */
abstract class userdeletefilter_testcase extends \advanced_testcase {
    /**
     * Returns the short plugin name of the filter sub-plugin under test.
     * E.g., 'suspension' for 'userdeletefilter_suspension'.
     *
     * @return string Plugin name
     */
    abstract protected function get_plugin_name(): string;

    /**
     * Returns the expected font-awesome icon CSS class string for the filter
     * sub-plugin under test, e.g. 'fa-solid fa-filter'.
     *
     * @return string Expected icon CSS class string
     */
    abstract protected function get_expected_icon_class(): string;

    /**
     * Creates and returns a filter instance that carries valid settings so that
     * user_records_filter_clause() can be called without throwing.
     *
     * @param step $step The step to attach the filter instance to
     * @return userdeletefilter A properly configured filter instance
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    abstract protected function create_valid_filter_instance(step $step): userdeletefilter;

    /**
     * Tests that user_records_filter_clause() returns a SQL clause that
     * correctly includes or excludes users according to the filter criteria.
     *
     * Use create_step(), create_filter(), and query_users_matching_clause()
     * to assemble the test scenario.
     *
     * @return void
     */
    abstract public function test_filter_matches_correct_users(): void;

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
     * Creates and returns a filter instance of the sub-plugin under test.
     *
     * @param step  $step     The step to attach the filter instance to
     * @param array $settings Optional initial instance settings to apply
     * @return userdeletefilter The created filter instance
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function create_filter(step $step, array $settings = []): userdeletefilter {
        return userdeletefilter::create_instance($step, $this->get_plugin_name(), $settings);
    }

    /**
     * Executes the given SQL filter clause against the Moodle user table and
     * returns an array of matching user IDs as integers.
     *
     * The clause must reference the user table via the 'u' alias, which is
     * exactly what every userdeletefilter implementation must produce.
     *
     * @param userfilter_clause $clause The filter clause to evaluate
     * @return int[] Array of matching user IDs
     * @throws \dml_exception
     */
    protected function query_users_matching_clause(userfilter_clause $clause): array {
        global $DB;

        return array_map(
            'intval',
            array_keys($DB->get_records_sql(
                "SELECT u.id FROM {user} u WHERE {$clause->sql}",
                $clause->params
            ))
        );
    }

    /**
     * Tests that get_plugin_name() returns the expected plugin name.
     *
     * @covers \tool_userautodelete\userdeletefilter
     * @return void
     * @throws \moodle_exception
     */
    public function test_plugin_name_is_correct(): void {
        /** @var userdeletefilter $cls */
        $cls = plugin_util::get_subplugin_class('userdeletefilter', $this->get_plugin_name());
        $this->assertSame(
            $this->get_plugin_name(),
            $cls::get_plugin_name(),
            'get_plugin_name() returned an unexpected value'
        );
    }

    /**
     * Tests that get_plugin_type() reports subplugin_type::FILTER.
     *
     * @covers \tool_userautodelete\userdeletefilter
     * @return void
     * @throws \moodle_exception
     */
    public function test_plugin_type_is_filter(): void {
        /** @var userdeletefilter $cls */
        $cls = plugin_util::get_subplugin_class('userdeletefilter', $this->get_plugin_name());
        $this->assertSame(
            subplugin_type::FILTER,
            $cls::get_plugin_type(),
            'Filter sub-plugin must report subplugin_type::FILTER as its plugin type'
        );
    }

    /**
     * Tests that get_icon_class() returns the expected icon CSS class string.
     *
     * @return void
     * @throws \moodle_exception
     */
    public function test_icon_class_is_correct(): void {
        /** @var userdeletefilter $cls */
        $cls = plugin_util::get_subplugin_class('userdeletefilter', $this->get_plugin_name());
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
        /** @var userdeletefilter $cls */
        $cls = plugin_util::get_subplugin_class('userdeletefilter', $this->get_plugin_name());
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
     * Tests that creating a filter instance via userdeletefilter::create_instance()
     * returns an object of the correct concrete sub-plugin class, and that
     * reloading by ID preserves the class.
     *
     * @covers \tool_userautodelete\userdeletefilter
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_resolves_to_correct_class(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $filter = $this->create_valid_filter_instance($step);

        $expectedclass = plugin_util::get_subplugin_class('userdeletefilter', $this->get_plugin_name());
        $this->assertInstanceOf(
            $expectedclass,
            $filter,
            'Created filter instance does not match the expected sub-plugin class'
        );

        $loaded = userdeletefilter::get_instance_by_id($filter->id);
        $this->assertInstanceOf(
            $expectedclass,
            $loaded,
            'Reloaded filter instance does not match the expected sub-plugin class'
        );
    }

    /**
     * Tests that the instance's get_plugin_name() matches the expected plugin name.
     *
     * @covers \tool_userautodelete\userdeletefilter
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_plugin_name_matches_expected(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $filter = $this->create_valid_filter_instance($step);

        $this->assertSame(
            $this->get_plugin_name(),
            $filter::get_plugin_name(),
            'Instance get_plugin_name() must return the expected plugin name'
        );
    }

    /**
     * Tests that user_records_filter_clause() returns a userfilter_clause with
     * a non-empty SQL string and an array of parameters.
     *
     * @covers \tool_userautodelete\userdeletefilter
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_clause_returns_valid_clause(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $filter = $this->create_valid_filter_instance($step);
        $clause = $filter->user_records_filter_clause();

        $this->assertInstanceOf(
            userfilter_clause::class,
            $clause,
            'user_records_filter_clause() must return a userfilter_clause instance'
        );
        $this->assertNotEmpty($clause->sql, 'Filter clause SQL must not be empty');
        $this->assertIsArray($clause->params, 'Filter clause params must be an array');
    }

    /**
     * Tests that delete() removes the filter instance record from the database.
     *
     * @covers \tool_userautodelete\userdeletefilter
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_can_be_deleted(): void {
        global $DB;

        $this->resetAfterTest();

        $step = $this->create_step();
        $filter = $this->create_valid_filter_instance($step);

        $this->assertTrue(
            $DB->record_exists(db_table::WORKFLOW_FILTER->value, ['id' => $filter->id]),
            'Filter record must exist in the database after creation'
        );

        $filter->delete();

        $this->assertFalse(
            $DB->record_exists(db_table::WORKFLOW_FILTER->value, ['id' => $filter->id]),
            'Filter record must be removed from the database after delete()'
        );
    }
}






