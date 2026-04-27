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
 * @package     userdeletefilter_role
 * @category    string
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreFile

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

$string['pluginname'] = 'Rolle';
$string['privacy:metadata'] = 'Dieses Plugin speichert keine personenbezogenen Daten.';
$string['setting_inverted'] = 'Invertiert';
$string['setting_inverted_help'] = 'Wenn diese Option aktiviert ist, werden nur Nutzer ausgewählt, die <b>keine der oben ausgewählten Rollen in irgendeinem Kontext zugewiesen haben</b>. Wenn diese Option deaktiviert ist, werden nur Nutzer ausgewählt, die alle der oben ausgewählten Rollen zugewiesen haben.';
$string['setting_roleids'] = 'Rollen';
$string['setting_roleids_help'] = 'Wählen Sie eine oder mehrere Rollen aus, die einem Nutzer zugewiesen sein müssen, damit dieser von diesem Filter betroffen ist. Rollenzuweisungen werden unabhängig vom Kontextlevel der Zuweisung überprüft. Das bedeutet, dass bei der Filterung von Manager-Nutzern sowohl globale als auch Kurs-Manager-Nutzer betroffen sein werden.';
