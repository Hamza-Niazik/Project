The Flexible permissions module allows you to gather, calculate and cache
permissions from a myriad of sources. By virtue of a centralized permission
checker service, it enables you to turn your website's access control layer
into a Policy Based Access Control (PBAC).

For instance, you could have no editors be allowed to edit any content by
default, but have a permission calculator that adds these permissions during
office hours. The cache (built using VariationCache) would be smart enough to
recognize this and serve the user a more permissive set of permissions during
office hours, allowing them to edit content.

Right now, the module needs to be implemented by any access defining module
such as Group, Domain, Commerce Stores, etc. The Group module already relies
on this module as of version 2.0.0.

Scope based permissions

You can define permissions for each scope. Scopes are a way to determine where
the permissions should be checked to give access. Your regular Drupal
permissions could be regarded as being in the "default" scope, but each
implementation is free to add their own scopes.

As an example: The Group module does not check for regular Drupal permissions.
Instead, it has its own permission layer where access is checked versus group
types when you are or aren't a member (group_outsider and group_insider scopes)
and against individual memberships (group_individual).

The Domain module would be able to define permissions specifically for certain
domains and Commerce would be able to do the same on a per-store basis.

Core integration

Sadly, core still has permission checks littered all over the place rather than
in a central service. The silver lining being that most permission checks use
AccountInterface::hasPermission(). If we are somehow able to swap out these
classes, then we could polyfill this system in core and make full PBAC easy to
achieve.

There is currently no budget to investigate this avenue, but any sponsorships
can be directed at hello@factorial.io and we can look into making the
permission layer in core as awesome as this module makes it for contrib.

To give you an example of how powerful this module can be: Because of the
centralized permission calculator and checker, we have a cache context in Group
that varies pages by your group permissions, just like the user.permissions
cache context in core. However, in Group, you can alter someone's permissions
without breaking said cache context. In core this is simply not possible
because the permission layer is not centralized. Altering someone's core
permissions would currently break the user.permissions cache context and lead
to security issues.
