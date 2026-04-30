# Actions

Actions are used to perform certain operations on users that enter a [workflow step](../workflow/steps.md). Actions are
always executed at the moment a user enters the workflow step the action is part of. 

Like [filters](../filters/index.md), actions are implemented as subplugins and can therefore be easily extended by
installing further action subplugins or creating your own institution-specific actions. The following actions are part
of the core plugin and therefore available within every installation:


---

<div class="subplugin-grid" markdown>
[:fontawesome-solid-user-secret:<br>Anonymize User](anonymize.md){.md-button .md-button-subplugin .md-button-subplugin-action}

[:fontawesome-solid-trash:<br>Delete User](delete.md){.md-button .md-button-subplugin .md-button-subplugin-action}

[:fontawesome-solid-envelope:<br>Send Mail](mail.md){.md-button .md-button-subplugin .md-button-subplugin-action}

[:fontawesome-regular-circle-pause:<br>Suspend User](suspend.md){.md-button .md-button-subplugin .md-button-subplugin-action}

[:fontawesome-regular-circle-play:<br>Unsuspend User](unsuspend.md){.md-button .md-button-subplugin .md-button-subplugin-action}
</div>

---
