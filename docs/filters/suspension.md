# Filter: Suspension State

The suspension state filter allows you to select users based on whether their account is currently suspended or active.
This can be useful if your users get suspended by an external sync task (i.e. LDAP sync) and you want to
automatically delete them after a specified period of time.

<div class="subplugin-grid" markdown>
[:fontawesome-solid-user-slash:<br>Suspension State](#){.md-button .md-button-subplugin .md-button-subplugin-filter .md-button-disabled}
</div>


## Settings

!!! setting "Suspension state"
    Select whether this filter should match suspended users or active (not suspended) users.

    If set to yes, only suspended users will be affected.
    If set to no, only active users will be affected.


## Example

![Example screenshot of the instance settings for the suspension state filter](../assets/screenshots/userdeletefilter_suspension_example.png)
