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
 * Unit tests for the userdeletefilter_suspension sub-plugin
 *
 * @package   userdeletefilter_suspension
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_suspension;

use tool_userautodelete\step;
use tool_userautodelete\userdeletefilter;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeletefilter_testcase.php');


/**
 * Unit tests for the userdeletefilter_suspension sub-plugin
 */
final class userdeletefilter_test extends \tool_userautodelete\userdeletefilter_testcase {
    /**
     * Returns the short plugin name of the filter sub-plugin under test.
     */
    protected function get_plugin_name(): string {
        return 'suspension';
    }

    /**
     * Returns the expected font-awesome icon CSS class string for the filter
     * sub-plugin under test, e.g. 'fa-solid fa-filter'.
     */
    protected function get_expected_icon_class(): string {
        return 'fa-solid fa-user-slash';
    }

    /**
     * Creates and returns a filter instance that carries valid settings so that
     * user_records_filter_clause() can be called without throwing.
     *
     * @param step $step The step to attach the filter instance to
     * @return userdeletefilter A properly configured filter instance
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function create_valid_filter_instance(step $step): userdeletefilter {
        return $this->create_filter($step, ['suspended' => true]);
    }

    /**
     * Tests that the filter clause correctly includes suspended users and
     * excludes active users (and vice versa) based on the suspended setting.
     *
     * @covers \userdeletefilter_suspension\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_correct_users(): void {
        $this->resetAfterTest();

        $suspendeduser = $this->getDataGenerator()->create_user(['suspended' => 1]);
        $activeuser    = $this->getDataGenerator()->create_user(['suspended' => 0]);

        $step = $this->create_step();

        // Clause targeting suspended users.
        $suspendedfilter = $this->create_filter($step, ['suspended' => true]);
        $suspendedclause = $suspendedfilter->user_records_filter_clause();
        $matchedsuspended = $this->query_users_matching_clause($suspendedclause);

        $this->assertContains(
            (int) $suspendeduser->id,
            $matchedsuspended,
            'Filter for suspended users must include a suspended user'
        );
        $this->assertNotContains(
            (int) $activeuser->id,
            $matchedsuspended,
            'Filter for suspended users must exclude an active user'
        );

        // Clause targeting active (non-suspended) users.
        $activefilter = $this->create_filter($step, ['suspended' => false]);
        $activeclause = $activefilter->user_records_filter_clause();
        $matchedactive = $this->query_users_matching_clause($activeclause);

        $this->assertContains(
            (int) $activeuser->id,
            $matchedactive,
            'Filter for active users must include an active user'
        );
        $this->assertNotContains(
            (int) $suspendeduser->id,
            $matchedactive,
            'Filter for active users must exclude a suspended user'
        );
    }

    /**
     * Tests that user_records_filter_clause() throws a moodle_exception when
     * the required 'suspended' setting has not been configured.
     *
     * @covers \userdeletefilter_suspension\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_clause_throws_for_missing_suspended_setting(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $filter = $this->create_filter($step, ['suspended' => true]);

        // Remove the required setting to simulate a misconfigured instance.
        $filter->set_instance_setting('suspended', null);
        $filter = userdeletefilter::get_instance_by_id($filter->id);

        $this->expectException(\moodle_exception::class);
        $filter->user_records_filter_clause();
    }

    /**
     * Tests that is_valid() returns false when the required 'suspended' setting
     * is missing and true once it has been set.
     *
     * @covers \userdeletefilter_suspension\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_validity(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        // Default instance has 'suspended = true' pre-loaded → valid.
        $filter = $this->create_valid_filter_instance($step);
        $this->assertTrue($filter->is_valid(), 'suspension filter with default settings must be valid');

        // Removing the required setting must make it invalid.
        $filter->set_instance_setting('suspended', null);
        $filter = userdeletefilter::get_instance_by_id($filter->id);
        $this->assertFalse($filter->is_valid(), 'suspension filter without suspended setting must be invalid');
    }

    /**
     * Tests that get_instance_details() returns the expected human-readable
     * summary string for each branch of the suspension filter.
     *
     * @covers \userdeletefilter_suspension\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        // Match suspended users.
        $filter = $this->create_filter($step, ['suspended' => true]);
        $this->assertSame(
            get_string('suspended'),
            $filter->get_instance_details(),
            'get_instance_details() must return the "suspended" string when suspended users are targeted'
        );

        // Match unsuspended users.
        $filter->set_instance_setting('suspended', false);
        $this->assertSame(
            get_string('active'),
            $filter->get_instance_details(),
            'get_instance_details() must return the "active" string when active users are targeted'
        );
    }
}
