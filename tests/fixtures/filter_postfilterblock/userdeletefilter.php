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
 * Test-only filter fixture that blocks specific user IDs via post-filtering.
 *
 * This class is intentionally NOT placed inside filter/ so it is never
 * discovered by Moodle's plugin manager and therefore never installed in
 * production. It is loaded in unit tests via require_once.
 *
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_postfilterblock;

use core\lang_string;
use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\local\type\userfilter_clause;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * Test fixture: passes all users through SQL but blocks specified IDs in post-filter.
 */
class userdeletefilter extends \tool_userautodelete\userdeletefilter {
    /**
     * Returns the short name of this sub-plugin.
     *
     * @return string Plugin name.
     */
    #[\Override]
    public static function get_plugin_name(): string {
        return 'postfilterblock';
    }

    /**
     * Returns a pass-through SQL clause that matches all users.
     *
     * @return userfilter_clause SQL clause referencing the 'u' user table alias.
     */
    #[\Override]
    public function user_records_filter_clause(): userfilter_clause {
        return new userfilter_clause(sql: '1=1', params: []);
    }

    /**
     * Removes any user IDs listed in the 'blockeduserids' instance setting.
     *
     * @param int[] $userids Candidate user IDs that passed the SQL filter stage.
     * @return int[] Subset of $userids with blocked IDs removed.
     */
    #[\Override]
    public function user_records_postfilter(array $userids): array {
        $raw = $this->get_instance_setting('blockeduserids') ?? '';
        if ($raw === '') {
            return $userids;
        }

        $blocked = array_flip(array_map('intval', explode(',', $raw)));
        return array_values(array_filter($userids, fn(int $id): bool => !isset($blocked[$id])));
    }

    /**
     * Returns the instance setting descriptors for this filter.
     *
     * @return instance_setting_descriptor[] Array of setting descriptors.
     * @throws \coding_exception
     */
    #[\Override]
    public static function instance_setting_descriptors(): array {
        return [
            new instance_setting_descriptor(
                key: 'blockeduserids',
                title: new lang_string('filter', 'tool_userautodelete'),
                type: PARAM_TEXT,
                required: false,
                default: '',
            ),
        ];
    }
}
