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

$string['aborted'] = 'Abgebrochen';
$string['action'] = 'Aktion';
$string['action_is_invalid'] = 'Diese Aktion ist aktuell ungültig und muss korrigiert werden, bevor der Workflow aktiviert werden kann. Bitte stellen Sie sicher, dass alle erforderlichen Konfigurationsfelder dieser Aktion korrekt ausgefüllt sind.';
$string['actions'] = 'Aktionen';
$string['active_processes'] = '{$a} aktive Prozesse';
$string['add_action'] = 'Aktion hinzufügen';
$string['add_action_desc'] = 'Klicken Sie auf die Aktion, die Sie zum Workflow-Schritt hinzufügen möchten.';
$string['add_filter'] = 'Filter hinzufügen';
$string['add_filter_desc'] = 'Klicken Sie auf den Filter, den Sie zum Workflow-Schritt hinzufügen möchten.';
$string['add_step'] = 'Schritt hinzufügen';
$string['affected_users'] = 'Betroffene Nutzer';
$string['back_to_overview'] = 'Zurück zur Übersicht';
$string['back_to_settings'] = 'Zurück zu den Einstellungen';
$string['back_to_workflow'] = 'Zurück zum Workflow';
$string['can_not_activate_invalid_workflow'] = 'Dieser Workflow enthält ungültige Schritte und kann daher derzeit nicht aktiviert werden. Bitte beheben Sie die Probleme und versuchen Sie es erneut.';
$string['can_not_edit_active_workflow'] = 'Aktive Workflows müssen deaktiviert werden, bevor sie bearbeitet werden können.';
$string['create_default_workflow'] = 'Neuen Standard-Workflow erstellen';
$string['create_default_workflow_desc'] = 'Ein vordefinierter Workflow, der inaktive Nutzer zunächst warnt und sie erst nach einer Karenzzeit löscht.';
$string['create_empty_workflow'] = 'Leeren Workflow erstellen';
$string['create_empty_workflow_desc'] = 'Ein neuer Workflow ohne Schritte, Aktionen oder Filter. Damit können Sie einen individuellen Workflow von Grund auf erstellen.';
$string['create_new_workflow'] = 'Neuen Workflow erstellen';
$string['defaultworkflow_delete_step_desc'] = 'Löscht Nutzer, die seit langer Zeit inaktiv sind.';
$string['defaultworkflow_delete_step_title'] = 'Inaktive Nutzer löschen';
$string['defaultworkflow_deletemail_message'] = '<p>Hallo {{user.firstname}} {{user.lastname}},</p><p>Ihr Konto auf <a href="{{urls.home}}">{{site.name}}</a> wurde aufgrund von Inaktivität gelöscht. Diese Löschung erfolgte gemäß unserer Datenschutzrichtlinien.</p><p>Wenn Sie {{site.shortname}} wieder nutzen möchten, erstellen Sie bitte ein neues Konto unter <a href="{{urls.home}}">{{urls.home}}</a>.</p><p>Mit freundlichen Grüßen,<br>Ihr {{site.name}} Team</p>';
$string['defaultworkflow_deletemail_subject'] = '{{site.shortname}}: Ihr Konto wurde gelöscht';
$string['defaultworkflow_desc'] = 'Sendet inaktiven Nutzenden eine Warnungs-Mail und löscht die Konten wenn kein Login erfolgt.';
$string['defaultworkflow_title'] = 'Standard-Workflow';
$string['defaultworkflow_warning_step_desc'] = 'Sendet inaktiven Nutzern eine Warn-E-Mail. Nutzer werden anhand der angegebenen Filterkriterien ausgewählt.';
$string['defaultworkflow_warning_step_title'] = 'Inaktive Nutzer warnen';
$string['defaultworkflow_warningmail_message'] = '<p>Hallo {{user.firstname}} {{user.lastname}},</p><p>Ihr Konto auf <a href="{{urls.home}}">{{site.name}}</a> war in den letzten drei Jahren inaktiv. Um Ihr Konto zu behalten, <strong><a href="{{urls.login}}">loggen Sie sich jetzt ein</a></strong>. Ohne Login wird Ihr Konto in den nächsten 30 Tagen gemäß unserer Datenschutzrichtlinien gelöscht.</p><p>Wenn Sie Ihr Konto nicht mehr benötigen, können Sie diese Nachricht ignorieren.</p><p>Mit freundlichen Grüßen,<br>Ihr {{site.name}} Team</p><p>Wenn Sie Hilfe benötigen, besuchen Sie bitte <a href="{{urls.support}}">{{urls.support}}</a>.</p>';
$string['defaultworkflow_warningmail_subject'] = '{{site.shortname}}: Bitte einloggen, um Ihr Konto zu behalten!';
$string['delete_action'] = 'Aktion löschen';
$string['delete_filter'] = 'Filter löschen';
$string['delete_step'] = 'Schritt löschen';
$string['delete_step_warning'] = 'Sie sind dabei, einen Schritt zu löschen. Hierbei werden auch alle zugehörigen Aktionen und Filter gelöscht. Möchten Sie wirklich fortfahren?';
$string['delete_workflow'] = 'Workflow löschen';
$string['delete_workflow_warning'] = '<p>Sie sind dabei, einen <b>Workflow dauerhaft zu löschen</b>. Dadurch werden alle aktiven Nutzerprozesse aus diesem Workflow entfernt und alle zugehörigen Schritte einschließlich ihrer Filter und Aktionen gelöscht. Möchten Sie wirklich fortfahren?</p><p>Statt zu löschen, können Sie einen bestehenden Workflow auf dessen Detailseite einfach deaktivieren. Dadurch werden Nutzer nicht mehr durch diesen Workflow verarbeitet, ohne die Workflow-Definition und Einstellungen zu entfernen.</p>';
$string['deleted'] = 'Gelöscht';
$string['description'] = 'Beschreibung';
$string['disable_workflow'] = 'Workflow deaktivieren';
$string['disable_workflow_warning'] = '<p>Sie sind dabei, den unten aufgeführten Workflow zu deaktivieren. Bis zur erneuten Aktivierung werden keine neuen Nutzer verarbeitet. Andere aktive Workflows sind davon nicht betroffen. Beim Deaktivieren werden außerdem alle aktiven Nutzerprozesse in ihrem aktuellen Zustand beendet.</p><p>Der Workflow kann später jederzeit wieder aktiviert werden, allerdings beginnen dann alle zutreffenden Nutzer wieder beim ersten Schritt. Möchten Sie den Workflow jetzt deaktivieren?</p>';
$string['dry_run'] = 'Probelauf';
$string['dry_run_explanation'] = '<p>Auf dieser Seite finden Sie alle Nutzer, die bei einer Ausführung in diesen Workflow aufgenommen werden würden. Zu Zeitpunkt dieses Probelaufs wurde keins der Nutzerkonten wirklich in den Workflow aufgenommen. Anhand der unten stehenden Liste können Sie sicher prüfen, dass der Workflow wie erwartet funktioniert.</p><p>Bitte beachten Sie, dass ein einzelner Nutzer zu einem gegebenen Zeitpunkt nur Teil eines einzigen Workflows sein kann. Daher werden unten nur Nutzer angezeigt, die die definierten Filterkriterien des ersten Workflow-Schritts erfüllen und noch keinem Workflow zugeordnet sind.</p>';
$string['edit_action'] = 'Aktion bearbeiten';
$string['edit_filter'] = 'Filter bearbeiten';
$string['edit_step'] = 'Schritt bearbeiten';
$string['edit_workflow'] = 'Workflow bearbeiten';
$string['enable_workflow'] = 'Workflow aktivieren';
$string['enable_workflow_warning'] = '<p>Sie sind dabei, den unten aufgeführten Workflow zu aktivieren. Dadurch werden weitere Änderungen an Schritten, Aktionen und Filtern verhindert, bis der Workflow wieder deaktiviert wird. Der Workflow kann später jederzeit deaktiviert werden. Beim Deaktivieren werden jedoch alle aktiven Nutzerprozesse in ihrem aktuellen Zustand beendet.</p><p>Sobald ein Workflow aktiv ist, werden passende Nutzer gemäß der Workflow-Definition automatisch verarbeitet. Möchten Sie den Workflow jetzt aktivieren?</p>';
$string['filter'] = 'Filter';
$string['filter_is_invalid'] = 'Dieser Filter ist aktuell ungültig und muss korrigiert werden, bevor der Workflow aktiviert werden kann. Bitte stellen Sie sicher, dass alle erforderlichen Konfigurationsfelder dieses Filters korrekt ausgefüllt sind.';
$string['filter_log_entries'] = 'Logeinträge filtern';
$string['filters'] = 'Filter';
$string['finished'] = 'Abgeschlossen';
$string['id'] = 'ID';
$string['in_step_since'] = 'Im Schritt seit';
$string['in_workflow_since'] = 'Im Workflow seit';
$string['inactivity_warning'] = 'Inaktivitätswarnung';
$string['last_check'] = 'Letzte Prüfung';
$string['log_filter_all_steps'] = 'Alle Schritte';
$string['log_filter_all_workflows'] = 'Alle Workflows';
$string['log_filter_apply'] = 'Filter anwenden';
$string['log_filter_reset'] = 'Zurücksetzen';
$string['logs'] = 'Logs';
$string['manage_workflow'] = 'Workflow verwalten';
$string['manage_workflow_desc'] = 'Diese Seite zeigt einen einzelnen Workflow an und erlaubt dessen Bearbeitung. Jeder Workflow besteht aus einem oder mehr Schritten mit jeweils Filtern und Aktionen. Die Filter definieren Kriterien zur Nutzerauswahl und den Zeitpunkt der Verarbeitung. Die Aktionen entscheiden, was mit den Nutzerkonten geschieht, die den jeweiligen Schritt erreichen.';
$string['manage_workflows'] = 'Workflows verwalten';
$string['manage_workflows_desc'] = 'Alle derzeit vorhandenen Workflows sind hier aufgelistet.';
$string['need_help_check_docs'] = 'Benötigen Sie Hilfe? Schauen Sie gerne in die umfassende <a href="https://moodleuserlifecycle.gandrass.de/" target="_blank">Online-Dokumentation</a> zu diesem Plugin.';
$string['newworkflow_desc'] = 'Dies ist ein neu erstellter Workflow';
$string['newworkflow_title'] = 'Neuer Workflow';
$string['next_check'] = 'Nächste Prüfung';
$string['next_check_never'] = 'Nie (Plugin deaktiviert)';
$string['next_check_would'] = 'Würde prüfen';
$string['no_workflows_exist_yet_create_one'] = 'Derzeit existieren noch keine Workflows. Sie können einen neuen Workflow mit den untenstehenden Buttons erstellen.';
$string['no_workflows_exist_yet_details'] = 'Für Beginner wird empfohlen, einen neuen Standard-Workflow anstelle eines leeren zu erstellen. Der Standard-Workflow sucht nach Nutzern, die seit langer Zeit inaktiv sind, und sendet ihnen eine Warn-E-Mail. Wenn sich Nutzer innerhalb einer Karenzzeit nicht wieder einloggen, werden ihre Konten gelöscht und anonymisiert (Entfernung von personenbezogenen Daten). Wenn Sie die Workflow-Schritte, Filter und Aktionen selbst definieren möchten, können Sie stattdessen einen leeren Workflow erstellen.';
$string['plugin_disabled_skipping_execution'] = 'Das Plugin ist global deaktiviert, überspringe Ausführung.';
$string['pluginname'] = 'Nutzerlebenszyklus-Management';
$string['privacy:metadata:tool_userautodelete_process'] = 'Informationen über Nutzer, die Teil eines Nutzerzyklus-Workflows sind.';
$string['privacy:metadata:tool_userautodelete_process:state'] = 'Der aktuelle Zustand des Nutzerprozesses.';
$string['privacy:metadata:tool_userautodelete_process:stepid'] = 'Die ID des Workflow-Schritts, in dem sich der Nutzer aktuell befindet.';
$string['privacy:metadata:tool_userautodelete_process:timecreated'] = 'Der Zeitpunkt, zu dem der Nutzer in den Workflow aufgenommen wurde.';
$string['privacy:metadata:tool_userautodelete_process:timemodified'] = 'Der Zeitpunkt der letzten Aktualisierung des Nutzerprozesses.';
$string['privacy:metadata:tool_userautodelete_process:userid'] = 'Die ID des Nutzers, der Teil des Workflow-Prozesses ist.';
$string['process'] = 'Prozess';
$string['processes'] = 'Prozesse';
$string['recovered'] = 'Zurückgekehrt';
$string['reltime_prefix_ago'] = 'vor';
$string['reltime_prefix_in'] = 'in';
$string['reltime_suffix_ago'] = '';
$string['reltime_suffix_in'] = '';
$string['selection_filters'] = 'Auswahlfilter';
$string['setting_enable'] = 'Plugin aktivieren';
$string['setting_enable_desc'] = 'Aktiviert oder deaktiviert das Plugin global. Wenn dies deaktiviert ist, werden keine Aktionen ausgeführt, selbst wenn einzelne Workflows aktiviert sind.';
$string['setting_plugin_desc'] = '<p>Dieses Plugin verwaltet den gesamten Lebenszyklus von Moodle-Nutzerkonten mithilfe frei konfigurierbarer, mehrstufiger Workflows.</p><p>Jeder Workflow kann aus mehreren Schritten mit einem oder mehreren Filtern (z. B. letzter Zugriff, Authentifizierungsmethode, Rollenzuweisung, ...) bestehen, die bestimmen welche Nutzer ausgewählt werden, sowie aus Aktionen (z. B. E-Mail senden, sperren, löschen, ...), die ausgeführt werden, wenn ein Nutzer einen Schritt erreicht. Dadurch lassen sich sowohl einfache als auch komplexe Nutzerzyklus-Workflows umsetzen, z. B. inaktive Nutzer warnen, sie nach einer Karenzzeit sperren und ihre Konten schließlich DSGVO-konform löschen. Ein integrierter Probelaufmodus und ein Aktionsprotokoll ermöglichen Administratoren eine sichere Vorschau und Nachvollziehbarkeit aller automatisierten Aktivitäten. Die Standard-Filter und -Aktionen können durch weitere Subplugins einfach erweitert werden.</p>';
$string['setting_task_execution_interval'] = 'Prüfintervall';
$string['setting_task_execution_interval_button'] = 'Prüfintervall konfigurieren';
$string['setting_task_execution_interval_desc'] = 'Die Überprüfung auf inaktive Nutzer wird über einen geplante Task durchgeführt, der über den Moodle-Cron ausgeführt wird. Sie können das Intervall, in dem dieser Task ausgeführt wird, über den folgenden Button konfigurieren.';
$string['state'] = 'Zustand';
$string['step'] = 'Schritt';
$string['step_is_invalid'] = 'Dieser Schritt ist aktuell ungültig und muss korrigiert werden, bevor der Workflow aktiviert werden kann. Jeder Schritt muss mindestens einen Filter und eine Aktion enthalten. Bitte stellen Sie außerdem sicher, dass alle Filter und Aktionen korrekt konfiguriert sind.';
$string['step_processes_none'] = 'Für diesen Schritt wurden keine Nutzerprozesse gefunden.';
$string['steps'] = 'Schritte';
$string['subplugin_has_no_instance_settings'] = 'Dieses Subplugin hat keine instanzspezifischen Einstellungen.';
$string['subplugintype_userdeleteaction'] = 'Nutzerzyklus Aktion';
$string['subplugintype_userdeleteaction_plural'] = 'Nutzerzyklus Aktionen';
$string['subplugintype_userdeletefilter'] = 'Nutzerzyklus Filter';
$string['subplugintype_userdeletefilter_plural'] = 'Nutzerzyklus Filter';
$string['task_cleanup'] = 'Daten-Bereinigung';
$string['task_executeworkflows'] = 'Workflows ausführen';
$string['title'] = 'Titel';
$string['unnamed'] = 'Unbenannt';
$string['user_processes'] = 'Nutzerprozesse';
$string['users_to_delete'] = 'Zu löschende Nutzer';
$string['users_to_warn'] = 'Zu warnende Nutzer';
$string['view_user_processes'] = 'Nutzerprozesse anzeigen';
$string['warned'] = 'Gewarnt';
$string['workflow'] = 'Workflow';
$string['workflow_stat_processes_in_steps'] = '{$a->active} aktive ({$a->finished} abgeschlossene) Prozesse in {$a->steps} Schritt(en)';
$string['workflow_stat_processes_multiline'] = '{$a->active} aktiv<br>{$a->finished} abgeschlossen';
$string['workflows'] = 'Workflows';
$string['workflows_plugin_disabled_warning'] = 'Mindestens ein Workflow ist aktiv, aber das Plugin ist <b>global deaktiviert</b>. Aktivieren Sie es in den <a href="{$a}">Plugin-Einstellungen</a>, damit die Ausführung aufgenommen werden kann.';
