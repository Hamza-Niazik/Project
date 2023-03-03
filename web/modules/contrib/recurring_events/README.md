[![Build Status](https://travis-ci.org/owenbush/recurring_events.svg?branch=2.0.x)](https://travis-ci.org/owenbush/recurring_events)

Introduction
------------

The recurring events module offers the basic building blocks to build a
recurring events management system, and the APIs and integrations enable
developers and site builders to extend and enhance the core functionality.

### Module Hierarchy

-   `recurring_events` is the main/core module which contains all the base
    functionality for the module.
-   `recurring_events_registration` is a submodule which enables registrations.
-   `recurring_events_views` is a submodule which replaces all entity lists with
    views for flexibility.

### Data Modelling Concepts

There are several data models relating to the Recurring Events module.

1.  **Event Series**

    The `eventseries` custom entity type is used as a wrapper around a series of
    event instances. The `eventseries` entity holds all the data necessary to
    build out and schedule event instances on particular dates at set times. The
    eventseries entity also contains the title, and description for all the
    events in the series (unless they are overridden - see below).

2.  **Event Instances**

    The `eventinstance` custom entity type represents a single event occurrence.
    For every date/time that an event takes place on, there will be an
    associated `eventinstance`. This makes querying, calendar display and list
    displays much simpler as they are single entities - we do not have to do any
    special display-time logic to generate instances on the fly. Out-of-the-box,
    the `eventinstance` entity type is very basic - the date/time can be
    changed, and an additional description can be appended to the series
    description. The title, and main description, are inherited directly from
    the `eventseries`.

3.  **Event Series and Event Instance Types**

    The module ships with a single bundle, called `default`, for series and
    instances, and new bundles, or event types, can be created. Types can only
    be created for `eventseries` but when they are an equivalent type is created
    for the `eventinstance` entity. If
    the `recurring_events_registration` submodule is enabled, creating an
    `eventseries` type will also create an equivalent `registrant` type.

### Adding Events

When creating an event, what is really happening is an `eventseries` entity is
being created which will then automatically generate the
associated `eventinstance` entities for each occurrence. There is no way to
create an `eventinstance` outside of an `eventseries` entity. If more dates need
to be added, then the `eventseries` will need to be modified, or
an `eventinstance` will need to be cloned.

Creating an `eventseries` involves choosing what type of recurrence the event
will have. There are 3 options.

1.  **Consecutive Event**

    A `consecutive event` is an event that takes place multiple times during a
    day. Events are given a `duration` and an optional `buffer` between events.
    An example would be parent-teacher conferences at a school, where one
    teacher will see multiple parents in one day for 20 minutes, and have a
    10-minute break between conferences. In this example, there would be an
    `eventinstance` every 30 minutes, lasting 20-minutes with a 10-minute break
    afterward. 

2.  **Daily Event**

    A `daily event` is an event that takes place once per day. It is the
    equivalent of a `weekly event` which recurs every single day of the week. 

3.  **Weekly Event**

    A `weekly event` is an event that recurs based on the day of the week, every
    week. For example, if you wanted to run a Toddler Story Time every Monday at
    10am, you would set that up as a weekly event recurring on Mondays. With
    a `weekly event` you are asked to select a date range, between which the
    events will take place. This means that all Mondays between two particular
    dates will have an `eventinstance` created for this event. You will also
    enter a start time of the event, and an event duration. The system will then
    create event instances on every occurrence of the weekdays you specify,
    between the date range, starting at the time you specify and lasting for as
    long as the duration set. To achieve an `every day` or `daily event`, a user
    could create a `weekly event` that recurs on every day of the week.

4.  **Monthly Event**

    A `monthly recurring event` is one which recurs on a monthly basis, as
    opposed to weekly. There are 2 types of `monthly recurring event`.

    1.  **Weekday Occurrence**

        You can choose for an event to take place on
        the `first`, `second`, `third`, `fourth` or `last` weekday of a month.
        As an example you can have an event take place every first and third
        Monday and Friday of every month.

    2.  **Day of Month Occurrence**

        You can alternatively choose for an event to take place on a specific
        day of the month, for example the 6th of every month, or the last day of
        every month.

        **Note:** If the day of the month selected does not actually occur in
        any given month then no event will be scheduled that month. As an
        example, if you choose to run an event on the 31st of every month
        between January 1st and September 30th of a particular year, then no
        events would be scheduled in February, April, June or September because
        those months do not have a 31st day.

5.  **Custom Dates**

    A `custom dates eventseries` allows a user to create one or
    more `eventinstance` entities without a repeatable or structured recurrence
    pattern. Despite what the name may suggest, there can multiple occurrences
    of a `custom dates eventseries`. The difference between a `custom dates
    eventseries` and any other type of recurring event is that the dates and
    times are individual and do not recur. So, the event could take place at
    noon on a Wednesday in June, then a 10am on a Monday in August. There is no
    structure to how and when the events take place.

    When creating a `custom dates eventseries` you will be asked to specify a
    date and time for each individual occurrence of the event.

### Modifying and Deleting Events

Providing a user has the appropriate permissions to do so, events can be
modified or deleted after they have been created. When modifying an event, a
warning will be displayed to show that any changes made to date configurations
or recurring types will cause all `eventinstance` entities in
that `eventseries` to be deleted, and recreated. All other changes are
non-breaking changes and will not result in any instances being
removed. `eventinstance` entities inherit their title, and description from
the `eventseries`, so any changes made to those fields will cascade down to
all `eventinstances` in the `eventseries`.

Deleting an `eventseries` will also remove all the `eventinstance` entities in
that `eventseries`. This action cannot be undone.

### Modifying and Deleting Event Instances

Individual `eventinstance` entities can be modified or deleted providing the
user has the appropriate permissions to do so. When modifying
an `eventinstance`, no changes will be made upstream to the `eventseries` or the
other `eventinstance` entities in the series. This way an individual occurrence
of an event can be moved to a different day (in case of a booking conflict, or
public holiday/close day for example) or a different time to the rest of the
events in that series. Equally, an individual occurrence can be removed without
affecting any of the other instances in that series.

**Note:** If an `eventinstance` is removed, and then the `eventseries` is
modified, the removed `eventinstance` may be recreated if the date recurrence
configuration gets changed.

### Field Inheritance from Series to Instance

The `recurring_events` module has one contrib-space dependency for the core
module, which is the `field_inheritance` module which facilitates inheriting
field data from an `eventseries` into an `eventinstance`.

There are two fields which are automatically inherited from
the `eventseries` down to the `eventinstance`. All fields created using the
Field API may be inherited, providing there is a Field Inheritance Plugin
written for that particular field type.

Below are the field inheritance fields that come out-of-the-box with the module.

|Field|Strategy|Notes|
|:----|:-------|:----|
|Title|Inherit|The title for an event is always controlled by the series and cannot be changed on a per-instance basis. The field is inherited directly to the `eventinstance` entities within that series.|
|Body|Append|The body field from the `eventinstance` (if set) is appended to the end of the body field from the `eventseries` (if set).|

### Publishing Workflow and Revisions

Out-of-the-box the module supports Drupal core content moderation and workflow
modules, although the module does not depend on those modules being enabled.
Both `eventseries` and `eventinstance` entities support revisioning.

Revisioning and Content Moderation do play a conceptually interesting role when
it comes to `eventseries` entities due to the way that `eventinstance` entities
are automatically created, or recreated. If an event series is already
published, and has a number of `eventinstance` entities associated with it, then
if a user creates a new draft of the `eventseries` and that draft contains data
recurrence configuration changes, then we must not recreate
any `eventinstances` until that revision is published. The module handles this
concept as part of its core offering.

### Hooks, extensibility and APIs

All `eventseries` and `eventinstance` entities are compatible with the Drupal
core translation API.

The `recurring_events` module exposes its own hooks to use to modify core
functionality. These hooks are defined in `recurring_events.api.php`. Custom
modules can be written to modify, or enhance the core functionality
of `recurring_events` by making use of these hooks.

The `recurring_events` module also has a number of Field Inheritance plugins
written to handle core fields, and custom plugins can be creared providing they
implement the `FieldInheritance` annotation and extend
the `FieldInheritancePluginBase` class. The core plugins are defined
in `src/Plugin/FieldInheritance`.

### Dependencies

This module only relies on the following two core modules, and one contrib
module being enabled:

-   drupal:datetime\_range
-   drupal:options
-   field\_inheritance:field\_inheritance

### Similar Modules

The closest comparison would be the `date_recur` module, which adds a field type
which allows RRule compliant date recurrence configuration to be added. While
that module does a really great job, this module approaches things differently.
With `date_recur`, a content editor would have a single entity with a recurrence
field that builds instances of that event at display time.
With `recurring_events`, `eventinstances` are separate entities completely, and
therefore can be overridden or extended, without affecting the rest of the
series. This module also comes with a registration submodule, including the
ability to register either for an entire series, or individual events.
Using `date_recur` that would not be possible as there is only one entity.

### Getting Started

- Configure the Events Series and Event Instances at /admin/structure/events.
- Add an event entity by going to Content -> Events -> Add Event (/events/add). Note: If you already have an Events node type, there may be some route collisions, and you may now see two menu items with a title of "Events".  See the recurring_events.routing.yml for the existing routes.
- Note: If you try to add the newly created Event fields types (Consecutive Event, Daily Event, Monthly Event, Weekly Event) directly on a node/entity, you're doing it wrong and it will complain about missing plugin types.
