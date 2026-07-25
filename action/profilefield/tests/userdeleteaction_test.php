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
 * Unit tests for the userdeleteaction_profilefield sub-plugin
 *
 * @package     userdeleteaction_profilefield
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_profilefield;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeleteaction_testcase.php');


/**
 * Unit tests for the userdeleteaction_profilefield sub-plugin
 */
final class userdeleteaction_test extends \tool_userautodelete\userdeleteaction_testcase {
    /**
     * Returns the short plugin name of the action sub-plugin under test.
     */
    protected function get_plugin_name(): string {
        return 'profilefield';
    }

    /**
     * Returns the expected font-awesome icon CSS class string for the action
     * sub-plugin under test.
     */
    protected function get_expected_icon_class(): string {
        return 'fa-solid fa-user-pen';
    }

    /**
     * Tests that execute() sets a standard user field and returns true.
     *
     * @covers \userdeleteaction_profilefield\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user(['firstname' => 'Original']);
        $step = $this->create_step();
        $action = $this->create_action($step, [
            'field' => userdeleteaction::PREFIX_STD . 'firstname',
            'value' => 'Updated',
        ]);
        $process = $this->create_process((int) $user->id, $step);

        $result = $action->execute($process);
        $this->assertTrue($result, 'profilefield action execute() must return true on success');

        $this->assertSame(
            'Updated',
            $DB->get_field('user', 'firstname', ['id' => $user->id]),
            'User firstname must be updated after execute()'
        );
    }

    /**
     * Provides standard field names and test values for parameterized testing.
     *
     * @return array<string, array{string, string}> Test cases with field suffix and value
     */
    public static function standard_field_provider(): array {
        return [
            'firstname' => ['firstname', 'TestFirst'],
            'lastname' => ['lastname', 'TestLast'],
            'idnumber' => ['idnumber', 'TESTID-001'],
            'department' => ['department', 'Engineering'],
            'institution' => ['institution', 'Test University'],
            'city' => ['city', 'Berlin'],
            'country' => ['country', 'DE'],
        ];
    }

