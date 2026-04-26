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
 * @package     tool_userautodelete
 * @category    string
 * @copyright   2026 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreFile

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

$string['affected_users'] = 'Betroffene Nutzer';
$string['back_to_settings'] = 'Zurück zu den Einstellungen';
$string['defaultworkflow_deletemail_message'] = '<p>Hallo,</p><p>Ihr Konto auf unserer Seite wurde aufgrund von Inaktivität gelöscht. Wenn Sie unseren Dienst weiterhin nutzen möchten, erstellen Sie sich bitte ein neues Konto.</p><p>Mit freundlichen Grüßen</p>';
$string['defaultworkflow_deletemail_subject'] = 'Ihr Konto wurde gelöscht';
$string['defaultworkflow_desc'] = 'Dieser Ablauf wird standardmäßig während der Plugin-Installation erstellt.';
$string['defaultworkflow_title'] = 'Standardablauf';
$string['defaultworkflow_warningmail_message'] = '<p>Hallo,</p><p>Ihr Konto auf unserer Seite war für einen langen Zeitraum inaktiv. Um Ihr Konto zu behalten, <strong>loggen Sie sich jetzt ein, um zu verhindern, dass Ihr Konto gemäß unserer Datenschutzrichtlinie in den nächsten Tagen gelöscht wird</strong>.</p><p>Wenn Sie möchten, dass Ihr Konto gelöscht wird, können Sie diese Nachricht ignorieren.</p><p>Mit freundlichen Grüßen</p>';
$string['defaultworkflow_warningmail_subject'] = 'Ihr Konto wird bald gelöscht - Handlung erforderlich!';
$string['deleted'] = 'Gelöscht';
$string['dry_run'] = 'Probelauf';
$string['dry_run_explanation'] = 'Diese Seite zeigt die Aktionen, die während der nächsten Inaktivitätsprüfung ausgeführt werden würden. Keine der Aktionen wurde tatsächlich ausgeführt!';
$string['inactivity_warning'] = 'Inaktivitätswarnung';
$string['last_check'] = 'Letzte Prüfung';
$string['manage_workflows'] = 'Abläufe verwalten';
$string['next_check'] = 'Nächste Prüfung';
$string['next_check_never'] = 'Nie (Plugin deaktiviert)';
$string['next_check_would'] = 'Würde prüfen';
$string['plugin_disabled_skipping_execution'] = 'Das Plugin ist global deaktiviert, überspringe Ausführung.';
$string['pluginname'] = 'Automatische Nutzerlöschung';
$string['privacy:metadata:tool_userautodelete_process'] = 'Informationen über Nutzer, die Teil eines Nutzerzykluss-Ablaufs sind.';
$string['privacy:metadata:tool_userautodelete_process:userid'] = 'Die ID des Nutzers, der Teil des Ablaufprozesses ist.';
$string['privacy:metadata:tool_userautodelete_process:stepid'] = 'Die ID des Ablauf-Schritts, in dem sich der Nutzer aktuell befindet.';
$string['privacy:metadata:tool_userautodelete_process:state'] = 'Der aktuelle Zustand des Nutzerprozesses.';
$string['privacy:metadata:tool_userautodelete_process:timecreated'] = 'Der Zeitpunkt, zu dem der Nutzer in den Ablauf aufgenommen wurde.';
$string['privacy:metadata:tool_userautodelete_process:timemodified'] = 'Der Zeitpunkt der letzten Aktualisierung des Nutzerprozesses.';
$string['recovered'] = 'Zurückgekehrt';
$string['reltime_prefix_ago'] = 'vor';
$string['reltime_prefix_in'] = 'in';
$string['reltime_suffix_ago'] = '';
$string['reltime_suffix_in'] = '';
$string['setting_enable'] = 'Plugin aktivieren';
$string['setting_enable_desc'] = 'Aktiviert oder deaktiviert das Plugin global. Wenn dies deaktiviert ist, werden keine Aktionen ausgeführt.';
$string['setting_plugin_desc'] = 'Dieses Plugin löscht automatisch Nutzer die sich für eine konfigurierbare Anzahl von Tagen nicht eingeloggt haben. Dies ist nützlich, um die Moodle-Datenbank sauber zu halten und inaktive Nutzerkonten zu entfernen. Das Plugin kann so konfiguriert werden, dass inaktive Nutzer einige Tage vor der Kontolöschung eine Warn-E-Mail erhalten. Dies gibt Nutzern die Möglichkeit, sich erneut einzuloggen und ihre Konten zu behalten. Das Plugin unterstützt außerdem das Löschen von Nutzern auf DSGVO-konforme Weise, sodass keine personenbezogene Daten in der Datenbank verbleiben.';
$string['setting_task_execution_interval'] = 'Prüfintervall';
$string['setting_task_execution_interval_button'] = 'Prüfintervall konfigurieren';
$string['setting_task_execution_interval_desc'] = 'Die Überprüfung auf inaktive Nutzer wird über einen geplante Task durchgeführt, der über den Moodle-Cron ausgeführt wird. Sie können das Intervall, in dem dieser Task ausgeführt wird, über den folgenden Button konfigurieren.';
$string['subplugintype_userdeleteaction'] = 'Nutzerzyklus Aktion';
$string['subplugintype_userdeleteaction_plural'] = 'Nutzerzyklus Aktionen';
$string['subplugintype_userdeletefilter'] = 'Nutzerzyklus Filter';
$string['subplugintype_userdeletefilter_plural'] = 'Nutzerzyklus Filter';
$string['task_check_and_delete_users'] = 'Inaktive Nutzer suchen und löschen';
$string['users_to_delete'] = 'Zu löschende Nutzer';
$string['users_to_warn'] = 'Zu warnende Nutzer';
$string['warned'] = 'Gewarnt';
$string['workflow'] = 'Ablauf';
$string['workflows_plugin_disabled_warning'] = 'Mindestens ein Ablauf ist aktiv, aber das Plugin ist <b>global deaktiviert</b>. Aktivieren Sie es in den <a href="{$a}">Plugin-Einstellungen</a>, damit die Ausführung aufgenommen wird.';
