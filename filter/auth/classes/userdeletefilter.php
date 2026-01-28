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
 * User delete filter based on authentication plugin / method
 *
 * @package     userdeletefilter_lastaccess
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_auth;

use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\local\type\userfilter_clause;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * User delete filter based on authentication plugin / method
 */
class userdeletefilter extends \tool_userautodelete\userdeletefilter {
    /**
     * Returns the name of this filter sub-plugin, e.g., 'lastaccess' for 'userdeletefilter_lastaccess'
     *
     * @return string The name of this filter sub-plugin
     */
    public function get_plugin_name(): string {
        return 'auth';
    }

    /**
     * Returns a userfilter_clause object defining the SQL where clause and parameters
     * to be used when querying user datasets that match this filter's criteria.
     *
     * Multiple filter clauses will be concatenated using a SQL 'AND' operator.
     *
     * @return userfilter_clause The SQL where clause and parameters for filtering user datasets
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function user_records_filter_clause(): userfilter_clause {
        global $DB;

        // Transform comma separated list of auth methods into SQL clause.
        [$insql, $inparams] = $DB->get_in_or_equal(
            explode(',', $this->get_instance_setting('auths')),
            SQL_PARAMS_NAMED,
            'authparam'
        );

        return new userfilter_clause(
            sql: "auth {$insql}",
            params: $inparams
        );
    }

    /**
     * Returns an array of descriptors for every setting this filter sub-plugin
     * defines and exposes.
     *
     * @return instance_setting_descriptor[] An array of setting descriptors
     */
    #[\Override]
    public function instance_setting_descriptors(): array {
        return [
            new instance_setting_descriptor(
                key: 'auths',
                type: PARAM_TEXT,
                required: true,
                default: 'manual',
                readonly: false
            ),
        ];
    }
}
