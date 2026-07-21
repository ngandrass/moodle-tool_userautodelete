# Changelog

## Version X.Y.Z (YYYYMMDDNN)

- Display account creation date on dry-run page if a user never logged in (thanks to @35grain !)
- Add support for AJAX-backed autocomplete elements in instance settings form
- Cohort filter: Dynamically fetch available cohorts to prevent long loading times on Moodle instances with thousands of cohorts


## Version 2.2.0 (2026051800)

- Add help button to instance settings form that allows quick access to the online documentation for the respective actions and filters 
- Include users that historically have been part of a workflow on the dry-run page
- Force password to `AUTH_PASSWORD_CACHED` during anonymization to prevent potentially unwanted side effects in auth plugins
- Improve German translation of auth filter settings


## Version 2.1.0 (2026051200)

- Prevent dry-run if a workflow currently contains errors
- Fix user filter query parameter prefixing if using multiple identically named parameters inside a single workflow step
- Fix user filter query parameter prefixing for future plugins that might re-use the same parameter name in a single query
- Terminate existing users sessions during suspension action
- Update users `timemodified` value during anonymization action
- Add session checks to workflow management endpoints to prevent admin CSRF
- Align `get_step_user_processes` web service function default values with descriptors
- Fall back to display role ID instead of role name if a role was deleted after being selected for a role filter
- Fix typos in workflow execution task log messages
- Exclude custom docs CSS file from Moodle CI stylelint to prevent prechecks from unrelated failures


## Version 2.0.0 (2026051100)

This is a full rewrite of the existing plugin, transforming it from a flexible but limited automatic user deletion tool into a fully-fledged user lifecycle workflow engine. All original features are still available but now embedded into a powerful framework that allows you to define your own multistep workflows and can easily be extended.

Check out our new [online documentation](https://moodleuserlifecycle.gandrass.de/) for more information about the new plugin features as well as screenshots and examples.

Short summary of the changes:

- Introduce a new workflow-based user lifecycle engine that replaces the pre-defined steps.
    - Workflows consist of one or more steps that are executed sequentially.
    - Each step can have one or more filters that determine which users are selected for the step and one or more actions that are executed for each selected user.
    - Multiple workflows can coexist, targeting different user groups and performing different actions.
- Create fully-featured UI for editing workflows, steps, filters, and actions directly via the Moodle admin page. 
- Add user filter subplugins for effectively targeting users. Available core filters:
    - Authentication method
    - Cohort membership
    - Date
    - Last access to site
    - Role assignment
    - Suspension state
    - Time delay
- Add action subplugins to perform various operations. Available core actions:
    - Anonymize user account (GDPR compliance)
    - Delete user
    - Send mail to user
    - Suspend user
    - Unsuspend user
- Allow use of dynamic variables (e.g., user first and last names) in email templates.
- Enable insight into which users are currently being processed by which workflow and step.
- Create a complete and filterable action log to audit workflow operations.
- Write a comprehensive [online documentation](https://moodleuserlifecycle.gandrass.de/) for users, administrators, and developers.

**ATTENTION**: This release drops support for Moodle <= 4.4 and PHP <= 8.0. Since this version, **Moodle >= 4.5 (LTS) and PHP >= 8.1 are required**.

### Migrating from v1 to v2

If you are migrating from an existing v1 installation to v2, everything will be migrated automatically for you ❤️ If you instead wish to start from scratch simply disable and delete the migrated workflow and create a new one.

This means:

- A new workflow that matches your existing configuration will be created.
- All configured filter rules (e.g., inactivity threshold, authentication method) will be translated to the new filters including all their settings.
- All configured actions (e.g., sending warning emails, anonymizing users) will be translated to the new actions including all their settings.
- All existing action log entries will be migrated.
- If warning mails were enabled, all users that did already receive a warning mail but are still within their grace period will, be migrated to the new workflow and keep their grace period.
- If the plugin was previously enabled, the new workflow will likewise be enabled.
- If the plugin was previously disabled, the new workflow will be created but remain disabled. You can enable it whenever you like.

Even though the migration process was tested with utmost care, we still recommend to if the migrated workflow is as expected.


## Version 1.6.0 (2026040100)

- Ensure Moodle 5.2 compatibility


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
