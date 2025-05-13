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
 * @copyright   2025 Niels Gandraß <niels@gandrass.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreFile

defined('MOODLE_INTERNAL') || die(); // @codeCoverageIgnore

// General.
$string['pluginname'] = 'Automatische Nutzerlöschung';
$string['privacy:metadata'] = 'Dieses Plugin speichert keine personenbezogenen Daten.';
$string['task_check_and_delete_users'] = 'Inaktive Nutzer suchen und löschen';

// Task execution.
$string['plugin_disabled_skipping_execution'] = 'Das Plugin ist global deaktiviert, überspringe Ausführung.';
$string['warning_email_disabled_skipping'] = 'Warn-E-Mails sind deaktiviert, überspringe ...';
$string['warning_email_sent_to_user'] = 'Warn-E-Mail an Nutzer mit ID {$a} gesendet';
$string['delete_email_sent_to_user'] = 'Löschungsbenachrichtigung an Nutzer mit ID {$a} gesendet';
$string['user_anonymized'] = 'Nutzer mit ID {$a} wurde anonymisiert';
$string['user_deleted'] = 'Nutzer mit ID {$a} wurde gelöscht';
$string['user_recovered'] = 'Nutzer mit ID {$a} war zuvor als inaktiv markiert, ist aber zurückgekehrt. Nutzer wird nicht gelöscht.';
$string['users_to_warn_a'] = '{$a} Nutzer für den Versand einer Warn-E-Mail gefunden.';
$string['no_users_to_warn'] = 'Keine Nutzer für den Versand einer Warn-E-Mail gefunden.';
$string['users_to_delete_a'] = '{$a} Nutzer zur Löschung gefunden.';
$string['no_users_to_delete'] = 'Keine Nutzer zur Löschung gefunden.';

// Admin settings.
$string['setting_plugin_desc'] = 'Dieses Plugin löscht automatisch Nutzer die sich für eine konfigurierbare Anzahl von Tagen nicht eingeloggt haben. Dies ist nützlich, um die Moodle-Datenbank sauber zu halten und inaktive Nutzerkonten zu entfernen. Das Plugin kann so konfiguriert werden, dass inaktive Nutzer einige Tage vor der Kontolöschung eine Warn-E-Mail erhalten. Dies gibt Nutzern die Möglichkeit, sich erneut einzuloggen und ihre Konten zu behalten. Das Plugin unterstützt außerdem das Löschen von Nutzern auf DSGVO-konforme Weise, sodass keine personenbezogene Daten in der Datenbank verbleiben.';
$string['setting_enable'] = 'Plugin aktivieren';
$string['setting_enable_desc'] = 'Aktiviert oder deaktiviert das Plugin global. Wenn dies deaktiviert ist, werden keine Aktionen ausgeführt.';
$string['setting_ignore_siteadmins'] = 'Administratoren ignorieren';
$string['setting_ignore_siteadmins_desc'] = 'Globale Administratoren können nicht gelöscht werden. Entfernen Sie die Administratorrolle, um sie in den automatischen Löschprozess einzubeziehen.';
$string['setting_ignore_roles'] = 'Ignorierte Rollen';
$string['setting_ignore_roles_desc'] = 'Alle Nutzer die mindestens einer der ausgewählten Rollen zugewiesen sind werden niemals gelöscht.';
$string['setting_task_execution_interval'] = 'Prüfintervall';
$string['setting_task_execution_interval_desc'] = 'Die Überprüfung auf inaktive Nutzer wird über einen geplante Task durchgeführt, der über den Moodle-Cron ausgeführt wird. Sie können das Intervall, in dem dieser Task ausgeführt wird, über den folgenden Button konfigurieren.';
$string['setting_task_execution_interval_button'] = 'Prüfintervall konfigurieren';
$string['setting_task_logs'] = 'Logdaten';
$string['setting_task_logs_desc'] = 'Dieses Plugin protokolliert alle Aktionen die es ausführt. Sie können die Protokolle aller vergangenen Task-Ausführungen über den folgenden Button einsehen.';
$string['setting_task_logs_button'] = 'Logdaten anzeigen';

