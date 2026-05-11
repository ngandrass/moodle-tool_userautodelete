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
 * Tests for the logger class
 *
 * @package   tool_userautodelete
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;

use coding_exception;
use tool_userautodelete\local\type\db_table;

/**
 * Tests for the logger class
 */
final class logger_test extends \advanced_testcase {
    /**
     * This method is called before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        logger::enable();
    }

    /**
     * Tests that the logger can be globally enabled
     *
     * @covers \tool_userautodelete\logger::enable
     *
     * @return void
     */
    public function test_enable_logging(): void {
        logger::debug("test foo bar baz 42");
        $this->expectOutputRegex("/.*test foo bar baz 42.*/");
    }

    /**
     * Tests that the logger can be globally disabled
     *
     * @covers \tool_userautodelete\logger::disable
     *
     * @return void
     */
    public function test_disable_logging(): void {
        logger::disable();
        logger::debug("test foo bar baz 42");
        $this->expectOutputString('');
    }

    /**
     * Tests logging different messages using different log levels
     *
     * @covers       \tool_userautodelete\logger
     * @dataProvider mtrace_logging_dataprovider
     *
     * @param string $logfn Name of the logging function to call
     * @param string $loglevelname Name of the log level to expect in the output
     * @return void
     */
    public function test_mtrace_logging(string $logfn, string $loglevelname): void {
        foreach (["test", "", "...", "foo\r\n"] as $message) {
            $this->expectOutputRegex("/.*\[{$loglevelname}\] {$message}.*/");
            logger::{$logfn}($message);
        }
    }

    /**
     * Data provider for test_mtrace_logging()
     *
     * @return array[] Test data
     */
    public static function mtrace_logging_dataprovider(): array {
        return [
            "DEBUG" => ['logfn' => 'debug', 'loglevelname' => 'DEBUG'],
            "INFO" => ['logfn' => 'info', 'loglevelname' => 'INFO'],
            "WARNING" => ['logfn' => 'warning', 'loglevelname' => 'WARN'],
            "ERROR" => ['logfn' => 'error', 'loglevelname' => 'ERROR'],
        ];
    }

    /**
     * Tests that action logs are persisted in the action log table.
     *
     * @covers \tool_userautodelete\logger
     *
     * @return void
     * @throws \core\exception\coding_exception
     * @throws \dml_exception
     */
    public function test_action_logging(): void {
        global $DB;

        $this->resetAfterTest();

        $details = [
            'foo' => 'bar',
            'count' => 42,
        ];

        logger::action(
            name: 'testaction',
            affectedusers: 7,
            workflowid: null,
            stepid: null,
            timestamp: 1234567890,
            details: $details,
        );

        $this->assertEquals(1, $DB->count_records(db_table::ACTIONLOG->value));

        $record = $DB->get_record(db_table::ACTIONLOG->value, ['action' => 'testaction'], '*', MUST_EXIST);
        $this->assertEquals(7, $record->affectedusers);
        $this->assertNull($record->workflowid);
        $this->assertNull($record->stepid);
        $this->assertEquals(1234567890, $record->timestamp);
        $this->assertEquals(json_encode($details), $record->details);
    }

    /**
     * Tests that invalid action() parameters throw coding exceptions.
     *
     * @covers       \tool_userautodelete\logger
     * @dataProvider action_validation_dataprovider
     *
     * @param array $args Action logger arguments
     * @param string $expectedmessage Expected exception message
     * @return void
     * @throws \core\exception\coding_exception
     * @throws \dml_exception
     */
    public function test_action_validation(array $args, string $expectedmessage): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage($expectedmessage);

        logger::action(...$args);
    }

    /**
     * Data provider for test_action_validation().
     *
     * @return array[]
     */
    public static function action_validation_dataprovider(): array {
        return [
            'empty action name' => [
                'args' => [
                    'name' => '',
                    'affectedusers' => 1,
                ],
                'expectedmessage' => 'Action name cannot be empty',
            ],
            'negative affected users' => [
                'args' => [
                    'name' => 'testaction',
                    'affectedusers' => -1,
                ],
                'expectedmessage' => 'Affected users count cannot be negative',
            ],
            'invalid workflow id' => [
                'args' => [
                    'name' => 'testaction',
                    'affectedusers' => 1,
                    'workflowid' => 0,
                ],
                'expectedmessage' => 'Workflow ID must be a positive integer',
            ],
            'invalid step id' => [
                'args' => [
                    'name' => 'testaction',
                    'affectedusers' => 1,
                    'workflowid' => 1,
                    'stepid' => 0,
                ],
                'expectedmessage' => 'Step ID must be a positive integer',
            ],
            'invalid timestamp' => [
                'args' => [
                    'name' => 'testaction',
                    'affectedusers' => 1,
                    'workflowid' => 1,
                    'stepid' => 1,
                    'timestamp' => 0,
                ],
                'expectedmessage' => 'Timestamp must be a positive integer',
            ],
            'json encoding failure' => [
                'args' => [
                    'name' => 'testaction',
                    'affectedusers' => 1,
                    'details' => [
                        'badutf8' => "\xB1\x31",
                    ],
                ],
                'expectedmessage' => 'Failed to encode action details as JSON',
            ],
        ];
    }
}
