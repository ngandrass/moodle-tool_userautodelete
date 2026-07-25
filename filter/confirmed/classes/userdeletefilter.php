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
 * Filter sub-plugin class for userdeletefilter_confirmed.
 *
 * @package     userdeletefilter_confirmed
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_confirmed;

use core\lang_string;
use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\local\type\userfilter_clause;
use userdeletefilter_confirmed\local\type\mode;

/**
 * Filters users based on their account confirmation state (mdl_user.confirmed).
 */
class userdeletefilter extends \tool_userautodelete\userdeletefilter {
    /**
     * Returns the short name of this sub-plugin.
     *
     * @return string Plugin name
     */
    #[\Override]
    public static function get_plugin_name(): string {
        return 'confirmed';
    }

    /**
     * Returns the font-awesome icon CSS class string for this filter.
     *
     * @return string Icon CSS class
     */
    #[\Override]
    public static function get_icon_class(): string {
        return 'fa-solid fa-circle-check';
    }

    /**
     * Returns the URL of the documentation page for this filter.
     *
     * @return \moodle_url|null Documentation URL
     */
    #[\Override]
    public static function get_help_url(): ?\moodle_url {
        return new \moodle_url('https://moodleuserlifecycle.gandrass.de/filters/confirmed/');
    }

    /**
     * Returns a human-readable summary of the active instance settings.
     *
     * @return string Human-readable mode label, or empty string if not yet configured
     * @throws \coding_exception
     */
    #[\Override]
    public function get_instance_details(): string {
        $mode = mode::tryFrom($this->get_instance_setting('mode') ?? '');
        if ($mode === null) {
            return '';
        }

        return get_string("mode_{$mode->value}", 'userdeletefilter_confirmed');
    }

    /**
     * Returns a SQL WHERE clause that matches users based on their confirmation state.
     *
     * @return userfilter_clause SQL clause referencing the 'u' user table alias
     * @throws \moodle_exception If the required mode setting has not been configured
     */
    #[\Override]
    public function user_records_filter_clause(): userfilter_clause {
        $mode = mode::tryFrom($this->get_instance_setting('mode') ?? '');
        if ($mode === null) {
            throw new \moodle_exception('missing_mode_setting', 'userdeletefilter_confirmed');
        }

        return new userfilter_clause(
            sql: 'u.confirmed = :confirmedval',
            params: ['confirmedval' => $mode === mode::CONFIRMED ? 1 : 0]
        );
    }

    /**
     * Returns the instance setting descriptors for this filter.
     *
     * @return instance_setting_descriptor[] Array of setting descriptors
     * @throws \coding_exception
     */
    #[\Override]
    public static function instance_setting_descriptors(): array {
        return [
            new instance_setting_descriptor(
                key: 'mode',
                title: new lang_string('setting_mode', 'userdeletefilter_confirmed'),
                type: PARAM_TEXT,
                required: true,
                default: mode::UNCONFIRMED->value,
                choices: [
                    mode::UNCONFIRMED->value => get_string('mode_unconfirmed', 'userdeletefilter_confirmed'),
                    mode::CONFIRMED->value => get_string('mode_confirmed', 'userdeletefilter_confirmed'),
                ],
                mformtype: 'select'
            ),
        ];
    }
}
