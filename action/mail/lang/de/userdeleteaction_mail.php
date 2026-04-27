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
 * @package     userdeleteaction_mail
 * @category    string
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreFile

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

$string['pluginname'] = 'E-Mail senden';
$string['privacy:metadata'] = 'Dieses Plugin speichert keine personenbezogenen Daten.';
$string['error_unknown_variables'] = 'Die folgenden Variablen sind unbekannt und können nicht aufgelöst werden: {$a}. Entfernen oder korrigieren Sie diese vor der Speicherung.';
$string['setting_message'] = 'Nachricht';
$string['setting_message_help'] = 'Der Inhalt der E-Mail, die an den Benutzer gesendet wird. Sie können alle verfügbaren Formatierungsoptionen sowie die unten aufgeführten Variablen verwenden.

<strong>Verfügbare Variablen:</strong>

<ul>
<li><code>{{user.id}}</code> - ID des Kontos</li>
<li><code>{{user.username}}</code> - Benutzername</li>
<li><code>{{user.firstname}}</code> - Vorname</li>
<li><code>{{user.lastname}}</code> - Nachname</li>
<li><code>{{user.email}}</code> - E-Mail-Adresse</li>
<li><code>{{user.idnumber}}</code> - ID-Nummer des Benutzers</li>
<li><code>{{user.institution}}</code> - Institution</li>
<li><code>{{user.timecreated}}</code> - Datum und Uhrzeit der Kontoerstellung</li>
<li><code>{{user.lastaccess}}</code> - Datum und Uhrzeit des letzten Zugriffs</li>
<li><code>{{user.lastaccessrelative}}</code> - Relative Zeit seit dem letzten Zugriff</li>
<li><code>{{user.lastip}}</code> - Letzte IP-Adresse</li>
<li><code>{{user.city}}</code> - Stadt</li>
<li><code>{{user.country}}</code> - Ländercode</li>
</ul>
<hr>
<ul>
<li><code>{{site.name}}</code> - Vollständiger Website-Name</li>
<li><code>{{site.shortname}}</code> - Kurzname der Website</li>
<li><code>{{site.supportemail}}</code> - Support-E-Mail-Adresse der Website</li>
</ul>
<hr>
<ul>
<li><code>{{urls.home}}</code> - URL der Startseite</li>
<li><code>{{urls.login}}</code> - URL der Anmeldeseite</li>
<li><code>{{urls.profile}}</code> - URL zum Profil des Benutzers</li>
<li><code>{{urls.support}}</code> - URL zum Kontakt des Website-Supports</li>
</ul>';
$string['setting_subject'] = 'Betreff';
$string['setting_subject_help'] = 'Betreff der E-Mail, die an den Benutzer gesendet wird. Sie können alle verfügbaren Formatierungsoptionen sowie die unten aufgeführten Variablen verwenden.

<strong>Verfügbare Variablen:</strong>

<ul>
<li><code>{{user.id}}</code> - ID des Kontos</li>
<li><code>{{user.username}}</code> - Benutzername</li>
<li><code>{{user.firstname}}</code> - Vorname</li>
<li><code>{{user.lastname}}</code> - Nachname</li>
<li><code>{{user.email}}</code> - E-Mail-Adresse</li>
<li><code>{{user.idnumber}}</code> - ID-Nummer des Benutzers</li>
<li><code>{{user.institution}}</code> - Institution</li>
<li><code>{{user.timecreated}}</code> - Datum und Uhrzeit der Kontoerstellung</li>
<li><code>{{user.lastaccess}}</code> - Datum und Uhrzeit des letzten Zugriffs</li>
<li><code>{{user.lastaccessrelative}}</code> - Relative Zeit seit dem letzten Zugriff</li>
<li><code>{{user.lastip}}</code> - Letzte IP-Adresse</li>
<li><code>{{user.city}}</code> - Stadt</li>
<li><code>{{user.country}}</code> - Ländercode</li>
</ul>
<hr>
<ul>
<li><code>{{site.name}}</code> - Vollständiger Website-Name</li>
<li><code>{{site.shortname}}</code> - Kurzname der Website</li>
<li><code>{{site.supportemail}}</code> - Support-E-Mail-Adresse der Website</li>
</ul>
<hr>
<ul>
<li><code>{{urls.home}}</code> - URL der Startseite</li>
<li><code>{{urls.login}}</code> - URL der Anmeldeseite</li>
<li><code>{{urls.profile}}</code> - URL zum Profil des Benutzers</li>
<li><code>{{urls.support}}</code> - URL zum Kontakt des Website-Supports</li>
</ul>';
