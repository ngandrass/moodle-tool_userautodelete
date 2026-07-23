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
 * Unit tests for the userdeletefilter_profilefield sub-plugin
 *
 * @package   userdeletefilter_profilefield
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_profilefield;

use tool_userautodelete\step;
use userdeletefilter_profilefield\local\type\matchmode;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeletefilter_testcase.php');


/**
 * Unit tests for the userdeletefilter_profilefield sub-plugin
 */
final class userdeletefilter_test extends \tool_userautodelete\userdeletefilter_testcase {
    /**
     * Returns the short plugin name of the filter sub-plugin under test.
     */
    protected function get_plugin_name(): string {
        return 'profilefield';
    }

    /**
     * Returns the expected font-awesome icon CSS class string for the filter
     * sub-plugin under test, e.g. 'fa-solid fa-filter'.
     */
    protected function get_expected_icon_class(): string {
        return 'fa-regular fa-id-card';
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
    protected function create_valid_filter_instance(step $step): \tool_userautodelete\userdeletefilter {
        return $this->create_filter($step, [
            'field' => userdeletefilter::PREFIX_STD . 'firstname',
            'matchmode' => matchmode::CONTAINS->value,
            'value' => 'test',
        ]);
    }

    /**
     * Tests that the filter clause correctly includes users whose firstname contains
     * the configured value and excludes those that do not match.
     *
     * @covers \userdeletefilter_profilefield\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_matches_correct_users(): void {
        $this->resetAfterTest();

        $alice = $this->getDataGenerator()->create_user(['firstname' => 'Alice']);
        $alan = $this->getDataGenerator()->create_user(['firstname' => 'Alan']);
        $bob = $this->getDataGenerator()->create_user(['firstname' => 'Bob']);

        $step = $this->create_step();
        $filter = $this->create_filter($step, [
            'field' => userdeletefilter::PREFIX_STD . 'firstname',
            'matchmode' => matchmode::CONTAINS->value,
            'value' => 'Al',
        ]);

        $matched = $this->query_users_matching_clause($filter->user_records_filter_clause());

        $this->assertContains((int) $alice->id, $matched, 'Filter must include user with matching firstname');
        $this->assertContains((int) $alan->id, $matched, 'Filter must include user with matching firstname');
        $this->assertNotContains((int) $bob->id, $matched, 'Filter must exclude user with non-matching firstname');
    }

    /**
     * Data provider for test_filter_standard_field().
     *
     * Builds a case for every combination of standard field and match mode.
     * Each field config defines target/other user props and the match values needed
     * to exercise CONTAINS, EQUALS, STARTS_WITH, and ENDS_WITH unambiguously.
     * NOT_CONTAINS / NOT_EQUALS / NOT_EMPTY are derived from the same values.
     *
     * @return array[] Test data with the following props: [field key, target user props, other user props,
     * match mode, comparison value, expect target matched, expect other matched].
     */
    public static function provide_standard_fields_and_match_modes(): array {
        $fieldconfigs = [
            [
                userdeletefilter::PREFIX_STD . 'fullname',
                ['firstname' => 'Alice', 'lastname' => 'Wonderland'],
                ['firstname' => 'Bob', 'lastname' => 'Builder'],
                'Alice',
                'Alice Wonderland',
                'Alice',
                'land',
            ],
            [
                userdeletefilter::PREFIX_STD . 'firstname',
                ['firstname' => 'Alice'],
                ['firstname' => 'Bob'],
                'Alic',
                'Alice',
                'Al',
                'ice',
            ],
            [
                userdeletefilter::PREFIX_STD . 'lastname',
                ['lastname' => 'Wonderland'],
                ['lastname' => 'Builder'],
                'Wonder',
                'Wonderland',
                'Wonder',
                'land',
            ],
            [
                userdeletefilter::PREFIX_STD . 'alternatename',
                ['alternatename' => 'TargetAlias'],
                ['alternatename' => 'OtherNick'],
                'Target',
                'TargetAlias',
                'Target',
                'Alias',
            ],
            [
                userdeletefilter::PREFIX_STD . 'idnumber',
                ['idnumber' => 'TGT-001'],
                ['idnumber' => 'OTH-002'],
                'TGT',
                'TGT-001',
                'TGT',
                '001',
            ],
            [
                userdeletefilter::PREFIX_STD . 'email',
                ['email' => 'target@example.com'],
                ['email' => 'other@different.org'],
                'example',
                'target@example.com',
                'target',
                '.com',
            ],
            [
                userdeletefilter::PREFIX_STD . 'department',
                ['department' => 'Engineering'],
                ['department' => 'Marketing'],
                'Engin',
                'Engineering',
                'Eng',
                'neering',
            ],
            [
                userdeletefilter::PREFIX_STD . 'institution',
                ['institution' => 'University'],
                ['institution' => 'College'],
                'Univer',
                'University',
                'Uni',
                'sity',
            ],
            [
                userdeletefilter::PREFIX_STD . 'city',
                ['city' => 'Springfield'],
                ['city' => 'Shelbyville'],
                'Spring',
                'Springfield',
                'Spring',
                'field',
            ],
            [
                userdeletefilter::PREFIX_STD . 'country',
                ['country' => 'DE'],
                ['country' => 'US'],
                'D',
                'DE',
                'D',
                'E',
            ],
        ];

        $cases = [];
        foreach ($fieldconfigs as [$field, $targetprops, $otherprops, $containsval, $equalsval, $startswith, $endswith]) {
            // phpcs:disable
            $f = substr($field, strlen(userdeletefilter::PREFIX_STD));
            $cases["{$f}/contains"] = [$field, $targetprops, $otherprops, matchmode::CONTAINS->value, $containsval, true, false];
            $cases["{$f}/not_contains"] = [$field, $targetprops, $otherprops, matchmode::NOT_CONTAINS->value, $containsval, false, true];
            $cases["{$f}/equals"] = [$field, $targetprops, $otherprops, matchmode::EQUALS->value, $equalsval, true, false];
            $cases["{$f}/not_equals"] = [$field, $targetprops, $otherprops, matchmode::NOT_EQUALS->value, $equalsval, false, true];
            $cases["{$f}/starts_with"] = [$field, $targetprops, $otherprops, matchmode::STARTS_WITH->value, $startswith, true, false];
            $cases["{$f}/ends_with"] = [$field, $targetprops, $otherprops, matchmode::ENDS_WITH->value, $endswith, true, false];
            $cases["{$f}/not_empty"] = [$field, $targetprops, $otherprops, matchmode::NOT_EMPTY->value, '', true, true];
            // phpcs:enable
        }

        return $cases;
    }

    /**
     * Tests a single match mode against a standard user table field.
     *
     * @covers \userdeletefilter_profilefield\userdeletefilter
     * @dataProvider provide_standard_fields_and_match_modes
     *
     * @param string $field The standard field key to filter on
     * @param array $targetprops User creation props for the target user
     * @param array $otherprops User creation props for the other / inverse user
     * @param string $mode The match mode to apply
     * @param string $value The comparison value
     * @param bool $expecttarget Whether the target user should be matched
     * @param bool $expectother Whether the other user should be matched
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_standard_field(
        string $field,
        array $targetprops,
        array $otherprops,
        string $mode,
        string $value,
        bool $expecttarget,
        bool $expectother
    ): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $target = $this->getDataGenerator()->create_user($targetprops);
        $other = $this->getDataGenerator()->create_user($otherprops);

        $filter = $this->create_filter($step, ['field' => $field, 'matchmode' => $mode, 'value' => $value]);
        $matched = $this->query_users_matching_clause($filter->user_records_filter_clause());

        if ($expecttarget) {
            $this->assertContains((int) $target->id, $matched, "Field '{$field}' mode '{$mode}': target user must be included");
        } else {
            $this->assertNotContains((int) $target->id, $matched, "Field '{$field}' mode '{$mode}': target user must be excluded");
        }

        if ($expectother) {
            $this->assertContains((int) $other->id, $matched, "Field '{$field}' mode '{$mode}': other user must be included");
        } else {
            $this->assertNotContains((int) $other->id, $matched, "Field '{$field}' mode '{$mode}': other user must be excluded");
        }
    }

    /**
     * Data provider for test_filter_custom_field(). Generates examples for all matching modes.
     *
     * @return array[] Test data for test_filter_custom_field().
     */
    public static function provide_custom_field_match_modes(): array {
        return [
            'contains' => [matchmode::CONTAINS->value, 'neer', 'engineering', null],
            'not_contains' => [matchmode::NOT_CONTAINS->value, 'neer', 'marketing', 'engineering'],
            'equals' => [matchmode::EQUALS->value, 'Engineering', 'engineering', 'marketing'],
            'not_equals' => [matchmode::NOT_EQUALS->value, 'Engineering', 'marketing', 'engineering'],
            'starts_with' => [matchmode::STARTS_WITH->value, 'Eng', 'engineering', 'marketing'],
            'ends_with' => [matchmode::ENDS_WITH->value, 'ing', 'marketing', null],
        ];
    }

    /**
     * Tests a single match mode against a custom user profile field.
     *
     * @covers \userdeletefilter_profilefield\userdeletefilter
     * @dataProvider provide_custom_field_match_modes
     *
     * @param string $mode The match mode to apply
     * @param string $value The comparison value
     * @param string $mustinclude Key of the user expected to appear in the matched set
     * @param string|null $mustexclude Key of the user expected to be absent from the matched set, or null
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_custom_field(
        string $mode,
        string $value,
        string $mustinclude,
        ?string $mustexclude
    ): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $this->getDataGenerator()->create_custom_profile_field(
            ['datatype' => 'text', 'shortname' => 'testdept', 'name' => 'Test Department']
        );

        $users = [
            'engineering' => $this->getDataGenerator()->create_user(['profile_field_testdept' => 'Engineering']),
            'marketing' => $this->getDataGenerator()->create_user(['profile_field_testdept' => 'Marketing']),
        ];

        $filter = $this->create_filter($step, [
            'field' => userdeletefilter::PREFIX_CUSTOM . 'testdept',
            'matchmode' => $mode,
            'value' => $value,
        ]);
        $matched = $this->query_users_matching_clause($filter->user_records_filter_clause());

        $this->assertContains(
            (int) $users[$mustinclude]->id,
            $matched,
            "Custom field mode '{$mode}': {$mustinclude} user must be included"
        );

        if ($mustexclude !== null) {
            $this->assertNotContains(
                (int) $users[$mustexclude]->id,
                $matched,
                "Custom field mode '{$mode}': {$mustexclude} user must be excluded"
            );
        }
    }

    /**
     * Tests that the empty and not_empty match modes correctly identify users with
     * and without custom profile field values.
     *
     * @covers \userdeletefilter_profilefield\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_empty_and_not_empty_modes(): void {
        $this->resetAfterTest();

        // Prepare test harness.
        $step = $this->create_step();
        $this->getDataGenerator()->create_custom_profile_field(
            ['datatype' => 'text', 'shortname' => 'emptytestfield', 'name' => 'Empty Test']
        );

        $withvalue = $this->getDataGenerator()->create_user(['profile_field_emptytestfield' => 'has value']);
        $withempty = $this->getDataGenerator()->create_user(['profile_field_emptytestfield' => '']);
        $norow = $this->getDataGenerator()->create_user();

        // Test empty.
        $emptyfilter = $this->create_filter($step, [
            'field' => userdeletefilter::PREFIX_CUSTOM . 'emptytestfield',
            'matchmode' => matchmode::EMPTY->value,
            'value' => '',
        ]);
        $emptymatched = $this->query_users_matching_clause($emptyfilter->user_records_filter_clause());

        $this->assertNotContains((int) $withvalue->id, $emptymatched, 'Empty mode must exclude users with a value');
        $this->assertContains((int) $withempty->id, $emptymatched, 'Empty mode must include users with an empty value');
        $this->assertContains((int) $norow->id, $emptymatched, 'Empty mode must include users with no field row at all');

        // Test non_empty.
        $notemptyfilter = $this->create_filter($step, [
            'field' => userdeletefilter::PREFIX_CUSTOM . 'emptytestfield',
            'matchmode' => matchmode::NOT_EMPTY->value,
            'value' => '',
        ]);
        $notemptymatched = $this->query_users_matching_clause($notemptyfilter->user_records_filter_clause());

        $this->assertContains((int) $withvalue->id, $notemptymatched, 'Not-empty mode must include users with a value');
        $this->assertNotContains((int) $withempty->id, $notemptymatched, 'Not-empty mode must exclude users with an empty value');
        $this->assertNotContains((int) $norow->id, $notemptymatched, 'Not-empty mode must exclude users with no field row at all');
    }

    /**
     * Data provider for test_filter_negation_includes_users_with_no_field_row().
     *
     * @return array[]
     */
    public static function provide_negation_modes(): array {
        return [
            'not_contains' => [matchmode::NOT_CONTAINS->value],
            'not_equals' => [matchmode::NOT_EQUALS->value],
        ];
    }

    /**
     * Tests that a negated match mode includes users who have no custom profile
     * field row at all.
     *
     * @covers \userdeletefilter_profilefield\userdeletefilter
     * @dataProvider provide_negation_modes
     *
     * @param string $mode The negation match mode to test
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_filter_negation_includes_users_with_no_field_row(string $mode): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $this->getDataGenerator()->create_custom_profile_field(
            ['datatype' => 'text', 'shortname' => 'negtestfield', 'name' => 'Negation Test']
        );

        $withmatch = $this->getDataGenerator()->create_user(['profile_field_negtestfield' => 'Engineering']);
        $withother = $this->getDataGenerator()->create_user(['profile_field_negtestfield' => 'Marketing']);
        $norow = $this->getDataGenerator()->create_user();

        $filter = $this->create_filter($step, [
            'field' => userdeletefilter::PREFIX_CUSTOM . 'negtestfield',
            'matchmode' => $mode,
            'value' => 'Engineering',
        ]);
        $matched = $this->query_users_matching_clause($filter->user_records_filter_clause());

        $this->assertNotContains((int) $withmatch->id, $matched, "Mode '{$mode}': matching user must be excluded");
        $this->assertContains((int) $withother->id, $matched, "Mode '{$mode}': non-matching user must be included");
        $this->assertContains((int) $norow->id, $matched, "Mode '{$mode}': user with no field row must be included");
    }

    /**
     * Tests that the filter is invalid when required settings are missing and valid
     * once field and matchmode are configured with a matching value.
     *
     * @covers \userdeletefilter_profilefield\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_validity(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        // No settings at all: invalid.
        $empty = $this->create_filter($step);
        $this->assertFalse($empty->is_valid(), 'Filter without settings must be invalid');

        // Only matchmode set (field missing): invalid.
        $nomatchfield = $this->create_filter($step, ['matchmode' => 'contains']);
        $this->assertFalse($nomatchfield->is_valid(), 'Filter without field must be invalid');

        // Valid instance: field + matchmode + value set.
        $valid = $this->create_valid_filter_instance($step);
        $this->assertTrue($valid->is_valid(), 'Filter with all required settings must be valid');
    }

    /**
     * Tests that get_instance_details() returns an empty string when no settings
     * are configured and a descriptive string when all settings are present.
     *
     * @covers \userdeletefilter_profilefield\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details(): void {
        $this->resetAfterTest();

        $step = $this->create_step();

        $empty = $this->create_filter($step);
        $this->assertSame('', $empty->get_instance_details(), 'Unconfigured filter must return empty instance details');

        $configured = $this->create_valid_filter_instance($step);
        $this->assertNotEmpty($configured->get_instance_details(), 'Configured filter must return non-empty instance details');
    }

    /**
     * Data provider for test_validate_detects_missing_value().
     *
     * Yields every matchmode paired with whether it requires a comparison value,
     * derived from the matchmode enum so that the test stays in sync with any
     * future mode additions automatically.
     *
     * @return array[]
     */
    public static function provide_modes_with_value_requirements(): array {
        $result = [];
        foreach (matchmode::cases() as $mode) {
            $result[$mode->value] = [$mode->value, $mode->requires_value()];
        }
        return $result;
    }

    /**
     * Tests that validate_instance_settings_data() errors on a missing value for modes
     * that require one and passes for modes that do not.
     *
     * @dataProvider provide_modes_with_value_requirements
     * @covers \userdeletefilter_profilefield\userdeletefilter
     *
     * @param string $mode The match mode to validate
     * @param bool $requiresvalue Whether this mode requires a comparison value
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_validate_detects_missing_value(string $mode, bool $requiresvalue): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $filter = $this->create_valid_filter_instance($step);

        $errors = $filter->validate_instance_settings_data([
            'field' => userdeletefilter::PREFIX_STD . 'firstname',
            'matchmode' => $mode,
            'value' => '',
        ]);

        if ($requiresvalue) {
            $this->assertArrayHasKey('value', $errors, "Mode '{$mode}' must produce a validation error when value is empty");
        } else {
            $this->assertArrayNotHasKey('value', $errors, "Mode '{$mode}' must not require a value");
        }
    }

    /**
     * Tests that validate() returns an error when the stored profile field key no
     * longer exists (e.g. a custom field was deleted after the filter was saved).
     *
     * @covers \userdeletefilter_profilefield\userdeletefilter
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_validate_detects_missing_field(): void {
        global $DB;

        $this->resetAfterTest();

        $step = $this->create_step();
        $field = $this->getDataGenerator()->create_custom_profile_field(
            ['datatype' => 'text', 'shortname' => 'deletedfield', 'name' => 'Deleted Field']
        );

        $filter = $this->create_filter($step, [
            'field' => userdeletefilter::PREFIX_CUSTOM . 'deletedfield',
            'matchmode' => matchmode::NOT_EMPTY->value,
            'value' => '',
        ]);

        $this->assertNull($filter->validate(), 'Filter must be valid when the profile field exists');

        // Delete the profile field to simulate it being removed from the system.
        $DB->delete_records('user_info_field', ['id' => $field->id]);

        $this->assertNotNull($filter->validate(), 'Filter must be invalid after the referenced profile field is deleted');
    }
}
