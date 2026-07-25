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
 * User action that sets user profile field values.
 *
 * @package     userdeleteaction_profilefield
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace userdeleteaction_profilefield;

use core\lang_string;
use tool_userautodelete\local\type\instance_setting_descriptor;
use tool_userautodelete\process;

// phpcs:ignore
defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore


/**
 * User action that sets a user profile field to a configured value.
 */
class userdeleteaction extends \tool_userautodelete\userdeleteaction {
    /** @var string Prefix for standard user table fields in the field setting value */
    const PREFIX_STD = 'std:';

    /** @var string Prefix for custom profile fields in the field setting value */
    const PREFIX_CUSTOM = 'custom:';

    /**
     * Returns the name of this action sub-plugin.
     *
     * @return string The name of this action sub-plugin
     */
    public static function get_plugin_name(): string {
        return 'profilefield';
    }

    /**
     * Returns a font-awesome icon CSS class string that is shown in the UI for
     * this action sub-plugin type.
     *
     * @return string A font-awesome icon CSS class string combination
     */
    public static function get_icon_class(): string {
        return 'fa-solid fa-user-pen';
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
        return new \moodle_url('https://moodleuserlifecycle.gandrass.de/actions/profilefield/');
    }

    /**
     * Returns a descriptive string of this action instance's settings to be shown in the UI.
     *
     * @return string A descriptive string of this action instance's settings to be shown in the UI
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_instance_details(): string {
        $field = $this->get_instance_setting('field');

        if (!$field) {
            return '';
        }

        $value = $this->get_instance_setting('value');
        $fieldlabel = self::get_available_fields()[$field] ?? $field;

        if (empty($value)) {
            return get_string('clear') . ": {$fieldlabel}";
        } else {
            return "{$fieldlabel}: \"{$value}\"";
        }
    }

    /**
     * Validates this action instance and returns an error string if invalid.
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
            return get_string('error_field_not_found', 'userdeleteaction_profilefield');
        }

        $value = $this->get_instance_setting('value') ?? '';
        if ($field === self::PREFIX_STD . 'country' && !empty($value) && !self::is_valid_country_code($value)) {
            return get_string('error_invalid_country_code', 'userdeleteaction_profilefield');
        }

        return null;
    }

    /**
     * Validates the given settings data before saving and returns per-key error messages.
     *
     * @param array $settings Associative array of setting key-value pairs to validate
     * @return string[] Associative array of setting key => localized error message for each invalid setting
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function validate_instance_settings_data(array $settings): array {
        $errors = parent::validate_instance_settings_data($settings);

        $field = $settings['field'] ?? null;
        $value = $settings['value'] ?? '';

        if (!array_key_exists($field, self::get_available_fields())) {
            $errors['field'] = get_string('error_field_not_found', 'userdeleteaction_profilefield');
        }

        if ($field === self::PREFIX_STD . 'country' && !empty($value) && !self::is_valid_country_code($value)) {
            $errors['value'] = get_string('error_invalid_country_code', 'userdeleteaction_profilefield');
        }

        return $errors;
    }

    /**
     * Executes this action for a given user deletion process.
     *
     * Sets the configured profile field to the configured value.
     *
     * @param process $process The user deletion process to execute this action for
     * @return bool True if the action was executed successfully, false otherwise
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function execute(process $process): bool {
        global $CFG;
        require_once("{$CFG->dirroot}/user/profile/lib.php");

        $field = $this->get_instance_setting('field');
        $value = $this->get_instance_setting('value') ?? '';

        try {
            if (str_starts_with($field, self::PREFIX_STD)) {
                // Handle standard core user fields.
                $fieldname = substr($field, strlen(self::PREFIX_STD));
                user_update_user((object)['id' => $process->userid, $fieldname => $value], false, false);
            } else if (str_starts_with($field, self::PREFIX_CUSTOM)) {
                // Handle custom user fields.
                $shortname = substr($field, strlen(self::PREFIX_CUSTOM));
                profile_save_custom_fields($process->userid, [$shortname => $value]);
            } else {
                throw new \coding_exception('Encountered invalid field name prefix. Please report this bug!');
            }
        } catch (\Exception) {
            return false;
        }

        return true;
    }

    /**
     * Returns an array of descriptors for every setting this action sub-plugin
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
                title: new lang_string('setting_field', 'userdeleteaction_profilefield'),
                type: PARAM_TEXT,
                required: true,
                default: null,
                choices: self::get_available_fields(),
                mformtype: 'select',
            ),
            new instance_setting_descriptor(
                key: 'value',
                title: new lang_string('setting_value', 'userdeleteaction_profilefield'),
                type: PARAM_TEXT,
                required: false,
                default: '',
                mformtype: 'text',
            ),
        ];
    }

    /**
     * Returns true if the given string is a valid ISO 3166-1 alpha-2 country code.
     *
     * Uses Moodle's built-in country list as the authoritative source.
     * An empty string is not considered a valid country code.
     *
     * @param string $code The country code to validate
     * @return bool True if the code is a recognized ISO 3166-1 alpha-2 country code
     */
    protected static function is_valid_country_code(string $code): bool {
        return array_key_exists($code, get_string_manager()->get_list_of_countries(true));
    }

    /**
     * Returns a flat associative array of all available profile fields.
     *
     * Field keys use the prefix 'std:' for standard user table fields and
     * 'custom:' for custom profile fields followed by the field identifier.
     *
     * @return string[] Flat array of field key => human-readable label
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_available_fields(): array {
        global $CFG;
        require_once("{$CFG->dirroot}/user/profile/lib.php");

        $fields = [
            self::PREFIX_STD . 'firstname' => get_string('field_std_firstname', 'userdeleteaction_profilefield'),
            self::PREFIX_STD . 'lastname' => get_string('field_std_lastname', 'userdeleteaction_profilefield'),
            self::PREFIX_STD . 'idnumber' => get_string('field_std_idnumber', 'userdeleteaction_profilefield'),
            self::PREFIX_STD . 'department' => get_string('field_std_department', 'userdeleteaction_profilefield'),
            self::PREFIX_STD . 'institution' => get_string('field_std_institution', 'userdeleteaction_profilefield'),
            self::PREFIX_STD . 'city' => get_string('field_std_city', 'userdeleteaction_profilefield'),
            self::PREFIX_STD . 'country' => get_string('field_std_country', 'userdeleteaction_profilefield'),
        ];

        foreach (profile_get_custom_fields() as $customfield) {
            $fields[self::PREFIX_CUSTOM . $customfield->shortname] = $customfield->name;
        }

        return $fields;
    }
}
