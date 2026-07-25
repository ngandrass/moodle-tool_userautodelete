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

$string['pluginname'] = 'Profilfeld setzen';
$string['error_field_not_found'] = 'Das ausgewählte Profilfeld wurde nicht gefunden. Möglicherweise wurde es gelöscht.';
$string['error_invalid_country_code'] = 'Der Wert muss ein gültiger ISO 3166-1 alpha-2 Ländercode sein (z.B. DE, US, GB).';
$string['field_std_city'] = 'Ort';
$string['field_std_country'] = 'Land';
$string['field_std_department'] = 'Abteilung';
$string['field_std_firstname'] = 'Vorname';
$string['field_std_idnumber'] = 'ID-Nummer';
$string['field_std_institution'] = 'Institution';
$string['field_std_lastname'] = 'Nachname';
$string['privacy:metadata'] = 'Dieses Plugin speichert keine personenbezogenen Daten.';
$string['setting_field'] = 'Profilfeld';
$string['setting_field_help'] = 'Wählen Sie das Nutzerprofilfeld aus, dessen Wert durch diese Aktion gesetzt werden soll.';
$string['setting_value'] = 'Wert';
$string['setting_value_help'] = 'Geben Sie den Wert ein, auf den das Profilfeld gesetzt werden soll. Leer lassen, um das Feld zu leeren.';
