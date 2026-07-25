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
 * Plugin strings are defined here.
 *
 * @package     userdeleteaction_profilefield
 * @category    string
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreFile

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

$string['pluginname'] = 'Set Profile Field';
$string['privacy:metadata'] = 'This plugin does not store any personal data.';
$string['setting_field'] = 'Profile field';
$string['setting_field_help'] = 'Select the user profile field whose value should be set by this action.';
$string['setting_value'] = 'Value';
$string['setting_value_help'] = 'Enter the value to set the profile field to. Leave empty to clear the field.';
$string['field_std_firstname'] = 'First name';
$string['field_std_lastname'] = 'Last name';
$string['field_std_idnumber'] = 'ID number';
$string['field_std_department'] = 'Department';
$string['field_std_institution'] = 'Institution';
$string['field_std_city'] = 'City / Town';
$string['field_std_country'] = 'Country';
$string['error_field_not_found'] = 'The selected profile field could not be found. It may have been deleted.';
$string['error_invalid_country_code'] = 'The value must be a valid ISO 3166-1 alpha-2 country code (e.g. DE, US, GB).';
