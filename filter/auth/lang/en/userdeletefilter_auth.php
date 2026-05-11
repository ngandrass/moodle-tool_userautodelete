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
 * @package     userdeletefilter_auth
 * @category    string
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreFile

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

$string['pluginname'] = 'Auth method';
$string['privacy:metadata'] = 'This plugin does not store any personal data.';
$string['setting_auths'] = 'Authentication methods';
$string['setting_auths_help'] = 'Select one or more authentication methods that should be targeted by this filter.';
$string['setting_inverted'] = 'Inverted match';
$string['setting_inverted_help'] = 'If set to yes, all users with authentication methods that are <b>different from the above</b> selected will be affected. If set to no, only users with one of the above selected authentication methods will be affected.';
