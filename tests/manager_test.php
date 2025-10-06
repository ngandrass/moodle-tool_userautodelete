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
 * Tests for the manager class
 *
 * @package   tool_userautodelete
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;


/**
 * Tests for the manager class
 */
final class manager_test extends \advanced_testcase {

    /**
     * This method is called before each test.
     */
    protected function setUp(): void {
        parent::setUp();

        // Disable logger by default.
        logger::disable();

        // Enable plugin by default.
        set_config('enable', true, 'tool_userautodelete');
    }

    /**
     * Tests that the user deletion tasks are not executed when the plugin is disabled
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_prevent_run_if_disabled(): void {
        $this->resetAfterTest();
        logger::enable();
        set_config('enable', false, 'tool_userautodelete');

        $manager = new manager();
        $res = $manager->execute();

        $this->expectOutputString('[INFO] '.get_string('plugin_disabled_skipping_execution', 'tool_userautodelete')."\n");
        $this->assertSame(false, $res, 'Execution was not prevented');
    }

    /**
     * Tests that the config values can be read
     *
     * @covers \tool_userautodelete\manager::get_config
     *
     * @dataProvider get_config_data_provider
     *
     * @param string $key Key of the config entry to check
     * @param bool $expectvalue True if the config value is expected to be set
     * @return void
     */
    public function test_get_config(string $key, bool $expectvalue): void {
        $this->resetAfterTest();

        $manager = new manager();
        $value = $manager->get_config($key);

        if ($expectvalue) {
            $this->assertNotEmpty($value, 'Valid config value is empty');
        } else {
            $this->assertNull($value, 'Invalid config value was found');
        }
    }

    /**
     * Data provider for test_get_config()
     *
     * @return array[] Test data
     */
    public static function get_config_data_provider(): array {
        return [
            'Valid: delete_threshold_days' => ['delete_threshold_days', true],
            'Valid: warning_threshold_days' => ['warning_threshold_days', true],
            'Valid: ignore_siteadmins' => ['ignore_siteadmins', true],
            'Invalid: foo' => ['foo', false],
            'Invalid: bar' => ['bar', false],
            'Invalid: lorem_ipsum' => ['lorem_ipsum', false],
        ];
    }

    /**
     * Tests that the user deletion tasks are not executed when the config is invalid
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_prevent_run_if_config_invalid(): void {
        $this->resetAfterTest();
        logger::enable();
        set_config('delete_threshold_days', 0, 'tool_userautodelete');

        $manager = new manager();
        $res = $manager->execute();

        $this->expectOutputRegex('/\[ERROR\]/');
        $this->assertSame(false, $res, 'Execution was not prevented');
    }

    /**
     * Tests that an invalid config is detected and logged as an error
     *
     * @covers \tool_userautodelete\manager::validate_config
     * @dataProvider error_on_invalid_config_data_provider
     *
     * @param string $configkey Key of the config entry to alter
     * @param mixed $configvalue Value to set the config entry to
     * @return void
     * @throws \coding_exception
     */
    public function test_error_on_invalid_config(string $configkey, $configvalue): void {
        $this->resetAfterTest();
        logger::enable();
        set_config('warning_email_enable', true, 'tool_userautodelete');
        set_config('delete_email_enable', true, 'tool_userautodelete');
        set_config($configkey, $configvalue, 'tool_userautodelete');

        $manager = new manager();
        $res = $manager->validate_config();

        $this->assertSame(false, $res, 'Invalid config was found valid');
        $this->expectOutputRegex('/\[ERROR\]/');
    }

    /**
     * Data provider for test_error_on_invalid_config()
     *
     * @return array[] Test data
     */
    public static function error_on_invalid_config_data_provider(): array {
        return [
            'Invalid delete_threshold_days (zero)' => ['delete_threshold_days', 0],
            'Invalid delete_threshold_days (negative)' => ['delete_threshold_days', -42],
            'Invalid warning_threshold_days (zero)' => ['warning_threshold_days', 0],
            'Invalid warning_threshold_days (negative)' => ['warning_threshold_days', -42],
            'Invalid ignore_roles' => ['ignore_roles', 'roleshortname,anotherrole'],
            'Invalid warning_email_subject' => ['warning_email_subject', ''],
            'Invalid delete_email_subject' => ['delete_email_subject', ''],
        ];
    }