    /**
     * Tests that execute() correctly sets all supported standard user fields.
     *
     * @covers \userdeleteaction_profilefield\userdeleteaction
     *
     * @dataProvider standard_field_provider
     *
     * @param string $fieldsuffix The standard field name without prefix
     * @param string $value The value to set the field to
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_sets_standard_field_values(string $fieldsuffix, string $value): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $step = $this->create_step();
        $action = $this->create_action($step, [
            'field' => userdeleteaction::PREFIX_STD . $fieldsuffix,
            'value' => $value,
        ]);
        $process = $this->create_process((int) $user->id, $step);

        $result = $action->execute($process);
        $this->assertTrue($result, "profilefield action execute() must return true for field '{$fieldsuffix}'");

        $this->assertSame(
            $value,
            $DB->get_field('user', $fieldsuffix, ['id' => $user->id]),
            "User field '{$fieldsuffix}' must be updated after execute()"
        );
    }

    /**
     * Tests that execute() correctly sets a custom profile field value.
     *
     * @covers \userdeleteaction_profilefield\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_sets_custom_profile_field(): void {
        global $DB;
        $this->resetAfterTest();

        // Create a custom text profile field.
        $generator = $this->getDataGenerator();
        $generator->create_custom_profile_field([
            'shortname' => 'testfield',
            'name' => 'Test Field',
            'datatype' => 'text',
        ]);

        $user = $generator->create_user();
        $step = $this->create_step();
        $action = $this->create_action($step, [
            'field' => userdeleteaction::PREFIX_CUSTOM . 'testfield',
            'value' => 'CustomValue',
        ]);
        $process = $this->create_process((int) $user->id, $step);

        $result = $action->execute($process);
        $this->assertTrue($result, 'profilefield action execute() must return true for custom field');

        $fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => 'testfield'], MUST_EXIST);
        $storedvalue = $DB->get_field('user_info_data', 'data', ['userid' => $user->id, 'fieldid' => $fieldid]);
        $this->assertSame('CustomValue', $storedvalue, 'Custom profile field value must be stored in user_info_data');
    }

    /**
     * Tests that execute() overwrites an existing custom profile field value.
     *
     * @covers \userdeleteaction_profilefield\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_overwrites_existing_custom_field_value(): void {
        global $DB;
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $generator->create_custom_profile_field([
            'shortname' => 'overwritefield',
            'name' => 'Overwrite Field',
            'datatype' => 'text',
        ]);

        $user = $generator->create_user(['profile_field_overwritefield' => 'OriginalValue']);
        $step = $this->create_step();
        $action = $this->create_action($step, [
            'field' => userdeleteaction::PREFIX_CUSTOM . 'overwritefield',
            'value' => 'NewValue',
        ]);
        $process = $this->create_process((int) $user->id, $step);

        $result = $action->execute($process);
        $this->assertTrue($result, 'profilefield action execute() must return true on overwrite');

        $fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => 'overwritefield'], MUST_EXIST);
        $storedvalue = $DB->get_field('user_info_data', 'data', ['userid' => $user->id, 'fieldid' => $fieldid]);
        $this->assertSame('NewValue', $storedvalue, 'Custom profile field value must be overwritten');
    }

    /**
     * Tests that execute() can clear a standard field by setting an empty value.
     *
     * @covers \userdeleteaction_profilefield\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_clears_standard_field_value(): void {
        global $DB;
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user(['department' => 'Marketing']);
        $step = $this->create_step();
        $action = $this->create_action($step, [
            'field' => userdeleteaction::PREFIX_STD . 'department',
            'value' => '',
        ]);
        $process = $this->create_process((int) $user->id, $step);

        $result = $action->execute($process);
        $this->assertTrue($result, 'profilefield action execute() must return true when clearing a field');

        $this->assertSame(
            '',
            $DB->get_field('user', 'department', ['id' => $user->id]),
            'Standard field must be empty after setting an empty value'
        );
    }

    /**
     * Tests that a default instance (no field selected) is considered invalid,
     * and becomes valid once a field is configured.
     *
     * @covers \userdeleteaction_profilefield\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_validity_requires_field(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertFalse($action->is_valid(), 'profilefield action without field must be invalid');

        $action->set_instance_setting('field', userdeleteaction::PREFIX_STD . 'firstname');
        $this->assertTrue($action->is_valid(), 'profilefield action with field set must be valid');
    }

    /**
     * Tests that validate() returns an error when the configured field no longer exists.
     *
     * @covers \userdeleteaction_profilefield\userdeleteaction
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_validate_returns_error_for_deleted_field(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step, ['field' => 'custom:nonexistent_field']);

        $error = $action->validate();
        $this->assertNotNull($error, 'validate() must return an error for a non-existent field');
        $this->assertStringContainsString(
            get_string('error_field_not_found', 'userdeleteaction_profilefield'),
            $error,
            'validate() error must reference field_not_found string'
        );
    }

    /**
     * Tests that validate_instance_settings_data() returns an error for an unknown field key.
     *
     * @covers \userdeleteaction_profilefield\userdeleteaction
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_validate_instance_settings_data_rejects_unknown_field(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $errors = $action->validate_instance_settings_data(['field' => 'custom:nonexistent', 'value' => '']);
        $this->assertArrayHasKey('field', $errors, 'validate_instance_settings_data() must return field error for unknown field');
        $this->assertSame(
            get_string('error_field_not_found', 'userdeleteaction_profilefield'),
            $errors['field']
        );
    }

    /**
     * Tests that get_instance_details() returns a string containing the field label and value.
     *
     * @covers \userdeleteaction_profilefield\userdeleteaction
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details_returns_expected_string(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step, [
            'field' => userdeleteaction::PREFIX_STD . 'department',
            'value' => 'Alumni',
        ]);

        $details = $action->get_instance_details();

        $this->assertStringContainsString(
            get_string('field_std_department', 'userdeleteaction_profilefield'),
            $details,
            'Instance details must contain the field label'
        );
        $this->assertStringContainsString(
            'Alumni',
            $details,
            'Instance details must contain the configured value'
        );
    }

    /**
     * Tests that get_instance_details() returns an empty string when no field is configured.
     *
     * @covers \userdeleteaction_profilefield\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details_returns_empty_without_field(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertSame(
            '',
            $action->get_instance_details(),
            'profilefield action get_instance_details() must return empty string when no field is configured'
        );
    }

    /**
     * Tests that validate_instance_settings_data() rejects an invalid country code when
     * the country field is selected.
     *
     * @covers \userdeleteaction_profilefield\userdeleteaction
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_validate_instance_settings_data_rejects_invalid_country_code(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $errors = $action->validate_instance_settings_data([
            'field' => userdeleteaction::PREFIX_STD . 'country',
            'value' => 'INVALID',
        ]);

        $this->assertArrayHasKey(
            'value',
            $errors,
            'validate_instance_settings_data() must return a value error for an invalid country code'
        );
        $this->assertSame(
            get_string('error_invalid_country_code', 'userdeleteaction_profilefield'),
            $errors['value']
        );
    }

    /**
     * Tests that validate_instance_settings_data() accepts a valid ISO 3166-1 alpha-2 country code.
     *
     * @covers \userdeleteaction_profilefield\userdeleteaction
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_validate_instance_settings_data_accepts_valid_country_code(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $errors = $action->validate_instance_settings_data([
            'field' => userdeleteaction::PREFIX_STD . 'country',
            'value' => 'DE',
        ]);

        $this->assertArrayNotHasKey(
            'value',
            $errors,
            'validate_instance_settings_data() must not return an error for a valid country code'
        );
    }

    /**
     * Tests that validate_instance_settings_data() accepts an empty country value
     * (clearing the country field is valid).
     *
     * @covers \userdeleteaction_profilefield\userdeleteaction
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_validate_instance_settings_data_accepts_empty_country_value(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $errors = $action->validate_instance_settings_data([
            'field' => userdeleteaction::PREFIX_STD . 'country',
            'value' => '',
        ]);

        $this->assertArrayNotHasKey(
            'value',
            $errors,
            'validate_instance_settings_data() must allow empty country value to clear the field'
        );
    }

    /**
     * Tests that validate() returns an error when the country field holds an invalid code.
     *
     * @covers \userdeleteaction_profilefield\userdeleteaction
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_validate_returns_error_for_invalid_country_code(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step, [
            'field' => userdeleteaction::PREFIX_STD . 'country',
            'value' => 'XX',
        ]);

        $error = $action->validate();
        $this->assertNotNull($error, 'validate() must return an error for an invalid country code');
        $this->assertSame(
            get_string('error_invalid_country_code', 'userdeleteaction_profilefield'),
            $error
        );
    }
}
