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
 * @copyright 2025 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_userautodelete;


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

}