    /**
     * Tests that a warning is logged if the config is suspicious
     *
     * @covers \tool_userautodelete\manager::validate_config
     * @dataProvider warning_on_invalid_config_data_provider
     *
     * @param string $configkey Key of the config entry to alter
     * @param mixed $configvalue Value to set the config entry to
     * @return void
     * @throws \coding_exception
     */
    public function test_warning_on_invalid_config(string $configkey, $configvalue): void {
        $this->resetAfterTest();
        logger::enable();
        set_config('warning_email_enable', true, 'tool_userautodelete');
        set_config('delete_email_enable', true, 'tool_userautodelete');
        set_config($configkey, $configvalue, 'tool_userautodelete');

        $manager = new manager();
        $res = $manager->validate_config();

        $this->assertSame(true, $res, 'Valid config was found invalid');
        $this->expectOutputRegex('/\[WARN\]/');
    }

    /**
     * Data provider for test_warning_on_invalid_config()
     *
     * @return array[] Test data
     */
    public static function warning_on_invalid_config_data_provider(): array {
        return [
            'Invalid warning_email_body' => ['warning_email_body', ''],
            'Invalid delete_email_body' => ['delete_email_body', ''],
        ];
    }

    /**
     * Tests that inactive users are deleted
     *
     * @covers \tool_userautodelete\manager
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_deletion_of_inactive_user(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('delete_threshold_days', 365, 'tool_userautodelete');

        $user = $this->getDataGenerator()->create_user(['lastaccess' => time() - DAYSECS * 366]);
        $numactiveusersbefore = $DB->count_records('user', ['deleted' => 0]);

        $manager = new manager();
        $res = $manager->execute();

        $this->assertSame(true, $res, 'Execution was not successful');
        $this->assertSame('1', $DB->get_field('user', 'deleted', ['id' => $user->id]), 'User was not deleted');
        $this->assertSame($numactiveusersbefore - 1, $DB->count_records('user', ['deleted' => 0]), 'Wrong users were deleted');
    }

    /**
     * Tests that users that never logged in will not be deleted straight away
     *
     * @covers \tool_userautodelete\manager
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_new_user_exclusion(): void {
        global $DB;
        $this->resetAfterTest();

        $newuser = $this->getDataGenerator()->create_user(['lastaccess' => 0]);

        $manager = new manager();
        $manager->execute();

        $this->assertSame('0', $DB->get_field('user', 'deleted', ['id' => $newuser->id]), 'New user was falsely deleted');
    }

    /**
     * Tests that users that never logged in are still deleted once their account
     * creation date exceeded the deletion threshold
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_deleting_users_never_logged_in(): void {
        global $DB;
        $this->resetAfterTest();

        set_config('delete_threshold_days', 365, 'tool_userautodelete');
        $user = $this->getDataGenerator()->create_user(['lastaccess' => 0, 'timecreated' => time() - DAYSECS * 366]);

        $manager = new manager();
        $manager->execute();

        $this->assertSame('1', $DB->get_field('user', 'deleted', ['id' => $user->id]), 'User was not deleted');
    }

    /**
     * Tests that all site admins are excluded from deletion
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_site_admins_exclusion(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        set_config('ignore_siteadmins', true, 'tool_userautodelete');
        set_config('delete_threshold_days', 100, 'tool_userautodelete');

        // Create additional admin users.
        $admins = [];
        foreach (range(1, 3) as $i) {
            $admins[] = $this->getDataGenerator()->create_user([
                'username' => 'anotheradmin'.$i,
                'lastaccess' => time() - DAYSECS * 101,
            ]);
        }
        $newsiteadmins = $CFG->siteadmins.','.implode(',', array_map(fn($admin) => $admin->id, $admins));
        set_config('siteadmins', $newsiteadmins);
        $CFG->siteadmins = $newsiteadmins;

        $numactiveusersbefore = $DB->count_records('user', ['deleted' => 0]);

        // Execute deletion routine and check if admins were deleted.
        $manager = new manager();
        $manager->execute();

        foreach ($admins as $admin) {
            $this->assertSame('0', $DB->get_field('user', 'deleted', ['id' => $admin->id]), 'Admin user was deleted');
        }
        $this->assertSame($numactiveusersbefore, $DB->count_records('user', ['deleted' => 0]), 'Wrong users were deleted');
    }

    /**
     * Tests that users that are excluded by role are not deleted
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_user_exclusion_by_role(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('delete_threshold_days', 60, 'tool_userautodelete');
        set_config('warning_threshold_days', 30, 'tool_userautodelete');

        // Create a nodelete role and assign users to it. Also create users that does not have this role.
        $roleid = $this->getDataGenerator()->create_role(['shortname' => 'nodelete']);
        $roleusers = [];
        for ($i = 0; $i < 5; $i++) {
            $user = $this->getDataGenerator()->create_user(['lastaccess' => time() - DAYSECS * (31 + $i)]);
            $this->getDataGenerator()->role_assign($roleid, $user->id);
            $roleusers[] = $user;
        }

        $noroleusers = [];
        for ($i = 0; $i < 5; $i++) {
            $noroleusers[] = $this->getDataGenerator()->create_user(['lastaccess' => time() - DAYSECS * (31 + $i)]);
        }

        // Execute check routine and validate if excluded user did not receive a warning.
        set_config('ignore_roles', $roleid, 'tool_userautodelete');
        $manager = new manager();
        $manager->execute();

        // Ensure that the user with the role did not receive a warning message.
        foreach ($roleusers as $roleuser) {
            $this->assertFalse(
                $DB->record_exists('tool_userautodelete_mail', ['userid' => $roleuser->id]),
                'User with excluded role received a warning message'
            );
        }
        foreach ($noroleusers as $noroleuser) {
            $this->assertTrue(
                $DB->record_exists('tool_userautodelete_mail', ['userid' => $noroleuser->id]),
                'User without excluded role did not receive a warning message'
            );
        }

        // Turn the clock and execute the deletion routine.
        foreach (array_merge($roleusers, $noroleusers) as $user) {
            $DB->set_field('user', 'lastaccess', time() - DAYSECS * 61, ['id' => $user->id]);
        }
        $manager->execute();

        // Ensure that the user with the role was not deleted.
        foreach ($roleusers as $roleuser) {
            $this->assertSame(
                '0',
                $DB->get_field('user', 'deleted', ['id' => $roleuser->id]),
                'User with excluded role was deleted'
            );
        }
        foreach ($noroleusers as $noroleuser) {
            $this->assertSame(
                '1',
                $DB->get_field('user', 'deleted', ['id' => $noroleuser->id]),
                'User without excluded role was not deleted'
            );
        }
    }

    /**
     * Tests that users that have an auth that is ignored are excluded from receiving warning messages.
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_user_warning_exclusion_by_auth(): void {
        $this->resetAfterTest();
        set_config('delete_threshold_days', 60, 'tool_userautodelete');
        set_config('warning_threshold_days', 30, 'tool_userautodelete');

        // Create users to warn with different auth methods.
        $usermanual = $this->getDataGenerator()->create_user(['auth' => 'manual', 'lastaccess' => time() - DAYSECS * 31]);
        $useremail = $this->getDataGenerator()->create_user(['auth' => 'email', 'lastaccess' => time() - DAYSECS * 31]);
        $usernologin = $this->getDataGenerator()->create_user(['auth' => 'nologin', 'lastaccess' => time() - DAYSECS * 31]);

        // Ensure that all users are considered if no auth method is ignored.
        set_config('ignore_auths', '', 'tool_userautodelete');
        $manager = new manager();
        $useridstowarn = array_map(fn($u) => $u->id, $manager->get_users_to_warn());
        $this->assertEqualsCanonicalizing(
            [$usermanual->id, $useremail->id, $usernologin->id],
            $useridstowarn,
           'Expected all users to be warned if no auth is ignored'
        );

        // Ignore the manual user.
        set_config('ignore_auths', 'manual', 'tool_userautodelete');
        $manager = new manager();
        $useridstowarn = array_map(fn($u) => $u->id, $manager->get_users_to_warn());
        $this->assertEqualsCanonicalizing(
            [$useremail->id, $usernologin->id],
            $useridstowarn,
            'Expected the manual auth user to be ignored when warning'
        );

        // Ignore maunal and nologin users.
        set_config('ignore_auths', 'manual,nologin', 'tool_userautodelete');
        $manager = new manager();
        $useridstowarn = array_map(fn($u) => $u->id, $manager->get_users_to_warn());
        $this->assertEqualsCanonicalizing(
            [$useremail->id],
            $useridstowarn,
            'Expected the manual and nologin auth users to be ignored when warning'
        );
    }

    /**
     * Tests that users that have an auth that is ignored are excluded from deletion.
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_user_deletion_exclusion_by_auth(): void {
        $this->resetAfterTest();
        set_config('delete_threshold_days', 30, 'tool_userautodelete');
        set_config('warning_threshold_days', 15, 'tool_userautodelete');

        // Create users to warn with different auth methods.
        $usermanual = $this->getDataGenerator()->create_user(['auth' => 'manual', 'lastaccess' => time() - DAYSECS * 31]);
        $useremail = $this->getDataGenerator()->create_user(['auth' => 'email', 'lastaccess' => time() - DAYSECS * 31]);
        $usernologin = $this->getDataGenerator()->create_user(['auth' => 'nologin', 'lastaccess' => time() - DAYSECS * 31]);

        // Ensure that all users are considered if no auth method is ignored.
        set_config('ignore_auths', '', 'tool_userautodelete');
        $manager = new manager();
        $useridstodelete = array_map(fn($u) => $u->id, $manager->get_users_to_delete());
        $this->assertEqualsCanonicalizing(
            [$usermanual->id, $useremail->id, $usernologin->id],
            $useridstodelete,
            'Expected all users to be deleted if no auth is ignored'
        );

        // Ignore the manual user.
        set_config('ignore_auths', 'manual', 'tool_userautodelete');
        $manager = new manager();
        $useridstodelete = array_map(fn($u) => $u->id, $manager->get_users_to_delete());
        $this->assertEqualsCanonicalizing(
            [$useremail->id, $usernologin->id],
            $useridstodelete,
            'Expected the manual auth user to be ignored during deletion'
        );

        // Ignore maunal and nologin users.
        set_config('ignore_auths', 'manual,nologin', 'tool_userautodelete');
        $manager = new manager();
        $useridstodelete = array_map(fn($u) => $u->id, $manager->get_users_to_delete());
        $this->assertEqualsCanonicalizing(
            [$useremail->id],
            $useridstodelete,
            'Expected the manual and nologin auth users to be ignored during deletion'
        );
    }

    /**
     * Tests that users do not receive warning messages prematurely or are deleted too eraly
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_user_not_deleted_premature(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('delete_threshold_days', 10, 'tool_userautodelete');
        set_config('warning_threshold_days', 5, 'tool_userautodelete');

        // Test user that is neither eligible for deletion nor for a warning message.
        $user = $this->getDataGenerator()->create_user(['lastaccess' => time() - DAYSECS * 2]);
        $manager = new manager();
        $manager->execute();
        $this->assertSame('0', $DB->get_field('user', 'deleted', ['id' => $user->id]), 'User was deleted too early');
        $this->assertFalse($DB->record_exists(
            'tool_userautodelete_mail',
            ['userid' => $user->id]
        ), 'User received a warning message too early');

        // Test user that is eligible for a warning message but not for deletion.
        $user = $this->getDataGenerator()->create_user(['lastaccess' => time() - DAYSECS * 6]);
        $manager->execute();
        $this->assertSame('0', $DB->get_field('user', 'deleted', ['id' => $user->id]), 'User was deleted too early');
        $this->assertTrue($DB->record_exists(
            'tool_userautodelete_mail',
            ['userid' => $user->id]
        ), 'Warning message was not sent to user');

        // Test that user is not deleted right after the warning message was sent.
        $manager->execute();
        $this->assertSame('0', $DB->get_field('user', 'deleted', ['id' => $user->id]), 'User was deleted too early');
    }

    /**
     * Tests that users that received a warning message are not deleted if they
     * logged back in after the warning message was sent.
     *
     * @covers \tool_userautodelete\manager
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_user_logged_in_after_warning(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('delete_threshold_days', 10, 'tool_userautodelete');
        set_config('warning_threshold_days', 5, 'tool_userautodelete');

        // Create a user that is eligible for receiving a warning message.
        $user = $this->getDataGenerator()->create_user(['lastaccess' => time() - DAYSECS * 6]);
        $manager = new manager();
        $manager->execute();

        // Ensure that the user received a warning message.
        $this->assertTrue($DB->record_exists(
            'tool_userautodelete_mail',
            ['userid' => $user->id]
        ), 'Warning message was not sent to user');
        $this->assertSame('0', $DB->get_field('user', 'deleted', ['id' => $user->id]), 'User was deleted too early');

        // Shorten deletion threshold but mark user as logged in.
        $DB->set_field('user', 'lastaccess', time(), ['id' => $user->id]);
        set_config('delete_threshold_days', 5, 'tool_userautodelete');
        set_config('warning_threshold_days', 1, 'tool_userautodelete');
        $manager = new manager();
        $manager->execute();

        // Ensure that the user was not deleted.
        $this->assertSame('0', $DB->get_field('user', 'deleted', ['id' => $user->id]), 'User was deleted even after login');

        // Check that the obsolete table entry was removed.
        $this->assertFalse($DB->record_exists(
            'tool_userautodelete_mail',
            ['userid' => $user->id]
        ), 'User entry was not removed from mail table');
    }

    /**
     * Tests that a user that is eligible for receiving a warning message
     * actually receives it
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_user_receives_warning_message(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('delete_threshold_days', 10, 'tool_userautodelete');
        set_config('warning_threshold_days', 5, 'tool_userautodelete');

        // Prepare mail sink.
        unset_config('noemailever');
        $sink = $this->redirectEmails();

        // Create a user that is eligible for receiving a warning message.
        $user = $this->getDataGenerator()->create_user(['lastaccess' => time() - DAYSECS * 6]);
        $manager = new manager();
        $manager->execute();

        // Ensure that the user received a warning message.
        $messages = $sink->get_messages();
        $this->assertCount(1, $messages, 'Warning message was not sent to user');
        $this->assertEquals(
            $user->email,
            $messages[0]->to,
            'Warning message recipient does not match'
        );

        // Check that only a single warning message is sent.
        $DB->set_field('user', 'lastaccess', time() - DAYSECS * 5, ['id' => $user->id]);
        $manager->execute();
        $this->assertCount(1, $sink->get_messages(), 'Multiple warning messages were sent to user');

        $DB->set_field('user', 'lastaccess', time() - DAYSECS * 7, ['id' => $user->id]);
        $manager->execute();
        $this->assertCount(1, $sink->get_messages(), 'Multiple warning messages were sent to user');
    }

    /**
     * Tests that a user does not receive a warning message if warnings are
     * disabled
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_user_receives_no_warning_message(): void {
        $this->resetAfterTest();
        set_config('delete_threshold_days', 10, 'tool_userautodelete');
        set_config('warning_threshold_days', 5, 'tool_userautodelete');
        set_config('warning_email_enable', false, 'tool_userautodelete');

        // Prepare mail sink.
        unset_config('noemailever');
        $sink = $this->redirectEmails();

        // Create a user that is eligible for receiving a warning message.
        $this->getDataGenerator()->create_user(['lastaccess' => time() - DAYSECS * 6]);
        $manager = new manager();
        $manager->execute();

        // Ensure that the user did not receive a warning message.
        $messages = $sink->get_messages();
        $this->assertCount(0, $messages, 'Warning message was sent to user even though it was disabled');
    }

    /**
     * Tests that a user that was deleted receives a deletion message if
     * deletion messages are enabled
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_user_receives_deletion_message(): void {
        $this->resetAfterTest();
        set_config('delete_threshold_days', 10, 'tool_userautodelete');
        set_config('warning_threshold_days', 5, 'tool_userautodelete');
        set_config('delete_email_enable', true, 'tool_userautodelete');

        // Prepare mail sink.
        unset_config('noemailever');
        $sink = $this->redirectEmails();

        // Create a user that is eligible for receiving a deletion message.
        $user = $this->getDataGenerator()->create_user(['lastaccess' => time() - DAYSECS * 11]);
        $manager = new manager();
        $manager->execute();

        // Ensure that the user received a deletion message.
        $messages = $sink->get_messages();
        $this->assertCount(1, $messages, 'Deletion message was not sent to user');
        $this->assertEquals(
            $user->email,
            $messages[0]->to,
            'Deletion message recipient does not match'
        );
    }

    /**
     * Tests that a user that was deleted does not receive a deletion message if
     * deletion messages are disabled
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_user_receives_no_deletion_message(): void {
        $this->resetAfterTest();
        set_config('delete_threshold_days', 10, 'tool_userautodelete');
        set_config('warning_threshold_days', 5, 'tool_userautodelete');
        set_config('delete_email_enable', false, 'tool_userautodelete');

        // Prepare mail sink.
        unset_config('noemailever');
        $sink = $this->redirectEmails();

        // Create a user that is eligible for receiving a deletion message.
        $this->getDataGenerator()->create_user(['lastaccess' => time() - DAYSECS * 11]);
        $manager = new manager();
        $manager->execute();

        // Ensure that the user did not receive a deletion message.
        $messages = $sink->get_messages();
        $this->assertCount(0, $messages, 'Deletion message was sent to user even though it was disabled');
    }

    /**
     * Tests that user data is anonymized if the anonymize_user_data feature is
     * enabled
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_user_anonymization(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('delete_threshold_days', 50, 'tool_userautodelete');
        set_config('anonymize_user_data', true, 'tool_userautodelete');

        // Create test users and perform deletion.
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $users[] = $this->getDataGenerator()->create_user(['lastaccess' => time() - DAYSECS * 100]);
        }
        $manager = new manager();
        $manager->execute();

        // Check that personal information is stripped from user datasets.
        foreach ($users as $user) {
            $userrecord = $DB->get_record('user', ['id' => $user->id]);
            $this->assertSame('1', $userrecord->deleted, 'User was not deleted');
            $this->assertNotEquals($user->username, $userrecord->username, 'Username was not anonymized');
            $this->assertNotEquals($user->firstname, $userrecord->firstname, 'Firstname was not anonymized');
            $this->assertNotEquals($user->lastname, $userrecord->lastname, 'Lastname was not anonymized');
            $this->assertNotEquals($user->email, $userrecord->email, 'Email was not anonymized');
            $this->assertNotEquals($user->lastip, $userrecord->lastip, 'Last IP address was not anonymized');
        }
    }

    /**
     * Tests that user data is not altered by the plugin in addition to the
     * actions performed by Moodle if the anonymize_user_data feature is disabled
     *
     * @covers \tool_userautodelete\manager
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_user_not_anonymized(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('delete_threshold_days', 50, 'tool_userautodelete');
        set_config('anonymize_user_data', false, 'tool_userautodelete');

        // Create test users and perform deletion.
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $users[] = $this->getDataGenerator()->create_user(['lastaccess' => time() - DAYSECS * 100]);
        }
        $manager = new manager();
        $manager->execute();

        // Check that personal information is not stripped from user datasets.
        foreach ($users as $user) {
            $userrecord = $DB->get_record('user', ['id' => $user->id]);
            $this->assertSame('1', $userrecord->deleted, 'User was not deleted');
            $this->assertStringStartsWith($user->username, $userrecord->username, 'Username was anonymized');
            $this->assertEquals($user->firstname, $userrecord->firstname, 'Firstname was anonymized');
            $this->assertEquals($user->lastname, $userrecord->lastname, 'Lastname was anonymized');
            $this->assertEquals($user->lastip, $userrecord->lastip, 'Last IP address was anonymized');
        }
    }

    /**
     * Tests that the action log is created and contains the correct number of
     * warnings, deletions and recoveries.
     *
     * @covers \tool_userautodelete\manager
     * @dataProvider action_log_data_provider
     *
     * @param int $numwarnings
     * @param int $numdeleted
     * @param int $numrecovered
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_action_log(int $numwarnings, int $numdeleted, int $numrecovered): void {
        global $DB;
        $this->resetAfterTest();
        set_config('delete_threshold_days', 10, 'tool_userautodelete');
        set_config('warning_threshold_days', 5, 'tool_userautodelete');

        // Create users to receive warnings.
        for ($i = 0; $i < $numwarnings; $i++) {
            $this->getDataGenerator()->create_user(['lastaccess' => time() - 6 * DAYSECS]);
        }

        // Create users to be deleted.
        for ($i = 0; $i < $numdeleted; $i++) {
            $this->getDataGenerator()->create_user(['lastaccess' => time() - 11 * DAYSECS]);
        }

        // Create users to be recovered.
        for ($i = 0; $i < $numrecovered; $i++) {
            $user = $this->getDataGenerator()->create_user(['lastaccess' => time() - DAYSECS]);
            $DB->insert_record('tool_userautodelete_mail', [
                'userid' => $user->id,
                'timesent' => time() - 2 * DAYSECS,
            ]);
        }

        // Execute deletion routine.
        $manager = new manager();
        $manager->execute();

        // Check action log.
        $logentry = $DB->get_record('tool_userautodelete_log', [], '*', IGNORE_MISSING);
        if ($numwarnings > 0 || $numdeleted > 0 || $numrecovered > 0) {
            $this->assertNotEmpty($logentry, 'Action log entry was not created');
            $this->assertEquals($numwarnings, $logentry->warned, 'Wrong number of warnings logged');
            $this->assertEquals($numdeleted, $logentry->deleted, 'Wrong number of deletions logged');
            $this->assertEquals($numrecovered, $logentry->recovered, 'Wrong number of recoveries logged');
        } else {
            $this->assertFalse($logentry, 'Action log entry was created even though no actions were performed');
        }
    }

    /**
     * Data provider for test_action_log()
     *
     * @return array Test data
     */
    public static function action_log_data_provider(): array {
        return [
            'No warnings, no deletions, no recoveries' => [0, 0, 0],
            'One warning, no deletions, no recoveries' => [1, 0, 0],
            'No warnings, one deletion, no recoveries' => [0, 1, 0],
            'No warnings, no deletions, one recovery' => [0, 0, 1],
            'One warning, one deletion, one recovery' => [1, 1, 1],
            'Multiple warnings, multiple deletions, multiple recoveries' => [3, 3, 3],
            'Multiple warnings, no deletions, some recoveries' => [10, 0, 5],
            'No warnings, multiple deletions, some recoveries' => [0, 10, 5],
        ];
    }

}
