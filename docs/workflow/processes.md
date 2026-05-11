# User Processes

Whenever a user is ingested into a workflow, a so-called user process is created for it. This process represents the
state of the user inside the workflow and tracks its progress through the sequential steps. The plugin automatically
updates user processes whenever users enter a workflow step or transition to the next step.

<div style="text-align: center;" markdown>
![User process list](../assets/screenshots/step_with_processes_list.png)
</div>

!!! info "In other words"
    User processes are the way users move through your workflows.


## Lifecycle

You can find extensive details on how user processes are created, updated, and terminated in the
[execution model section](execution.md) of this documentation:

[:fontawesome-solid-arrow-down-1-9: Execution Model](execution.md){.md-button}


## Inspecting

You can find detailed information on how to view and inspect all current user processes in the
[inspecting user processes](../audit/userprocesses.md) section of this documentation:

[:fontawesome-solid-magnifying-glass: Inspecting User Processes](../audit/userprocesses.md){.md-button}
