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
 * Unit tests for the userdeletefilter_auth sub-plugin
 *
 * @package   userdeletefilter_auth
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_auth;

use tool_userautodelete\step;
use tool_userautodelete\userdeletefilter;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeletefilter_testcase.php');


/**
 * Unit tests for the userdeletefilter_auth sub-plugin
 */
final class userdeletefilter_test extends \tool_userautodelete\userdeletefilter_testcase {
    /**
     * Returns the short plugin name of the filter sub-plugin under test.
     */
    protected function get_plugin_name(): string {
        return 'auth';
    }

    /**
     * Returns the expected font-awesome icon CSS class string for the filter
     * sub-plugin under test, e.g. 'fa-solid fa-filter'.
     */
    protected function get_expected_icon_class(): string {
        return 'fa-solid fa-key';
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
        // Auth 'manual' is always available as an auth plugin in any Moodle installation.
        return $this->create_filter($step, ['auths' => ['manual'], 'inverted' => false]);
    }

    /**
     * Tests that the filter clause includes only users whose auth plugin matches
     * the configured list (inverted = false) and excludes users with a different
     * auth method.
     *
     * @covers \userdeletefilter_auth\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_correct_users(): void {
        $this->resetAfterTest();

        // Use two auth values that are just stored as strings in the user table;
        // actual plugin availability is irrelevant for the SQL clause test.
        $manualuser = $this->getDataGenerator()->create_user(['auth' => 'manual']);
        $otheruser  = $this->getDataGenerator()->create_user(['auth' => 'ldap']);

        $step = $this->create_step();

        // Non-inverted: include only manual auth users.
        $filter = $this->create_filter($step, ['auths' => ['manual'], 'inverted' => false]);
        $clause = $filter->user_records_filter_clause();
        $matched = $this->query_users_matching_clause($clause);

        $this->assertContains(
            (int) $manualuser->id,
            $matched,
            'Non-inverted auth filter must include a user with the matching auth plugin'
        );
        $this->assertNotContains(
            (int) $otheruser->id,
            $matched,
            'Non-inverted auth filter must exclude a user with a different auth plugin'
        );

        // Inverted: exclude manual auth users → include only non-manual users.
        $invertedfilter = $this->create_filter($step, ['auths' => ['manual'], 'inverted' => true]);
        $invertedclause = $invertedfilter->user_records_filter_clause();
        $invertedmatched = $this->query_users_matching_clause($invertedclause);

        $this->assertContains(
            (int) $otheruser->id,
            $invertedmatched,
            'Inverted auth filter must include a user whose auth plugin is NOT in the list'
        );
        $this->assertNotContains(
            (int) $manualuser->id,
            $invertedmatched,
            'Inverted auth filter must exclude a user whose auth plugin IS in the list'
        );
    }

    /**
     * Tests that the filter clause correctly handles multiple auth plugins in
     * the configured list.
     *
     * @covers \userdeletefilter_auth\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_multiple_auth_plugins(): void {
        $this->resetAfterTest();

        $manualuser = $this->getDataGenerator()->create_user(['auth' => 'manual']);
        $ldapuser   = $this->getDataGenerator()->create_user(['auth' => 'ldap']);
        $otheruser  = $this->getDataGenerator()->create_user(['auth' => 'oauth2']);

        $step = $this->create_step();
        $filter = $this->create_filter($step, ['auths' => ['manual', 'ldap'], 'inverted' => false]);
        $clause = $filter->user_records_filter_clause();
        $matched = $this->query_users_matching_clause($clause);

        $this->assertContains((int) $manualuser->id, $matched, 'Filter must include manual auth user');
        $this->assertContains((int) $ldapuser->id, $matched, 'Filter must include ldap auth user');
        $this->assertNotContains((int) $otheruser->id, $matched, 'Filter must exclude user with unlisted auth');
    }

    /**
     * Tests that is_valid() returns false when the required 'auths' setting is
     * missing and true once it has been set.
     *
     * @covers \userdeletefilter_auth\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_validity(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        // A valid instance with auths set must be valid.
        $valid = $this->create_valid_filter_instance($step);
        $this->assertTrue($valid->is_valid(), 'auth filter with auths set must be valid');

        // Remove the required 'auths' setting → must become invalid.
        $valid->set_instance_setting('auths', null);
        $reloaded = userdeletefilter::get_instance_by_id($valid->id);
        $this->assertFalse($reloaded->is_valid(), 'auth filter without auths setting must be invalid');
    }

    /**
     * Tests that get_instance_details() returns an empty string when no auths
     * are configured, and a non-empty string once auth plugins are set.
     *
     * @covers \userdeletefilter_auth\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        // No auths set → empty details.
        $empty = $this->create_filter($step);
        $this->assertSame(
            '',
            $empty->get_instance_details(),
            'auth filter without auths must return empty instance details'
        );

        // With a valid auth plugin name → non-empty details.
        $configured = $this->create_valid_filter_instance($step);
        $this->assertNotEmpty(
            $configured->get_instance_details(),
            'auth filter with auths configured must return non-empty instance details'
        );
    }
}
