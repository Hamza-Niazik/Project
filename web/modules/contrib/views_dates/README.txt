== Views dates ==

This module provides different filters and arguments for date fields within Views.

Using this, you can include a filter on a date field for 'any day this week' without having to specify what 'this week' means in absolute terms.

You can also use it with Views Arguments to select dates by named periods, such as a year (2021), a year-and-month (202105), a year-and-week (202144) or a year-month-day (20210315). The particular pattern to be used must be specified in advance.

The new options are exposed under the Filter Add dialog of views, which now shows additional entries for date and timestamp fields.

Such filters can of course be exposed, and results in a multiple-choice select list:

The module's code is implemented as Views Plugins, and the pattern makes it easy to extend.
