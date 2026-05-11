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
 * Unit tests for the userdeletefilter_lastaccess sub-plugin
 *
 * @package   userdeletefilter_lastaccess
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_lastaccess;

use tool_userautodelete\step;
use tool_userautodelete\userdeletefilter;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeletefilter_testcase.php');


/**
 * Unit tests for the userdeletefilter_lastaccess sub-plugin
 */
final class userdeletefilter_test extends \tool_userautodelete\userdeletefilter_testcase {
    /**
     * Returns the short plugin name of the filter sub-plugin under test.
     */
    protected function get_plugin_name(): string {
        return 'lastaccess';
    }

    /**
     * Returns the expected font-awesome icon CSS class string for the filter
     * sub-plugin under test, e.g. 'fa-solid fa-filter'.
     */
    protected function get_expected_icon_class(): string {
        return 'fa-solid fa-clock-rotate-left';
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
        // Default value is YEARSECS * 3; any positive integer is valid.
        return $this->create_filter($step, ['thresholdsec' => DAYSECS * 30]);
    }

    /**
     * Tests that the filter clause includes users whose last access exceeds the
     * threshold and excludes users who accessed the site recently.
     *
     * @covers \userdeletefilter_lastaccess\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_correct_users(): void {
        global $DB;

        $this->resetAfterTest();

        $thresholdsec = DAYSECS * 30;

        // User whose last access is older than the threshold.
        $olduser = $this->getDataGenerator()->create_user();
        $DB->update_record('user', [
            'id'         => $olduser->id,
            'lastaccess' => time() - $thresholdsec - 60,
        ]);

        // User who accessed the site just now (well within the threshold).
        $recentuser = $this->getDataGenerator()->create_user();
        $DB->update_record('user', [
            'id'         => $recentuser->id,
            'lastaccess' => time(),
        ]);

        $step = $this->create_step();
        $filter = $this->create_filter($step, ['thresholdsec' => $thresholdsec]);
        $clause = $filter->user_records_filter_clause();
        $matched = $this->query_users_matching_clause($clause);

        $this->assertContains(
            (int) $olduser->id,
            $matched,
            'Filter must include a user whose last access exceeds the threshold'
        );
        $this->assertNotContains(
            (int) $recentuser->id,
            $matched,
            'Filter must exclude a user whose last access is within the threshold'
        );
    }

    /**
     * Tests that a user who has never logged in is matched when their account
     * creation time exceeds the threshold (timecreated fallback).
     *
     * @covers \userdeletefilter_lastaccess\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_never_logged_in_user_past_threshold(): void {
        global $DB;

        $this->resetAfterTest();
        $thresholdsec = DAYSECS * 30;

        // User that was created long ago and has never logged in.
        $oldneverloggedin = $this->getDataGenerator()->create_user();
        $DB->update_record('user', [
            'id'          => $oldneverloggedin->id,
            'lastaccess'  => 0,
            'timecreated' => time() - $thresholdsec - 60,
        ]);

        $step = $this->create_step();
        $filter = $this->create_filter($step, ['thresholdsec' => $thresholdsec]);
        $clause = $filter->user_records_filter_clause();
        $matched = $this->query_users_matching_clause($clause);

        $this->assertContains(
            (int) $oldneverloggedin->id,
            $matched,
            'Filter must include a never-logged-in user whose account creation time exceeds the threshold'
        );
    }

    /**
     * Tests that a user who has never logged in is not matched when their account
     * creation time is within the threshold (timecreated fallback).
     *
     * @covers \userdeletefilter_lastaccess\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_excludes_never_logged_in_user_within_threshold(): void {
        global $DB;

        $this->resetAfterTest();
        $thresholdsec = DAYSECS * 30;

        // User created recently who has never logged in.
        $freshneverloggedin = $this->getDataGenerator()->create_user();
        $DB->update_record('user', [
            'id'          => $freshneverloggedin->id,
            'lastaccess'  => 0,
            'timecreated' => time() - DAYSECS,
        ]);

        $step = $this->create_step();
        $filter = $this->create_filter($step, ['thresholdsec' => $thresholdsec]);
        $clause = $filter->user_records_filter_clause();
        $matched = $this->query_users_matching_clause($clause);

        $this->assertNotContains(
            (int) $freshneverloggedin->id,
            $matched,
            'Filter must exclude a never-logged-in user whose account creation time is within the threshold'
        );
    }

    /**
     * Tests that user_records_filter_clause() throws a moodle_exception when
     * the configured threshold is zero or negative.
     *
     * @covers \userdeletefilter_lastaccess\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_clause_throws_for_non_positive_threshold(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $filter = $this->create_filter($step, ['thresholdsec' => 0]);

        $this->expectException(\moodle_exception::class);
        $filter->user_records_filter_clause();
    }

    /**
     * Tests that is_valid() returns false when thresholdsec is not set and
     * true once a valid threshold has been configured.
     *
     * @covers \userdeletefilter_lastaccess\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_validity(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        // The default value for thresholdsec is YEARSECS * 3, so a fresh instance
        // without explicit settings override must already be valid.
        $filter = $this->create_filter($step);
        $this->assertTrue($filter->is_valid(), 'lastaccess filter with default threshold must be valid');
    }

    /**
     * Tests that get_instance_details() returns a non-empty human-readable
     * representation of the configured threshold duration.
     *
     * @covers \userdeletefilter_lastaccess\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details_returns_threshold_representation(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $filter = $this->create_filter($step, ['thresholdsec' => DAYSECS * 30]);

        $details = $filter->get_instance_details();
        $this->assertNotEmpty($details, 'get_instance_details() must return a non-empty string');
    }
}
