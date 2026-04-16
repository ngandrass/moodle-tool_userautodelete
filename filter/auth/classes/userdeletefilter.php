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

use core\lang_string;
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
    public static function get_plugin_name(): string {
        return 'auth';
    }

    /**
     * Returns a font-awesome icon CSS class string that is shown in the UI for
     * this filter sub-plugin type.
     *
     * @return string A font-awesome icon CSS class string combination
     */
    public static function get_icon_class(): string {
        return 'fa-solid fa-key';
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
     * @throws \coding_exception
     */
    public function get_instance_details(): string {
        $auths = $this->get_instance_setting('auths');
        $inverted = $this->get_instance_setting('inverted');

        if (!$auths) {
            return '';
        }

        $authnames = array_map(
            fn ($auth) => get_string('pluginname', 'auth_' . $auth),
            $auths
        );

        return ($inverted ? '! ' : '') . implode(', ', $authnames);
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
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function user_records_filter_clause(): userfilter_clause {
        global $DB;

        // Transform comma separated list of auth methods into SQL clause.
        [$insql, $inparams] = $DB->get_in_or_equal(
            items: $this->get_instance_setting('auths'),
            type: SQL_PARAMS_NAMED,
            prefix: 'authparam',
            equal: !$this->get_instance_setting('inverted')
        );

        return new userfilter_clause(
            sql: "u.auth {$insql}",
            params: $inparams
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
                key: 'auths',
                title: new lang_string('setting_auths', 'userdeletefilter_auth'),
                type: PARAM_TEXT,
                required: true,
                default: [],
                choices: self::get_auth_plugins(),
                serialize: true,
                readonly: false,
                mformtype: 'autocomplete-multi'
            ),
            new instance_setting_descriptor(
                key: 'inverted',
                title: new lang_string('setting_inverted', 'userdeletefilter_auth'),
                type: PARAM_BOOL,
                required: false,
                default: false,
                readonly: false,
                mformtype: 'selectyesno'
            ),
        ];
    }

    /**
     * Generates a list of all installed auth plugins, indexed by their identifier
     *
     * @return array List of all installed auth plugins where the key is the plugin
     * identifier and the value is the localized human-readable name
     * @throws \coding_exception
     */
    public static function get_auth_plugins(): array {
        return array_reduce(
            array_keys(\core_component::get_plugin_list('auth')),
            function ($carry, $item) {
                $carry[$item] = get_string('pluginname', 'auth_' . $item);
                return $carry;
            },
            []
        );
    }
}
