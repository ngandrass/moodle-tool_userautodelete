<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Tests for the userdeleteaction_badges sub-plugin
 *
 * @package     userdeleteaction_badges
 * @category    test
 * @author      Marcus Green
 * @copyright   2026 Catalyst-EU
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_badges;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../tests/userdeleteaction_testcase.php');


/**
 * Unit tests for the userdeleteaction_badges sub-plugin
 */
final class userdeleteaction_test extends \tool_userautodelete\userdeleteaction_testcase {
    /**
     * Returns the short plugin name of the action sub-plugin under test.
     */
    protected function get_plugin_name(): string {
        return 'badges';
    }

    /**
     * Returns the expected font-awesome icon CSS class string for the action
     * sub-plugin under test, e.g. 'fa-solid fa-gear'.
     */
    protected function get_expected_icon_class(): string {
        return 'fa-solid fa-award';
    }

    /**
     * Awards a new site badge to the given user and bakes the badge image into
     * the user context.
     *
     * @param \stdClass $user The user to award the badge to
     * @return \core_badges\badge The awarded badge
     */
    private function award_badge(\stdClass $user): \core_badges\badge {
        global $CFG;
        require_once($CFG->libdir . '/badgeslib.php');

        $badge = $this->getDataGenerator()->get_plugin_generator('core_badges')->create_badge([
            'status' => BADGE_STATUS_ACTIVE,
        ]);
        $badge->issue($user->id, true);

        // Store a baked badge image, as badges_bake() would, without relying on the badge image file.
        get_file_storage()->create_file_from_string([
            'contextid' => \context_user::instance($user->id)->id,
            'component' => 'badges',
            'filearea' => 'userbadge',
            'itemid' => $badge->id,
            'filepath' => '/',
            'filename' => 'baked.png',
        ], 'png');

        return $badge;
    }

    /**
     * Tests that execute() removes all badge data of the process user, keeps
     * badge data of other users, and returns true.
     *
     * @covers \userdeleteaction_badges\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute(): void {
        global $DB;

        // Prepare users with badges, step, and process.
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $badge = $this->award_badge($user);
        $this->award_badge($otheruser);
        $DB->insert_record('badge_manual_award', (object) [
            'badgeid' => $badge->id,
            'recipientid' => $user->id,
            'issuerid' => get_admin()->id,
            'issuerrole' => 1,
            'datemet' => time(),
        ]);

        $this->assertTrue($DB->record_exists('badge_issued', ['userid' => $user->id]));
        $this->assertTrue($DB->record_exists('badge_manual_award', ['recipientid' => $user->id]));

        $step = $this->create_step();
        $action = $this->create_action($step);
        $process = $this->create_process((int) $user->id, $step);

        // Execute and assert success.
        $this->assertTrue($action->execute($process), 'badges action execute() must return true on success');

        // Badge data of the process user must be gone.
        $this->assertFalse($DB->record_exists('badge_issued', ['userid' => $user->id]));
        $this->assertFalse($DB->record_exists('badge_criteria_met', ['userid' => $user->id]));
        $this->assertFalse($DB->record_exists('badge_manual_award', ['recipientid' => $user->id]));
        $this->assertTrue(get_file_storage()->is_area_empty(
            \context_user::instance($user->id)->id,
            'badges',
            'userbadge'
        ));

        // Badge data of other users must be untouched.
        $this->assertTrue($DB->record_exists('badge_issued', ['userid' => $otheruser->id]));
        $this->assertFalse(get_file_storage()->is_area_empty(
            \context_user::instance($otheruser->id)->id,
            'badges',
            'userbadge'
        ));
    }

    /**
     * Tests that execute() also removes badge data of users that were already
     * deleted by Moodle's delete_user(), e.g., by a preceding delete action.
     *
     * @covers \userdeleteaction_badges\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_for_deleted_user(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->award_badge($user);

        $step = $this->create_step();
        $action = $this->create_action($step);
        $process = $this->create_process((int) $user->id, $step);

        // Core delete_user() leaves issued badges behind.
        delete_user($user);
        $this->assertTrue($DB->record_exists('badge_issued', ['userid' => $user->id]));

        $this->assertTrue($action->execute($process));
        $this->assertFalse($DB->record_exists('badge_issued', ['userid' => $user->id]));
    }

    /**
     * Tests that execute() succeeds for users without any badges.
     *
     * @covers \userdeleteaction_badges\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_execute_without_badges(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();

        $step = $this->create_step();
        $action = $this->create_action($step);
        $process = $this->create_process((int) $user->id, $step);

        $this->assertTrue($action->execute($process));
    }

    /**
     * Tests that a default instance (no required settings) is considered valid.
     *
     * @covers \userdeleteaction_badges\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_default_instance_is_valid(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertTrue($action->is_valid(), 'badges action without settings must be valid by default');
    }

    /**
     * Tests that get_instance_details() returns an empty string (no settings).
     *
     * @covers \userdeleteaction_badges\userdeleteaction
     *
     * @return void
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function test_get_instance_details_returns_empty_string(): void {
        $this->resetAfterTest();

        $step = $this->create_step();
        $action = $this->create_action($step);

        $this->assertSame('', $action->get_instance_details(), 'badges action get_instance_details() must return empty string');
    }
}
