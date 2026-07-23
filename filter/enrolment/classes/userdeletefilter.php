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
 * User delete filter based on course enrolments
 *
 * @package     userdeletefilter_enrolment
 * @author      Richard Aljaste <richard.aljaste@ut.ee>
 * @copyright   2026 University of Tartu <veebitiim@ut.ee>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_enrolment;

use core\lang_string;
use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\local\type\userfilter_clause;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

/**
 * User delete filter based on course enrolments
 */
class userdeletefilter extends \tool_userautodelete\userdeletefilter {
    /** @var int Internal data value to store for "User must not be enroled" condition */
    public const MUST_NOT_BE_ENROLLED = 0;

    /** @var int Internal data value to store for "User must be enroled" condition */
    public const MUST_BE_ENROLLED = 1;

    /**
     * Returns the name of this filter sub-plugin, e.g., 'lastaccess' for 'userdeletefilter_lastaccess'
     *
     * @return string The name of this filter sub-plugin
     */
    public static function get_plugin_name(): string {
        return 'enrolment';
    }

    /**
     * Returns a font-awesome icon CSS class string that is shown in the UI for
     * this filter sub-plugin type.
     *
     * @return string A font-awesome icon CSS class string combination
     */
    public static function get_icon_class(): string {
        return 'fa-solid fa-graduation-cap';
    }

    /**
     * Returns an URL to additional documentation for this sub-plugin, if
     * available. When this URL is set, an additional button to open the linked
     * documentation will be shown in the sub-plugin instance settings form.
     *
     * @return \moodle_url|null URL to the sub-plugin specific documentation, or
     * null if no additional documentation is available
     */
    public static function get_help_url(): ?\moodle_url {
        return null;
    }

    /**
     * Returns a descriptive string of this filter instance's settings to be shown in the UI
     *
     * This should be a human-readable string that describes the actual settings
     * of this filter instance, e.g., '<= 3 months' for a filter instance that
     * filters users based on their last access time with a threshold of 3 months.
     *
     * If no settings are defined, this function can simply return an empty string.
     *
     * @return string A descriptive string of this filter instance's settings to be shown in the UI
     */
    public function get_instance_details(): string {
        return (int)$this->get_instance_setting('enrolled') === self::MUST_BE_ENROLLED
            ? new lang_string('enrolled', 'userdeletefilter_enrolment')
            : new lang_string('not_enrolled', 'userdeletefilter_enrolment');
    }

    /**
     * Returns a userfilter_clause object defining the SQL where clause and parameters
     * to be used when querying user datasets that match this filter's criteria.
     *
     * User table fields must be accessed using the 'u' table alias, e.g., 'u.lastaccess'
     * for the 'lastaccess' field inside the Moodle 'user' table.
     *
     * Multiple filter clauses will be concatenated using a SQL 'AND' operator.
     *
     * @return userfilter_clause The SQL where clause and parameters for filtering user datasets
     */
    public function user_records_filter_clause(): userfilter_clause {
        $sqlverb = (int) $this->get_instance_setting('enrolled') === self::MUST_BE_ENROLLED
            ? 'EXISTS'
            : 'NOT EXISTS';

        return new userfilter_clause(
            sql: "{$sqlverb} (
                    SELECT 1 FROM {user_enrolments} ue
                    WHERE ue.userid = u.id
                )",
            params: []
        );
    }

    /**
     * Returns an array of descriptors for every setting this filter sub-plugin
     * defines and exposes.
     *
     * @return instance_setting_descriptor[] An array of setting descriptors
     * @throws \coding_exception
     */
    #[\Override]
    public static function instance_setting_descriptors(): array {
        return [
            new instance_setting_descriptor(
                key: 'enrolled',
                title: new lang_string('setting_enrolled', 'userdeletefilter_enrolment'),
                type: PARAM_INT,
                required: false,
                default: self::MUST_NOT_BE_ENROLLED,
                choices: [
                    self::MUST_NOT_BE_ENROLLED => new lang_string('not_enrolled', 'userdeletefilter_enrolment'),
                    self::MUST_BE_ENROLLED => new lang_string('enrolled', 'userdeletefilter_enrolment'),
                ],
                readonly: false,
                mformtype: 'select'
            ),
        ];
    }
}