$string['setting_header_user_deletion'] = 'Nutzerlöschung';
$string['setting_header_user_deletion_desc'] = 'Konfiguration der automatischen Nutzerlöschung. Nutzer, die sich für mindestens die konfigurierte Anzahl von Tagen nicht eingeloggt haben und nicht durch die obigen Einstellungen ausgeschlossen sind, werden automatisch gelöscht.';
$string['setting_delete_threshold_days'] = 'Löschschwelle';
$string['setting_delete_threshold_days_desc'] = 'Die Anzahl der Tage seit dem letzten Login, nach denen ein Nutzer gelöscht wird. Die Löschung erfolgt nur, wenn sich der Nutzer für diese Anzahl von Tagen nicht eingeloggt hat.';
$string['setting_anonymize_user_data'] = 'Gelöschte Nutzer anonymisieren';
$string['setting_anonymize_user_data_desc'] = 'Beim Löschen eines Nutzers behält Moodle Teile des Nutzerkontos in seiner Datenbank, einschließlich Vorname, Nachname, E-Mail-Adresse und anderer sensibler Informationen. Dies steht im Widerspruch zur Datenschutz-Grundverordnung (DSGVO / GDPR) der EU. Wenn diese Einstellung aktiviert ist, werden alle verbleibenden Nutzerdaten anonymisiert, wodurch die Möglichkeit, ein gelöschtes Nutzerkonto dem vorherigen Besitzer zuzuordnen, vollständig eliminiert wird.';
$string['setting_delete_email_enable'] = 'Löschbenachrichtigungen';
$string['setting_delete_email_enable_desc'] = 'Wenn aktiviert, erhalten Nutzer direkt vor der Kontolöschung eine abschließende E-Mail und werden anschließend sofort gelöscht. Dies kann verwendet werden, um Bestätigungen der Nutzerlöschung zu senden.';
$string['setting_delete_email_subject'] = 'Betreff';
$string['setting_delete_email_subject_desc'] = 'Der Betreff der Löschbenachrichtigungs-E-Mail.';
$string['setting_delete_email_subject_default'] = 'Ihr Konto wurde gelöscht';
$string['setting_delete_email_body'] = 'Inhalt';
$string['setting_delete_email_body_desc'] = 'Der Inhalt der Löschbenachrichtigungs-E-Mail.';
$string['setting_delete_email_body_default'] = '<p>Hallo,</p><p>Ihr Konto auf unserer Seite wurde aufgrund von Inaktivität gelöscht. Wenn Sie unseren Dienst weiterhin nutzen möchten, erstellen Sie sich bitte ein neues Konto.</p><p>Mit freundlichen Grüßen</p>';

$string['setting_header_deletion_warning'] = 'Löschwarnungen';
$string['setting_header_deletion_warning_desc'] = 'Konfiguration der Löschwarnung-E-Mails. Warnungs-E-Mails werden einige Tage vor der inaktivitätsbedingten Löschung eines Nutzers gesendet. Diese informieren Nutzer darüber, dass ihr Konto bald gelöscht wird, und gibt ihnen Zeit, sich einzuloggen um ihre Konten vor der Löschung zu bewahren.';
$string['setting_warning_email_enable'] = 'Löschwarnungen';
$string['setting_warning_email_enable_desc'] = 'Wenn aktiviert, erhalten Nutzer eine Anzahl an Tagen vor ihrer Löschung eine Warn-E-Mail.';
$string['setting_warning_threshold_days'] = 'Warnschwelle';
$string['setting_warning_threshold_days_desc'] = 'Die Anzahl der Tage vor der Löschung des Nutzers, an denen die Löschwarnung per E-Mail gesendet wird. Dies ist relativ zur Löschschwelle.';
$string['setting_warning_email_subject'] = 'Betreff';
$string['setting_warning_email_subject_desc'] = 'Der Betreff der Warn-E-Mail.';
$string['setting_warning_email_subject_default'] = 'Ihr Konto wird bald gelöscht - Handlung erforderlich!';
$string['setting_warning_email_body'] = 'Inhalt';
$string['setting_warning_email_body_desc'] = 'Der Inhalt der Warn-E-Mail.';
$string['setting_warning_email_body_default'] = '<p>Hallo,</p><p>Ihr Konto auf unserer Seite war für einen langen Zeitraum inaktiv. Um Ihr Konto zu behalten, <strong>loggen Sie sich jetzt ein, um zu verhindern, dass Ihr Konto gemäß unserer Datenschutzrichtlinie in den nächsten Tagen gelöscht wird</strong>.</p><p>Wenn Sie möchten, dass Ihr Konto gelöscht wird, können Sie diese Nachricht ignorieren.</p><p>Mit freundlichen Grüßen</p>';

// Errors.
$string['error_invalid_config_aborting'] = 'Ungültige Plugin-Konfiguration gefunden. Abbruch ...';
$string['error_invalid_role_id'] = 'Ungültige Rollen-ID {$a} in der Liste der ignorierten Rollen gefunden. Dies ist höchstwahrscheinlich ein Fehler im Plugin. Bitte melden Sie dies dem Plugin-Maintainer.';
$string['error_delete_threshold_days_negative'] = 'Die Löschschwelle muss größer als 0 sein.';
$string['error_warning_threshold_days_negative'] = 'Die Warnschwelle muss größer als 0 sein.';
$string['error_warning_threshold_days_geq_delete'] = 'Die Warnschwelle muss kleiner als die Löschschwelle sein.';
$string['error_warning_email_subject_empty'] = 'Der Betreff der Warn-E-Mail darf nicht leer sein.';
$string['error_warning_email_body_empty'] = 'Der Inhalt der Warn-E-Mail ist leer.';
$string['error_delete_email_subject_empty'] = 'Der Betreff der Löschungs-E-Mail darf nicht leer sein.';
$string['error_delete_email_body_empty'] = 'Der Inhalt der Löschungs-E-Mail ist leer.';
$string['error_sending_warning_mail_to_user'] = 'Senden der Warn-E-Mail an Nutzer mit ID {$a} fehlgeschlagen.';
$string['error_sending_delete_mail_to_user'] = 'Senden der Löschbenachrichtigung an Nutzer mit ID {$a} fehlgeschlagen.';
$string['error_anonymizing_user'] = 'Anonymisieren des Nutzers mit ID {$a} fehlgeschlagen.';
$string['error_deleting_user'] = 'Löschen des Nutzers mit ID {$a} fehlgeschlagen.';
