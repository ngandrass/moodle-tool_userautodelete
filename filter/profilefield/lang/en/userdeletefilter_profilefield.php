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
 * @package     userdeletefilter_profilefield
 * @category    string
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreFile

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

$string['pluginname'] = 'Profile field';
$string['privacy:metadata'] = 'This plugin does not store any personal data.';

// Settings.
$string['setting_field'] = 'Profile field';
$string['setting_field_help'] = 'Select the user profile field whose value should be checked by this filter.';
$string['setting_matchmode'] = 'Match mode';
$string['setting_matchmode_help'] = 'Select how the profile field value should be compared against the value below.';
$string['setting_value'] = 'Comparison value';
$string['setting_value_help'] = 'Enter the value to compare the profile field against. Not used for the "Is empty" and "Is not empty" match modes.';

// Match modes.
$string['matchmode_contains'] = 'contains';
$string['matchmode_not_contains'] = 'does not contain';
$string['matchmode_equals'] = 'is equal to';
$string['matchmode_not_equals'] = 'is not equal to';
$string['matchmode_starts_with'] = 'starts with';
$string['matchmode_ends_with'] = 'ends with';
$string['matchmode_empty'] = 'is empty';
$string['matchmode_not_empty'] = 'is not empty';

// Standard field labels.
$string['field_std_fullname'] = 'Full name';
$string['field_std_firstname'] = 'First name';
$string['field_std_lastname'] = 'Last name';
$string['field_std_alternatename'] = 'Nickname';
$string['field_std_idnumber'] = 'ID number';
$string['field_std_email'] = 'Email address';
$string['field_std_department'] = 'Department';
$string['field_std_institution'] = 'Institution';
$string['field_std_city'] = 'City / Town';
$string['field_std_country'] = 'Country';

// Error messages.
$string['error_value_required'] = 'A comparison value is required for the selected match mode.';
$string['error_field_not_found'] = 'The selected profile field could not be found. It may have been deleted.';
