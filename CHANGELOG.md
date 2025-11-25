# Changelog

## Version 1.5.0 (2025112500)

- List accounts that were created but to which no user ever logged into with their "Time created" instead of "Last
  access" date on dry-run page
- Add informational text about differentiation of inactive and never-used accounts on the dry run page


## Version 1.4.1 (2025110600)

- Improve plugin config validation routine
- Display reason for failed plugin config validation on dry-run page
- Fix missing language string on dry-run page if plugin config is invalid


## Version 1.4.0 (2025101000)

- Add setting to limit user selection to users that are already suspended. This can be useful if your user lifecycle is
  externally managed (e.g., via SSO) or contains a suspension phase before the user is finally deleted.
- Ensure compatibility with Moodle 5.1
- Fix background color of Bootstrap badges on dry-run page with Moodle 5.1
- Add Moodle 5.1 and all supported PHP versions to CI test matrix
- Comply with Moodle coding style version 3.6


## Version 1.3.0 (2025100600)

- Allow to ignore users based on their authentication method (e.g., ignore all users authenticated via LDAP)
- Show ignored auth plugins on dry-run page
- Fix MoodleXML definition meta attributes for XML schema validation


## Version 1.2.1 (2025082500)

- Fix translations on log page (thanks to @jboulen)


## Version 1.2.0 (2025061100)

- Add action log that keeps track of sent user warnings, user deletions, and user recoveries
- Fix missing translation of next check info on dry-run page
- Improve language support for relative time specifications
- Provide testing instructions for developers


## Version 1.1.1 (2025052100)

- Add warning email state table to privacy provider


## Version 1.1.0 (2025052000)

- Add dry-run feature to show which users would be warned and deleted without actually performing the actions


## Version 1.0.1 (2025051700)

- Improve description of user record anonymization feature on plugin settings page


## Version 1.0.0 (2025051300)

- First stable release 🚀
- Automatically deleting inactive user accounts
- Sending warning emails to users before deletion
- GDPR-compliant deletion of user accounts
- Ignoring users with specific roles
- Configurable deletion and warning thresholds, email templates, enabling/disabling of single features, etc.
- Logging of deletion events
- Automated testing and support for all active Moodle releases
- Full English and German language support
- Documentation on installation, configuration, and usage
