# Filter subplugins

Filters are used to select the Moodle users that are eligible to enter or transition between
[workflow steps](../workflow/steps.md). Each filter contributes a SQL `WHERE` clause fragment that is combined with
all other active filters via a logical `AND` to produce the final user selection query.

Filters are implemented as small subplugins and can therefore be easily extended by your own institution-specific filters.

## Overview

!!! info "Overview reduced for clarity"
    For clarity, the following overview diagram is reduced to the most important classes and members. Therefore, some
    details like methods, parameters, or members are omitted. Please refer to the {{ source_file('', 'plugin source code') }}
    for a complete reference.

<div style="text-align: center;" markdown>

```mermaid
classDiagram
    direction TB

    %% Main classes
    class step_subplugin {
        <<abstract>>
        #id: int
        #step: step

        +get_instance_id()$ int
        +get_step() step
        +get_instance_title() string
        +get_instance_details() string
        +is_valid() bool
    }

    class userdeletefilter {
        <<abstract>>

        +get_instance_by_id(instanceid: int)$ self
        +create_instance(step: step, pluginname: string, settings: array)$ self
        +delete() void
        +get_plugin_type()$ subplugin_type
        +get_icon_class()$ string
    }

    class userdeletefilter_myfoo {
        +get_plugin_name()$ string
        +instance_setting_descriptors()$ array
        +user_records_filter_clause() userfilter_clause
    }

    %% Supporting classes
    class subplugin_instance_settings {
        <<trait>>

        +get_all_instance_settings() array
        +get_instance_setting(key: string) mixed
        +set_instance_setting(key: string, value: mixed) void
        +load_default_instance_settings(overrides: array) void
        +validate_instance_settings_data(data: array) array
    }

    class userfilter_clause {
        +sql: string
        +params: array
    }

    %% Relationships
    userdeletefilter_myfoo --|> userdeletefilter
    userdeletefilter_myfoo --> userfilter_clause
    userdeletefilter --|> step_subplugin
    step_subplugin ..> subplugin_instance_settings

    %% Style
    style step fill:#dedede,stroke:#666666
    style workflow fill:#dedede,stroke:#666666
    style userfilter_clause fill:#dedede,stroke:#666666
    style subplugin_instance_settings fill:#dedede,stroke:#666666
    style step_subplugin fill:#dedede,stroke:#666666
    style userdeletefilter fill:#dedede,stroke:#666666
```

</div>


## Implementation

All filter subplugins must use the `userdeletefilter` frankenstyle plugin type and extend the abstract
{{ source_file('classes/userdeletefilter.php', '\\tool_userautodelete\\userdeletefilter') }} base class.

Any filter subplugin must implement the following methods:

1. {{ source_file('classes/step_subplugin.php', 'get_plugin_name(): string') }}
2. {{ source_file('classes/userdeletefilter.php', 'user_records_filter_clause(): userfilter_clause') }}
3. {{ source_file('classes/local/trait/subplugin_instance_settings.php', 'instance_setting_descriptors(): array') }}
   (see also: [instance settings](instancesettings.md))

The `user_records_filter_clause()` method must return a
{{ source_file('classes/local/type/userfilter_clause.php', 'userfilter_clause') }} object that contains
a SQL `WHERE` clause fragment and the associated named parameters. All references to user table columns
**must** use the `u` table alias (e.g., `u.lastaccess`). All active filter clauses are joined with a SQL
`AND` operator at the time of evaluation.

You do not have to prefix your SQL parameter names in any way, as the core plugin will automatically
prefix them uniquely for you at the time of evaluation.

Of course you can also override other methods like {{ source_file('classes/step_subplugin.php',
'get_instance_details(): string') }} or {{ source_file('classes/userdeletefilter.php',
'get_icon_class(): string') }} to further customize the behavior of your filter subplugin and how it
displays within the UI.

!!! warning "PHPDocs are the ground source of truth"
    Please refer to the PHPDocs in the source code as the ground source of truth for detailed
    information regarding the implementation of these methods and their expected behavior.

!!! example "Example filter subplugin implementations"
    You can find many examples of filter subplugins directly within {{ source_file('filter/') }}.
