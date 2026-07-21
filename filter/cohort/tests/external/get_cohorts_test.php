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
 * Tests for the get_cohorts external function
 *
 * @package   userdeletefilter_cohort
 * @copyright 2026 Niels Gandraß <niels@gandrass.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_cohort\external;

use context_system;
use core_external\external_api;

/**
 * Tests for the get_cohorts external function
 */
final class get_cohorts_test extends \advanced_testcase {
    /**
     * Creates a cohort
     *
     * @param string $name Cohort name
     * @param string|null $idnumber Optional idnumber
     * @return \stdClass Cohort record
     * @throws \dml_exception
     */
    private function create_cohort(string $name, ?string $idnumber = null): \stdClass {
        return $this->getDataGenerator()->create_cohort([
            'name' => $name,
            'idnumber' => $idnumber ?? '',
            'contextid' => context_system::instance()->id,
        ]);
    }

    /**
     * Tests that execute() throws when called by a non-admin user.
     *
     * @covers \userdeletefilter_cohort\external\get_cohorts
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_requires_admin_capability(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        get_cohorts::execute('test');
    }

    /**
     * Tests that execute() returns cohorts whose name matches the query.
     *
     * @covers \userdeletefilter_cohort\external\get_cohorts
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_returns_cohorts_matching_name_query(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $matching = $this->create_cohort('Group Alpha');
        $this->create_cohort('Group Beta');

        $result = get_cohorts::execute('Alpha');
        $result = external_api::clean_returnvalue(get_cohorts::execute_returns(), $result);

        $ids = array_column($result, 'id');
        $this->assertCount(1, $ids, 'Expected only one cohort result');
        $this->assertContains((int) $matching->id, $ids, 'Name-matching cohort must be in the result');
        foreach ($result as $row) {
            $this->assertStringNotContainsString('Beta', $row['label'], 'Non-matching cohort must not appear');
        }
    }

    /**
     * Tests that execute() returns cohorts whose idnumber matches the query.
     *
     * @covers \userdeletefilter_cohort\external\get_cohorts
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_returns_cohorts_matching_idnumber_query(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $withkey = $this->create_cohort('Group X', 'id-aaa');
        $this->create_cohort('Group Y', 'id-bbb');

        $result = get_cohorts::execute('aaa');
        $result = external_api::clean_returnvalue(get_cohorts::execute_returns(), $result);

        $ids = array_column($result, 'id');
        $this->assertCount(1, $ids, 'Expected only one cohort result');
        $this->assertContains((int) $withkey->id, $ids, 'Cohort matching by idnumber must be in the result');
        foreach ($result as $row) {
            $this->assertStringNotContainsString('Group Y', $row['label'], 'Non-matching cohort must not appear');
        }
    }

    /**
     * Tests that every element in the result has the expected id and label structure.
     *
     * @covers \userdeletefilter_cohort\external\get_cohorts
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_returns_correct_structure(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $cohort = $this->create_cohort('Test Group', 'num-001');

        $result = get_cohorts::execute('Test Group');
        $result = external_api::clean_returnvalue(get_cohorts::execute_returns(), $result);

        $this->assertNotEmpty($result, 'Result must not be empty for an existing cohort');
        $row = reset($result);

        $this->assertArrayHasKey('id', $row, 'Result row must contain an id field');
        $this->assertArrayHasKey('label', $row, 'Result row must contain a label field');
        $this->assertIsInt($row['id'], 'id field must be an integer');
        $this->assertIsString($row['label'], 'label field must be a string');
        $this->assertSame((int) $cohort->id, $row['id'], 'id must match the created cohort');
        $this->assertStringContainsString('Test Group', $row['label'], 'label must contain the cohort name');
        $this->assertStringContainsString('[num-001]', $row['label'], 'label must contain the idnumber');
    }

    /**
     * Tests that execute() honors the limitnum parameter.
     *
     * @covers \userdeletefilter_cohort\external\get_cohorts
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_respects_limitnum(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->create_cohort('Group One');
        $this->create_cohort('Group Two');
        $this->create_cohort('Group Three');

        $result = get_cohorts::execute('', 2);
        $result = external_api::clean_returnvalue(get_cohorts::execute_returns(), $result);

        $this->assertCount(2, $result, 'Result count must not exceed limitnum');
    }

    /**
     * Tests that execute() returns an empty array when no cohort matches the query.
     *
     * @covers \userdeletefilter_cohort\external\get_cohorts
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_returns_empty_for_no_match(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->create_cohort('Test Group');

        $result = get_cohorts::execute('xyznonexistentxyz');
        $result = external_api::clean_returnvalue(get_cohorts::execute_returns(), $result);

        $this->assertSame([], $result, 'No-match query must return an empty array');
    }
}
