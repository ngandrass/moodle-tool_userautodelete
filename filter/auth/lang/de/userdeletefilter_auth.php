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

$string['pluginname'] = 'Auth Methode';
$string['privacy:metadata'] = 'Dieses Plugin speichert keine personenbezogenen Daten.';
$string['setting_auths'] = 'Authentifizierungsmethoden';
$string['setting_auths_help'] = 'Wählen Sie eine oder mehrere Authentifizierungsmethoden aus die für die Filterung genutzt werden sollen.';
$string['setting_inverted'] = 'Invertierte Übereinstimmung';
$string['setting_inverted_help'] = 'Wenn diese Option aktiviert ist, werden alle Nutzer mit Authentifizierungsmethoden die <b>von den gewählten abweichen</b> ausgewählt. Wenn diese Option deaktiviert ist, werden ausschließlich Nutzer die eine der oben gewählten Authentifizierungsmethoden nutzen ausgewählt.';
