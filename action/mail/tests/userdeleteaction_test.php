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
 * Unit tests for the userdeleteaction_mail sub-plugin
 *
 * @package   userdeleteaction_mail
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_mail;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeleteaction_testcase.php');


/**
 * Unit tests for the userdeleteaction_mail sub-plugin
 */
final class userdeleteaction_test extends \tool_userautodelete\userdeleteaction_testcase {
    /**
     * Returns the short plugin name of the action sub-plugin under test.
     */
    protected function get_plugin_name(): string {
        return 'mail';
    }

    /**
     * Returns the expected font-awesome icon CSS class string for the action
     * sub-plugin under test, e.g. 'fa-solid fa-gear'.
     */
    protected function get_expected_icon_class(): string {
        return 'fa-solid fa-envelope';
    }

    /**
     * Tests that execute() sends exactly one email to the target user with the
     * configured subject and returns true.
     *
     * @covers \userdeleteaction_mail\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute(): void {
        $this->resetAfterTest();

        // Prepare user, step, and proess.
        $user = $this->getDataGenerator()->create_user(['email' => 'target@example.com']);
        $step = $this->create_step();
        $action = $this->create_action($step, [
            'subject' => 'Test Subject',
            'message' => '<p>Test message body</p>',
        ]);
        $process = $this->create_process((int) $user->id, $step);

        // Execute action and catch mails.
        $mailsink = $this->redirectEmails();
        $result = $action->execute($process);
        $mailsink->close();

        // Assert successful execution.
        $this->assertTrue($result, 'mail action execute() must return true on success');
        $messages = $mailsink->get_messages();
        $this->assertCount(1, $messages, 'Exactly one email must be sent');
        $this->assertSame(
            'Test Subject',
            $messages[0]->subject,
            'Email subject must match the configured subject setting'
        );
        $this->assertSame(
            $user->email,
            $messages[0]->to,
            'Email must be addressed to the target user'
        );
    }

    /**
     * Tests that execute() resolves user and site variable references in both
     * subject and message body before sending.
     *
     * @covers \userdeleteaction_mail\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_resolves_variables_in_subject_and_message(): void {
        global $SITE, $CFG;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'jd@example.com',
            'city' => 'Hamburg',
        ]);
        $step = $this->create_step();
        $action = $this->create_action($step, [
            'subject' => 'Hi {{user.firstname}}, account on {{site.name}}',
            'message' => '<p>Dear {{user.firstname}} {{user.lastname}}, visit {{urls.home}}.</p>',
        ]);
        $process = $this->create_process((int) $user->id, $step);

        $mailsink = $this->redirectEmails();
        $action->execute($process);
        $mailsink->close();

        $messages = $mailsink->get_messages();
        $this->assertCount(1, $messages, 'Exactly one email must be sent.');
        $this->assertSame(
            "Hi John, account on {$SITE->fullname}",
            $messages[0]->subject,
            'Variables in subject must be resolved before sending.'
        );
        $this->assertStringContainsString(
            'Dear John Doe',
            $messages[0]->body,
            'User name variables in message body must be resolved.'
        );
        $this->assertStringContainsString(
            $CFG->wwwroot,
            $messages[0]->body,
            'Site URL variable in message body must be resolved.'
        );
    }

    /**
     * Tests that execute() leaves unknown variable references unreplaced in the
     * sent email rather than stripping or failing on them.
     *
     * @covers \userdeleteaction_mail\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_leaves_unknown_variables_unreplaced(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user(['email' => 'user@example.com']);
        $step = $this->create_step();
        $action = $this->create_action($step, [
            'subject' => 'Hello {{user.firstname}}',
            'message' => 'Token: {{user.invalidvariable}} End.',
        ]);
        $process = $this->create_process((int) $user->id, $step);

        $mailsink = $this->redirectEmails();
        $result = $action->execute($process);
        $mailsink->close();

        $this->assertTrue($result, 'execute() must return true even when unknown variables are present.');
        $this->assertStringContainsString(
            '{{user.invalidvariable}}',
            $mailsink->get_messages()[0]->body,
            'Unknown variable references must remain literally in the sent message.'
        );
    }

    /**
     * Tests that execute() returns false when the target user has no email address.
     *
     * @covers \userdeleteaction_mail\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_returns_false_for_user_without_email(): void {
        global $DB;

        $this->resetAfterTest();

        // Create user and remove the email address afterwards.
        $user = $this->getDataGenerator()->create_user();
        $DB->update_record('user', ['id' => $user->id, 'email' => '']);

        $step = $this->create_step();
        $action = $this->create_action($step, [
            'subject' => 'Subject',
            'message' => 'Body',
        ]);
        $process = $this->create_process((int) $user->id, $step);

        $result = $action->execute($process);
        $this->assertFalse($result, 'mail action execute() must return false when user has no email address');
    }

    /**
     * Tests that execute() throws a moodle_exception when the required subject
     * or message settings are missing.
     *
     * @covers \userdeleteaction_mail\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_throws_for_missing_required_settings(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user(['email' => 'target@example.com']);

        $step = $this->create_step();
        // Intentionally create the action without subject/message settings.
        $action  = $this->create_action($step);
        $process = $this->create_process((int) $user->id, $step);

        $this->expectException(\moodle_exception::class);
        $action->execute($process);
    }

    /**
     * Tests that the instance is invalid when required settings are missing and
     * becomes valid once both subject and message have been set.
     *
     * @covers \userdeleteaction_mail\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_instance_validity_requires_subject_and_message(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertFalse($action->is_valid(), 'mail action without settings must be invalid');

        $action->set_instance_setting('subject', 'Some Subject');
        $this->assertFalse($action->is_valid(), 'mail action with only subject must still be invalid');

        $action->set_instance_setting('message', 'Some message body');
        $this->assertTrue($action->is_valid(), 'mail action with both subject and message must be valid');
    }

    /**
     * Tests that get_instance_details() returns the configured subject string.
     *
     * @covers \userdeleteaction_mail\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details_returns_subject(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step, ['subject' => 'My Email Subject']);

        $this->assertSame(
            'My Email Subject',
            $action->get_instance_details(),
            'get_instance_details() must return the configured subject setting'
        );
    }

    /**
     * Tests that get_instance_details() returns an empty string when no subject
     * has been configured yet.
     *
     * @covers \userdeleteaction_mail\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details_returns_empty_string_without_subject(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertSame(
            '',
            $action->get_instance_details(),
            'get_instance_details() must return an empty string when no subject is configured'
        );
    }

    /**
     * Tests that validate_instance_settings() returns an empty array when both
     * subject and message contain only valid variable references.
     *
     * @covers \userdeleteaction_mail\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_validate_instance_settings_returns_no_errors_for_valid_variables(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $errors = $action->validate_instance_settings([
            'subject' => 'Hello {{user.firstname}} on {{site.name}}',
            'message' => '<p>Dear {{user.firstname}} {{user.lastname}}, visit {{urls.home}}.</p>',
        ]);

        $this->assertSame([], $errors, 'Settings with only valid variable references must produce no validation errors.');
    }

    /**
     * Tests that validate_instance_settings() returns an empty array when
     * settings contain plain text with no variable references at all.
     *
     * @covers \userdeleteaction_mail\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_validate_instance_settings_returns_no_errors_for_plain_text(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $errors = $action->validate_instance_settings([
            'subject' => 'Your account will be deleted',
            'message' => '<p>Please log in to keep your account.</p>',
        ]);

        $this->assertSame([], $errors, 'Settings without variable references must produce no validation errors.');
    }

    /**
     * Tests that validate_instance_settings() returns an error for the subject
     * when it contains an unknown variable reference.
     *
     * @covers \userdeleteaction_mail\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_validate_instance_settings_returns_error_for_unknown_variable_in_subject(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $errors = $action->validate_instance_settings([
            'subject' => 'Hello {{user.nonexistentfield}}',
            'message' => '<p>Valid message.</p>',
        ]);

        $this->assertArrayHasKey('subject', $errors, 'An error must be reported for the subject key.');
        $this->assertArrayNotHasKey('message', $errors, 'No error must be reported for a valid message.');
        $this->assertStringContainsString(
            '{{user.nonexistentfield}}',
            $errors['subject'],
            'The error message must name the unknown variable reference.'
        );
    }

    /**
     * Tests that validate_instance_settings() returns an error for the message
     * when it contains an unknown variable reference.
     *
     * @covers \userdeleteaction_mail\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_validate_instance_settings_returns_error_for_unknown_variable_in_message(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $errors = $action->validate_instance_settings([
            'subject' => 'Valid subject',
            'message' => '<p>Token: {{workflow.secret}}</p>',
        ]);

        $this->assertArrayNotHasKey('subject', $errors, 'No error must be reported for a valid subject.');
        $this->assertArrayHasKey('message', $errors, 'An error must be reported for the message key.');
        $this->assertStringContainsString(
            '{{workflow.secret}}',
            $errors['message'],
            'The error message must name the unknown variable reference.'
        );
    }

    /**
     * Tests that validate_instance_settings() returns errors for both subject
     * and message when both contain unknown variable references.
     *
     * @covers \userdeleteaction_mail\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_validate_instance_settings_returns_errors_for_both_fields(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $errors = $action->validate_instance_settings([
            'subject' => '{{bad.subject}}',
            'message' => '{{bad.message}}',
        ]);

        $this->assertArrayHasKey('subject', $errors, 'An error for subject must be present.');
        $this->assertArrayHasKey('message', $errors, 'An error for message must be present.');
    }
}
