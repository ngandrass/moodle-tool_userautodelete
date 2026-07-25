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
 * Unit tests for the userdeletefilter_confirmed sub-plugin.
 *
 * @package   userdeletefilter_confirmed
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_confirmed;

use tool_userautodelete\step;
use tool_userautodelete\userdeletefilter;
use userdeletefilter_confirmed\local\type\mode;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeletefilter_testcase.php');


/**
 * Unit tests for the userdeletefilter_confirmed sub-plugin.
 */
final class userdeletefilter_test extends \tool_userautodelete\userdeletefilter_testcase {
    /**
     * Returns the short plugin name of the filter sub-plugin under test.
     */
    protected function get_plugin_name(): string {
        return 'confirmed';
    }

    /**
     * Returns the expected font-awesome icon CSS class string for the filter
     * sub-plugin under test.
     */
    protected function get_expected_icon_class(): string {
        return 'fa-solid fa-circle-check';
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
        return $this->create_filter($step, ['mode' => mode::UNCONFIRMED->value]);
    }

    /**
     * Tests that the filter clause correctly includes unconfirmed users and
     * excludes confirmed users (and vice versa) based on the mode setting.
     *
     * @covers \userdeletefilter_confirmed\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_correct_users(): void {
        $this->resetAfterTest();

        $confirmeduser = $this->getDataGenerator()->create_user(['confirmed' => 1]);
        $unconfirmeduser = $this->getDataGenerator()->create_user(['confirmed' => 0]);

        $step = $this->create_step();

        // Clause targeting unconfirmed users.
        $unconfirmedfilter = $this->create_filter($step, ['mode' => mode::UNCONFIRMED->value]);
        $unconfirmedclause = $unconfirmedfilter->user_records_filter_clause();
        $matchedunconfirmed = $this->query_users_matching_clause($unconfirmedclause);

        $this->assertContains(
            (int) $unconfirmeduser->id,
            $matchedunconfirmed,
            'Filter for unconfirmed users must include an unconfirmed user'
        );
        $this->assertNotContains(
            (int) $confirmeduser->id,
            $matchedunconfirmed,
            'Filter for unconfirmed users must exclude a confirmed user'
        );

        // Clause targeting confirmed users.
        $confirmedfilter = $this->create_filter($step, ['mode' => mode::CONFIRMED->value]);
        $confirmedclause = $confirmedfilter->user_records_filter_clause();
        $matchedconfirmed = $this->query_users_matching_clause($confirmedclause);

        $this->assertContains(
            (int) $confirmeduser->id,
            $matchedconfirmed,
            'Filter for confirmed users must include a confirmed user'
        );
        $this->assertNotContains(
            (int) $unconfirmeduser->id,
            $matchedconfirmed,
            'Filter for confirmed users must exclude an unconfirmed user'
        );
    }

    /**
     * Tests that user_records_filter_clause() throws a moodle_exception when
     * the required 'mode' setting has not been configured.
     *
     * @covers \userdeletefilter_confirmed\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_clause_throws_for_missing_mode_setting(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $filter = $this->create_filter($step, ['mode' => mode::UNCONFIRMED->value]);

        // Remove the required setting to simulate a misconfigured instance.
        $filter->set_instance_setting('mode', null);
        $filter = userdeletefilter::get_instance_by_id($filter->id);

        $this->expectException(\moodle_exception::class);
        $filter->user_records_filter_clause();
    }

    /**
     * Tests that is_valid() returns false when the required 'mode' setting is
     * missing and true once it has been set.
     *
     * @covers \userdeletefilter_confirmed\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_validity(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        // Default instance has 'mode = unconfirmed' pre-loaded -> valid.
        $filter = $this->create_valid_filter_instance($step);
        $this->assertTrue($filter->is_valid(), 'Confirmed filter with default settings must be valid');

        // Removing the required setting must make it invalid.
        $filter->set_instance_setting('mode', null);
        $filter = userdeletefilter::get_instance_by_id($filter->id);
        $this->assertFalse($filter->is_valid(), 'Confirmed filter without mode setting must be invalid');
    }

    /**
     * Tests that get_instance_details() returns the expected human-readable
     * summary string for each confirmation mode.
     *
     * @covers \userdeletefilter_confirmed\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        // Match unconfirmed users.
        $filter = $this->create_filter($step, ['mode' => mode::UNCONFIRMED->value]);
        $this->assertSame(
            get_string('mode_unconfirmed', 'userdeletefilter_confirmed'),
            $filter->get_instance_details(),
            'get_instance_details() must return the unconfirmed label when targeting unconfirmed users'
        );

        // Match confirmed users.
        $filter->set_instance_setting('mode', mode::CONFIRMED->value);
        $this->assertSame(
            get_string('mode_confirmed', 'userdeletefilter_confirmed'),
            $filter->get_instance_details(),
            'get_instance_details() must return the confirmed label when targeting confirmed users'
        );
    }
}
