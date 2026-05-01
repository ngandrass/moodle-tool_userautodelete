# User Processes

Whenever a user is ingested into a workflow, a so-called user process is created for it. This process represents the
state of the user inside the workflow and tracks its progress through the sequential steps. The plugin automatically
updates user processes whenever users enter a workflow step or transition to the next step.

<div style="text-align: center;" markdown>
![User process list](../assets/screenshots/step_with_processes_list.png)
</div>

!!! info "In other words"
    User processes are the way users move through your workflows.


## Inspecting

You can find detailed information on how to view and inspect all current user processes in the
[inspecting user processes](../audit/userprocesses.md) section of this documentation:

[:fontawesome-solid-magnifying-glass: Inspecting User Processes](../audit/userprocesses.md){.md-button}


## Finishing

A user process is considered completed / finished when it reaches the last step of a workflow. Once a user process
transitions to the last step, all actions associated with that step are executed and the user process is then marked as
finished.

For workflows that consist of only one step, an active user process is created during ingestion into the workflow, the
respective actions of the step are executed, and the user process is then immediately marked as finished.


## Termination / Timeout

In multistep workflows, user processes usually have to wait for a certain amount of time or another condition to be
fulfilled in order to advance inside the workflow. In some scenarios it can be desirable for user processes to be
terminated without reaching the end of a workflow. For example, if a user gets send an inactivity warning via mail and
then becomes active again. In this case, the user process should not advance to the deletion step of the workflow but
should be terminated prematurely.

This plugin automatically terminates idle user processes based on the time a user process spent in a step. If a step has
a [time delay filter](../filters/delay.md), the configured delay time is used as a threshold for termination. If no
explicit delay is given, processes that stayed inside a single step for more than 7 days will be terminated
automatically.
