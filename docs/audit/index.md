# Verification and Logging

This plugin includes built-in tools to verify workflow behavior before activation and to monitor automated activity once
workflows are enabled for automated processing.


## Dry-run

Use [dry-run mode](dryrun.md) before enabling a workflow. It shows which users would currently be targeted by the
workflow without executing any real action.

[:fontawesome-solid-flask: Dry-Run](dryrun.md){.md-button}


## User process inspection

Use [user process inspection](userprocesses.md) to see which users are currently being processes by a given workflow.
This is especially useful when tracing delays, step transitions, or long-running workflows.

[:fontawesome-solid-magnifying-glass: Inspecting User Processes](userprocesses.md){.md-button}


## Action log

Use the [action log](actionlog.md) to audit what the plugin has done over time and how many user were affected by which
workflows and actions. Action log entries are always written whenever any action is executed during workflow processing.

[:fontawesome-solid-clipboard-list: Action Logs](actionlog.md){.md-button}
