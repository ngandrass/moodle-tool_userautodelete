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
 * Unit tests for the userdeleteaction_anonymize sub-plugin
 *
 * @package   userdeleteaction_anonymize
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_anonymize;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeleteaction_testcase.php');


/**
 * Unit tests for the userdeleteaction_anonymize sub-plugin
 */
final class userdeleteaction_test extends \tool_userautodelete\userdeleteaction_testcase {
    /**
     * Returns the short plugin name of the action sub-plugin under test.
     */
    protected function get_plugin_name(): string {
        return 'anonymize';
    }

    /**
     * Returns the expected font-awesome icon CSS class string for the action
     * sub-plugin under test, e.g. 'fa-solid fa-gear'.
     */
    protected function get_expected_icon_class(): string {
        return 'fa-solid fa-user-secret';
    }

    /**
     * Tests that execute() replaces all PII fields with anonymized placeholder
     * values and returns true.
     *
     * @covers \userdeleteaction_anonymize\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute(): void {
        global $DB;

        $this->resetAfterTest();

        // Create a user that carries identifiable data in every anonymized field.
        $user = $this->getDataGenerator()->create_user([
            'firstname'   => 'John',
            'lastname'    => 'Doe',
            'email'       => 'john.doe@example.com',
            'phone1'      => '+1234567890',
            'phone2'      => '+9876543210',
            'institution' => 'My Corp',
            'department'  => 'Engineering',
            'address'     => '1 Main Street',
            'city'        => 'Berlin',
            'country'     => 'DE',
            'lastip'      => '192.168.1.1',
            'description' => 'Personal bio text',
            'imagealt'    => 'Profile image alt text',
        ]);

        $step = $this->create_step();
        $action = $this->create_action($step);
        $process = $this->create_process((int) $user->id, $step);

        // Execute and assert it signals success.
        $result = $action->execute($process);
        $this->assertTrue($result, 'anonymize action execute() must return true on success');

        // Reload the user record and verify every PII field has been anonymized.
        $anonymized = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);

        $this->assertSame(
            "DELETED-USER-{$user->id}",
            $anonymized->username,
            'Username must be set to DELETED-USER-{id}'
        );
        $this->assertSame(AUTH_PASSWORD_NOT_CACHED, $anonymized->password, 'Password hash must be cleared');
        $this->assertSame('', $anonymized->idnumber, 'ID number must be cleared');
        $this->assertSame('DELETED', $anonymized->firstname, 'First name must be anonymized to DELETED');
        $this->assertSame('DELETED', $anonymized->lastname, 'Last name must be anonymized to DELETED');
        $this->assertSame(
            "DELETED-USER-{$user->id}@localhost",
            $anonymized->email,
            'Email must be set to DELETED-USER-{id}@localhost'
        );
        $this->assertSame('', $anonymized->phone1, 'Phone1 must be cleared');
        $this->assertSame('', $anonymized->phone2, 'Phone2 must be cleared');
        $this->assertSame('', $anonymized->institution, 'Institution must be cleared');
        $this->assertSame('', $anonymized->department, 'Department must be cleared');
        $this->assertSame('', $anonymized->address, 'Address must be cleared');
        $this->assertSame('', $anonymized->city, 'City must be cleared');
        $this->assertSame('', $anonymized->country, 'Country must be cleared');
        $this->assertSame('', $anonymized->lastip, 'Last IP must be cleared');
        $this->assertSame('', $anonymized->description, 'Description must be cleared');
        $this->assertSame('', $anonymized->imagealt, 'Image alt text must be cleared');
        $this->assertSame(0, (int) $anonymized->picture, 'Profile picture flag must be reset to 0');
    }

    /**
     * Tests that a default instance (no required settings) is considered valid.
     *
     * @covers \userdeleteaction_anonymize\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_default_instance_is_valid(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertTrue($action->is_valid(), 'anonymize action without settings must be valid by default');
    }

    /**
     * Tests that get_instance_details() returns an empty string (no settings).
     *
     * @covers \userdeleteaction_anonymize\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details_returns_empty_string(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertSame('', $action->get_instance_details(), 'anonymize action get_instance_details() must return empty string');
    }
}
