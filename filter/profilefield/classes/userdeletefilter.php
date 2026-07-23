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
 * User delete filter based on user profile field values
 *
 * @package     userdeletefilter_profilefield
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeletefilter_profilefield;

use core\lang_string;
use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\local\type\userfilter_clause;
use userdeletefilter_profilefield\local\type\matchmode;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * User delete filter based on user profile field values
 */
class userdeletefilter extends \tool_userautodelete\userdeletefilter {
    /** @var string Prefix for standard user table fields in the field setting value */
    const PREFIX_STD = 'std:';

    /** @var string Prefix for custom profile fields in the field setting value */
    const PREFIX_CUSTOM = 'custom:';

    /**
     * Returns the name of this filter sub-plugin, e.g., 'lastaccess' for 'userdeletefilter_lastaccess'
     *
     * @return string The name of this filter sub-plugin
     */
    public static function get_plugin_name(): string {
        return 'profilefield';
    }

    /**
     * Returns a font-awesome icon CSS class string that is shown in the UI for
     * this filter sub-plugin type.
     *
     * @return string A font-awesome icon CSS class string combination
     */
    public static function get_icon_class(): string {
        return 'fa-regular fa-id-card';
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
        return new \moodle_url('https://moodleuserlifecycle.gandrass.de/filters/profilefield/');
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
     * @throws \dml_exception
     */
    public function get_instance_details(): string {
        $field = $this->get_instance_setting('field');
        $matchmode = $this->get_instance_setting('matchmode');
        $value = $this->get_instance_setting('value');

        if (!$field || !$matchmode) {
            return '';
        }

        $fieldlabel = self::get_available_fields()[$field] ?? $field;
        $modelabel = self::get_match_modes()[$matchmode] ?? $matchmode;

        if (matchmode::tryFrom($matchmode)?->requires_value() && !empty($value)) {
            return "{$fieldlabel} {$modelabel} \"{$value}\"";
        }

        return "{$fieldlabel} {$modelabel}";
    }

    /**
     * Validates this filter instance and returns an error string if invalid.
     *
     * Extends the base validation to additionally check that the configured
     * profile field still exists on the system.
     *
     * @return string|null Error message if invalid, or null if valid
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function validate(): ?string {
        if ($error = parent::validate()) {
            return $error;
        }

        $field = $this->get_instance_setting('field');
        if (!array_key_exists($field, self::get_available_fields())) {
            return get_string('error_field_not_found', 'userdeletefilter_profilefield');
        }

        return null;
    }

    /**
     * Validates the given settings data before saving and returns per-key error messages.
     *
     * Checks that a comparison value is provided for all match modes that require one.
     *
     * @param array $settings Associative array of setting key-value pairs to validate
     * @return string[] Associative array of setting key => localized error message for each invalid setting
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function validate_instance_settings_data(array $settings): array {
        $errors = parent::validate_instance_settings_data($settings);

        $field = $settings['field'] ?? null;
        $matchmode = $settings['matchmode'] ?? null;
        $value = $settings['value'] ?? null;

        // Check that passed field actually exists.
        if (!array_key_exists($field, self::get_available_fields())) {
            $errors['field'] = get_string('error_field_not_found', 'userdeletefilter_profilefield');
        }

        // Check that a comparison value is set if required.
        if (matchmode::tryFrom($matchmode ?? '')?->requires_value() && empty($value)) {
            $errors['value'] = get_string('error_value_required', 'userdeletefilter_profilefield');
        }

        return $errors;
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
     * @throws \moodle_exception
     */
    public function user_records_filter_clause(): userfilter_clause {
        $field = $this->get_instance_setting('field');
        $matchmode = matchmode::from($this->get_instance_setting('matchmode'));
        $value = $this->get_instance_setting('value') ?? '';

        if (str_starts_with($field, self::PREFIX_STD)) {
            return $this->build_standard_field_clause(substr($field, strlen(self::PREFIX_STD)), $matchmode, $value);
        } else if (str_starts_with($field, self::PREFIX_CUSTOM)) {
            return $this->build_custom_field_clause(substr($field, strlen(self::PREFIX_CUSTOM)), $matchmode, $value);
        } else {
            throw new \coding_exception('Encountered invalid field name prefix. Please report this bug!');
        }
    }

    /**
     * Builds a SQL filter clause for standard user table fields.
     *
     * @param string $fieldname The user table column name (without table alias)
     * @param matchmode $matchmode The match mode to apply
     * @param string $value The comparison value
     * @return userfilter_clause The SQL clause and named parameters
     */
    private function build_standard_field_clause(string $fieldname, matchmode $matchmode, string $value): userfilter_clause {
        global $DB;

        // Use database-agnostic fullname concatenation for the fullname pseudo-field.
        $expr = $fieldname === 'fullname'
            ? $DB->sql_fullname('u.firstname', 'u.lastname')
            : "u.{$fieldname}";

        return match ($matchmode) {
            matchmode::CONTAINS => new userfilter_clause(
                sql: $DB->sql_like($expr, ':pfvval', false, false),
                params: ['pfvval' => '%' . $DB->sql_like_escape($value) . '%'],
            ),
            matchmode::NOT_CONTAINS => new userfilter_clause(
                sql: $DB->sql_like($expr, ':pfvval', false, false, true),
                params: ['pfvval' => '%' . $DB->sql_like_escape($value) . '%'],
            ),
            matchmode::EQUALS => new userfilter_clause(
                sql: $DB->sql_equal($expr, ':pfvval', false),
                params: ['pfvval' => $value],
            ),
            matchmode::NOT_EQUALS => new userfilter_clause(
                sql: $DB->sql_equal($expr, ':pfvval', false, true, true),
                params: ['pfvval' => $value],
            ),
            matchmode::STARTS_WITH => new userfilter_clause(
                sql: $DB->sql_like($expr, ':pfvval', false, false),
                params: ['pfvval' => $DB->sql_like_escape($value) . '%'],
            ),
            matchmode::ENDS_WITH => new userfilter_clause(
                sql: $DB->sql_like($expr, ':pfvval', false, false),
                params: ['pfvval' => '%' . $DB->sql_like_escape($value)],
            ),
            matchmode::EMPTY => new userfilter_clause(
                sql: "({$expr} = '' OR {$expr} IS NULL)",
                params: [],
            ),
            matchmode::NOT_EMPTY => new userfilter_clause(
                sql: "({$expr} != '' AND {$expr} IS NOT NULL)",
                params: [],
            ),
        };
    }

    /**
     * Builds a SQL filter clause for custom user profile fields stored in user_info_data.
     *
     * Uses EXISTS / NOT EXISTS subqueries so that users with no row in user_info_data
     * are correctly treated as having an empty value for negation and empty-check modes.
     *
     * @param string $shortname The shortname of the custom profile field
     * @param matchmode $matchmode The match mode to apply
     * @param string $value The comparison value
     * @return userfilter_clause The SQL clause and named parameters
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private function build_custom_field_clause(string $shortname, matchmode $matchmode, string $value): userfilter_clause {
        global $DB;

        // Prepare a base query that identifies the respective custom profile field.
        $fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => $shortname], MUST_EXIST);
        $base = 'SELECT 1 FROM {user_info_data} uid WHERE uid.userid = u.id AND uid.fieldid = :pfvfid';

        // Build final query for the desired matchmode.
        return match ($matchmode) {
            matchmode::CONTAINS => new userfilter_clause(
                sql: "EXISTS ({$base} AND {$DB->sql_like('uid.data', ':pfvval', false, false)})",
                params: ['pfvfid' => $fieldid, 'pfvval' => '%' . $DB->sql_like_escape($value) . '%'],
            ),
            matchmode::NOT_CONTAINS => new userfilter_clause(
                sql: "NOT EXISTS ({$base} AND {$DB->sql_like('uid.data', ':pfvval', false, false)})",
                params: ['pfvfid' => $fieldid, 'pfvval' => '%' . $DB->sql_like_escape($value) . '%'],
            ),
            matchmode::EQUALS => new userfilter_clause(
                sql: "EXISTS ({$base} AND {$DB->sql_equal('uid.data', ':pfvval', false)})",
                params: ['pfvfid' => $fieldid, 'pfvval' => $value],
            ),
            matchmode::NOT_EQUALS => new userfilter_clause(
                sql: "NOT EXISTS ({$base} AND {$DB->sql_equal('uid.data', ':pfvval', false)})",
                params: ['pfvfid' => $fieldid, 'pfvval' => $value],
            ),
            matchmode::STARTS_WITH => new userfilter_clause(
                sql: "EXISTS ({$base} AND {$DB->sql_like('uid.data', ':pfvval', false, false)})",
                params: ['pfvfid' => $fieldid, 'pfvval' => $DB->sql_like_escape($value) . '%'],
            ),
            matchmode::ENDS_WITH => new userfilter_clause(
                sql: "EXISTS ({$base} AND {$DB->sql_like('uid.data', ':pfvval', false, false)})",
                params: ['pfvfid' => $fieldid, 'pfvval' => '%' . $DB->sql_like_escape($value)],
            ),
            matchmode::EMPTY => new userfilter_clause(
                sql: "NOT EXISTS ({$base} AND uid.data != '' AND uid.data IS NOT NULL)",
                params: ['pfvfid' => $fieldid],
            ),
            matchmode::NOT_EMPTY => new userfilter_clause(
                sql: "EXISTS ({$base} AND uid.data != '' AND uid.data IS NOT NULL)",
                params: ['pfvfid' => $fieldid],
            ),
        };
    }

    /**
     * Returns an array of descriptors for every setting this filter sub-plugin
     * defines and exposes.
     *
     * @return instance_setting_descriptor[] An array of setting descriptors
     * @throws \coding_exception
     * @throws \dml_exception
     */
    #[\Override]
    public static function instance_setting_descriptors(): array {
        return [
            new instance_setting_descriptor(
                key: 'field',
                title: new lang_string('setting_field', 'userdeletefilter_profilefield'),
                type: PARAM_TEXT,
                required: true,
                default: null,
                choices: self::get_available_fields(),
                mformtype: 'select',
            ),
            new instance_setting_descriptor(
                key: 'matchmode',
                title: new lang_string('setting_matchmode', 'userdeletefilter_profilefield'),
                type: PARAM_TEXT,
                required: true,
                default: matchmode::CONTAINS->value,
                choices: self::get_match_modes(),
                mformtype: 'select',
            ),
            new instance_setting_descriptor(
                key: 'value',
                title: new lang_string('setting_value', 'userdeletefilter_profilefield'),
                type: PARAM_TEXT,
                required: false,
                default: '',
                mformtype: 'text',
            ),
        ];
    }

    /**
     * Returns a flat associative array of all available profile fields.
     *
     * Field keys use the prefix 'std:' or 'custom:' followed by the field identifier.
     *
     * @return string[] Flat array of field key => human-readable label
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_available_fields(): array {
        global $CFG;
        require_once("{$CFG->dirroot}/user/profile/lib.php");

        // Default user profile fields.
        $fields = [
            self::PREFIX_STD . 'fullname' => get_string('field_std_fullname', 'userdeletefilter_profilefield'),
            self::PREFIX_STD . 'firstname' => get_string('field_std_firstname', 'userdeletefilter_profilefield'),
            self::PREFIX_STD . 'lastname' => get_string('field_std_lastname', 'userdeletefilter_profilefield'),
            self::PREFIX_STD . 'alternatename' => get_string('field_std_alternatename', 'userdeletefilter_profilefield'),
            self::PREFIX_STD . 'idnumber' => get_string('field_std_idnumber', 'userdeletefilter_profilefield'),
            self::PREFIX_STD . 'email' => get_string('field_std_email', 'userdeletefilter_profilefield'),
            self::PREFIX_STD . 'department' => get_string('field_std_department', 'userdeletefilter_profilefield'),
            self::PREFIX_STD . 'institution' => get_string('field_std_institution', 'userdeletefilter_profilefield'),
            self::PREFIX_STD . 'city' => get_string('field_std_city', 'userdeletefilter_profilefield'),
            self::PREFIX_STD . 'country' => get_string('field_std_country', 'userdeletefilter_profilefield'),
        ];

        // Custom profile fields.
        foreach (profile_get_custom_fields() as $customfield) {
            $fields[self::PREFIX_CUSTOM . $customfield->shortname] = $customfield->name;
        }

        return $fields;
    }

    /**
     * Returns an associative array of all available match modes.
     *
     * Keys are the match mode identifiers (matchmode backed values), values are
     * localized human-readable labels.
     *
     * @return string[] Flat array of matchmode key => localized label
     * @throws \coding_exception
     */
    public static function get_match_modes(): array {
        return [
            matchmode::CONTAINS->value => get_string('matchmode_contains', 'userdeletefilter_profilefield'),
            matchmode::NOT_CONTAINS->value => get_string('matchmode_not_contains', 'userdeletefilter_profilefield'),
            matchmode::EQUALS->value => get_string('matchmode_equals', 'userdeletefilter_profilefield'),
            matchmode::NOT_EQUALS->value => get_string('matchmode_not_equals', 'userdeletefilter_profilefield'),
            matchmode::STARTS_WITH->value => get_string('matchmode_starts_with', 'userdeletefilter_profilefield'),
            matchmode::ENDS_WITH->value => get_string('matchmode_ends_with', 'userdeletefilter_profilefield'),
            matchmode::EMPTY->value => get_string('matchmode_empty', 'userdeletefilter_profilefield'),
            matchmode::NOT_EMPTY->value => get_string('matchmode_not_empty', 'userdeletefilter_profilefield'),
        ];
    }
}
