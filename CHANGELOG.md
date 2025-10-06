# Changelog

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
