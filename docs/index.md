# Automatic User Lifecycle Management

<div style="margin-top: -30px;" markdown>

[![Latest Version](https://img.shields.io/github/v/release/ngandrass/moodle-tool_userautodelete)](https://github.com/ngandrass/moodle-tool_userautodelete/releases)
[![PHP Support](https://img.shields.io/badge/dynamic/regex?url=https%3A%2F%2Fraw.githubusercontent.com%2Fngandrass%2Fmoodle-tool_userautodelete%2Frefs%2Fheads%2Fmaster%2Fversion.php&search=meta-supported-php%7B(%3F%3Cdata%3E%5B%5E%7D%5D%2B)%7D&replace=%24%3Cdata%3E&label=PHP&color=blue)](https://github.com/ngandrass/moodle-tool_userautodelete/blob/master/version.php)
[![Moodle Support](https://img.shields.io/badge/dynamic/regex?url=https%3A%2F%2Fraw.githubusercontent.com%2Fngandrass%2Fmoodle-tool_userautodelete%2Frefs%2Fheads%2Fmaster%2Fversion.php&search=meta-supported-moodle%7B(%3F%3Cdata%3E%5B%5E%7D%5D%2B)%7D&replace=%24%3Cdata%3E&label=Moodle&color=orange)](https://github.com/ngandrass/moodle-tool_userautodelete/blob/master/version.php)
[![GitHub Workflow Status: Moodle Plugin CI](https://img.shields.io/github/actions/workflow/status/ngandrass/moodle-tool_userautodelete/moodle-plugin-ci.yml?label=Moodle%20Plugin%20CI)](https://github.com/ngandrass/moodle-tool_userautodelete/actions/workflows/moodle-plugin-ci.yml)
[![Code Coverage](https://img.shields.io/coverallsCoverage/github/ngandrass/moodle-tool_userautodelete)](https://coveralls.io/github/ngandrass/moodle-tool_userautodelete)
[![GitHub Issues](https://img.shields.io/github/issues/ngandrass/moodle-tool_userautodelete)](https://github.com/ngandrass/moodle-tool_userautodelete/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/ngandrass/moodle-tool_userautodelete)](https://github.com/ngandrass/moodle-tool_userautodelete/pulls)
[![Maintenance Status](https://img.shields.io/maintenance/yes/9999)](https://github.com/ngandrass/moodle-tool_userautodelete/)
[![License](https://img.shields.io/github/license/ngandrass/moodle-tool_userautodelete)](https://github.com/ngandrass/moodle-tool_userautodelete/blob/master/LICENSE)
[![Donate with PayPal](https://img.shields.io/badge/PayPal-donate-d85fa0)](https://www.paypal.me/ngandrass)
[![Sponsor with GitHub](https://img.shields.io/badge/GitHub-sponsor-d85fa0)](https://github.com/sponsors/ngandrass)
[![GitHub Stars](https://img.shields.io/github/stars/ngandrass/moodle-tool_userautodelete?style=social)](https://github.com/ngandrass/moodle-tool_userautodelete/stargazers)
[![GitHub Forks](https://img.shields.io/github/forks/ngandrass/moodle-tool_userautodelete?style=social)](https://github.com/ngandrass/moodle-tool_userautodelete/network/members)
[![GitHub Contributors](https://img.shields.io/github/contributors/ngandrass/moodle-tool_userautodelete?style=social)](https://github.com/ngandrass/moodle-tool_userautodelete/graphs/contributors)

</div>

This plugin manages the full lifecycle of Moodle user accounts using freely configurable, multistep workflows.

Each workflow can consist of multiple steps with one or more filters that determine which users are selected, and
actions that are executed when a user enters a step. This allows building simple as well as sophisticated user lifecycle
workflows, i.e., warn inactive users, suspend them after a grace period, and finally delete their accounts in a
GDPR-compliant way. A built-in dry-run mode and action log let administrators safely preview and audit all automated
activity. The default filters and actions can easily be extended by further subplugins.

--- 
<div style="text-align: center;" markdown>
[:material-rocket-launch: Getting Started](getstarted/index.md){.md-button}
&nbsp;&nbsp;&nbsp;
[:material-image: Screenshots](screenshots.md){.md-button}
&nbsp;&nbsp;&nbsp;
[:material-file-document-edit-outline: Changelog](changelog.md){.md-button}
</div>
---

The automatic user lifecycle management plugin is available via the [Moodle plugin directory](https://marketplace.moodle.com/plugins/tool_userautodelete):

<div style="text-align: center;" markdown>
[![Moodle plugin directory](/assets/buttons/moodle-plugin-directory-button.png)](https://marketplace.moodle.com/plugins/tool_userautodelete)
</div>


## Features

- Workflow-based user lifecycle management with freely configurable, multi-step workflows
- User filter subplugins for effectively targeting users. See [Filters](filters/index.md) for details.
- Action subplugins to perform various operations. See [Actions](actions/index.md) for details.
- GDPR-compliant user account anonymization and deletion
- Use of dynamic variables (e.g., user first and last names) in email templates
- Dry-run mode to safely preview which users would be affected without taking any action
- Action log to audit all sent mails, issued suspensions, and performed deletions
- Highly configurable (time thresholds, email templates, ...)
- Automated testing and support for all active Moodle releases


## Contributing and Issues

You have found a bug or want to request a new feature? Please head over to the
[issue tracker](https://github.com/ngandrass/moodle-tool_userautodelete/issues).

Want to contribute? Awesome! Please check out the [developer documentation](dev/index.md) and feel free to submit a pull
request.

[:material-bug: Issue Tracker](https://github.com/ngandrass/moodle-tool_userautodelete/issues){.md-button}
&nbsp;&nbsp;&nbsp;
[:material-code-tags: Developer Docs](dev/index.md){.md-button}


## License

This work is licensed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.en.html).
