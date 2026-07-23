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

$string['pluginname'] = 'Profilfeld';
$string['privacy:metadata'] = 'Dieses Plugin speichert keine personenbezogenen Daten.';

// Settings.
$string['setting_field'] = 'Profilfeld';
$string['setting_field_help'] = 'Wählen Sie das Nutzerprofilfeld aus, dessen Wert von diesem Filter geprüft werden soll.';
$string['setting_matchmode'] = 'Vergleichstyp';
$string['setting_matchmode_help'] = 'Wählen Sie aus, wie der Wert des Profilfelds mit dem unten angegebenen Vergleichswert verglichen werden soll.';
$string['setting_value'] = 'Vergleichswert';
$string['setting_value_help'] = 'Geben Sie den Wert ein, mit dem das Profilfeld verglichen werden soll. Wird bei den Modi "Ist leer" und "Ist nicht leer" ignoriert.';

// Match modes.
$string['matchmode_contains'] = 'enthält';
$string['matchmode_not_contains'] = 'enthält nicht';
$string['matchmode_equals'] = 'ist gleich';
$string['matchmode_not_equals'] = 'ist ungleich';
$string['matchmode_starts_with'] = 'beginnt mit';
$string['matchmode_ends_with'] = 'endet mit';
$string['matchmode_empty'] = 'ist leer';
$string['matchmode_not_empty'] = 'ist nicht leer';

// Standard field labels.
$string['field_std_fullname'] = 'Vollständiger Name';
$string['field_std_firstname'] = 'Vorname';
$string['field_std_lastname'] = 'Nachname';
$string['field_std_alternatename'] = 'Spitzname';
$string['field_std_idnumber'] = 'ID-Nummer';
$string['field_std_email'] = 'E-Mail-Adresse';
$string['field_std_department'] = 'Abteilung';
$string['field_std_institution'] = 'Institution';
$string['field_std_city'] = 'Ort';
$string['field_std_country'] = 'Land';

// Error messages.
$string['error_value_required'] = 'Für den gewählten Vergleichstyp ist ein Vergleichswert erforderlich.';
$string['error_field_not_found'] = 'Das ausgewählte Profilfeld wurde nicht gefunden. Möglicherweise wurde es gelöscht.';
