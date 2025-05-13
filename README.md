# Automatic User Deletion

This plugin automatically deletes users that have not logged in for a configurable number of days.

Automatically deleting users is useful to keep your database clean and remove old accounts that are no longer needed.
The plugin can be configured to send warning emails a number of days before the user is deleted. This gives users the
chance to log back in and keep their accounts active. It furthermore supports deleting users in a GDPR-compliant way,
leaving no trace of personally identifiable information (PII) inside the user record.


## Features

- Automatically deleting inactive user accounts
- Sending warning emails to users before deletion
- GDPR-compliant deletion of user accounts
- Ignoring users with specific roles
- Highly configurable (deletion and warning thresholds, email templates, enabling/disabling of single features, etc.)
- Logging of deletion events


## Configuration and Usage

The plugin can be configured via the Moodle site administration under _Site administration > Plugins > Admin tools >
Automatic user deletion_. After installation, the plugin will be disabled by default until you configure and enable it.

### TODO


## Installation

This plugin can be installed like any other Moodle plugin by placing its source code inside your Moodle installation and
executing the upgrade routine afterward.

### Installing via the site administration (uploaded ZIP file)

1. Download the latest release of this plugin.
2. Log in to your Moodle site as an admin and go to _Site administration > Plugins > Install plugins_.
3. Upload the ZIP file with the plugin code.
4. Check the plugin validation report and finish the installation.

### Installing manually

The plugin can be also installed by putting the contents of this directory into

```
{your/moodle/dirroot}/admin/tool/userautodelete
```

Afterwards, log in to your Moodle site as an admin and go to _Site administration > Notifications_ to complete the
installation.

Alternatively, you can run `php admin/cli/upgrade.php` from the command line to complete the installation.


## License

2025 Niels Gandraß <niels@gandrass.de>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
