Introduction
------------

The Recurring Events Registration module is a submodule of `recurring_events`.
It provides a registration system designed to be site agnostic and extensible.

### Data Modeling Concepts

The `registrant` custom entity type represents a single person who has
registered for an `eventseries` or `eventinstance` - depending on the
registration configuration for the `eventseries`. The `registrant` entity type
is only available when the `recurring_events_registration` sub-module is
enabled. The entity can be bundled, but bundles are controlled by the series.

The `registrant` entity type has one base field - `email`, because email is so
integral to most registration systems. The entity is also fieldable and the
module ships with some installation configuration to add first name, last name
and phone number. These are added using the Fields API, so they can be removed
or edited as needed.

Registrants are not revisionable or translatable.

If an `eventseries` is set to have registrations enabled, then a number of
things are checked:

1.  What is the capacity of the event?
2.  How many users are already registered?
3.  Is there a waiting list?

The answer to those questions dictates what experience a user has when they try
to register for an event. If registration is enabled and there is capacity then
the user will be able to register for the event one at a time. If there is no
capacity left, but there is a waiting list the verbiage will change a little and
the user will be added to a waitlist instead. If there is no capacity and no
waitlist, then the user cannot register.

There are several settings related to registration for events:

1.  Registration type

    There are two registration types:

    1.  Series Registration - when this mode is used users register for all
        instances in a series with one registration. This is helpful should a
        user need to attend all instances in a series, for example if the series
        was a set of six computer classes.
    2.  Instance Registration - when this mode is used users register for
        individual instances in a series. This is helpful if a user does not
        need to attend every event, for example if the series is a recurring
        story time at a library with limited capacity.

2.  Registration Dates

    A user creating an event series can specify when registration is allowed for
    an event series or instance. There are two options:

    1.  Open Registration - when this mode is enabled then registration for an
        event instance is available from the moment the instance is published,
        until the moment the instance begins. For series registration users are
        able to register from the moment the instances are published until the
        moment the first event in the series begins.
    2.  Scheduled Registration - when registering for an entire series, users
        are able to specify the date and time that event registration opens and
        closes. For individual instance registration a user can specify how many
        days or hours prior to the instance start date that registration opens -
        in this case, registration closes when the event instance begins.

3.  Capacity

    Users can specify how many registrants can attend an event. If event series
    registration is enabled, then the capacity applies across all instances. If
    individual event instance registration is enabled, then each instance will
    have the same capacity.

4.  Waitlist

    Users can specify whether an event has a waitlist. If event series
    registration is enabled, then the waitlist applies across all instances. If
    individual event instance registration is enabled, then each instance will
    have its own waitlist. When an event is full users will automatically get
    added to the waitlist if enabled. If a spot opens up on the registration
    list because someone deleted their registration, then the first person on
    the waitlist is automatically promoted to the registration list.

Users with appropriate permissions will be allowed to view the list of
registrants for an event and modify or delete them.

`Authenticated users` are able to modify and delete their own registrations
through their account page via a new `Registrations` tab in the user profile.

`Anonymous users` can optionally receive an email to the email address provided,
with a unique URL to edit/cancel their registration. The URL will contain a UUID
value to make them difficult to guess and therefore reduce the risk of gaming
the system. An `anonymous user` will be unable to view a list of their
registrations anywhere because they do not have an account page. Instead, they
will manage their registrations purely through emails and links within those
emails.

### Registration Emails

The ability to enable/disable individual registration emails or modify their
subject/body fields is built in to the core of the module. This gives users lots
of flexibility when it comes to messaging registrants. There are a number of
tokens available to administrators to use in the emails.

Out-of-the-box the following registrant emails are available

|Email|Notes|
|:----|:----|
|Registration|Send an email to a registrant to confirm they were registered for an event|
|Waitlist|Send an email to a registrant to confirm they were added to the waitlist|
|Promotion|Send an email to a registrant to confirm they were promoted from the waitlist|
|Instance Deletion|Send an email to a registrant to confirm an instance deletion|
|Series Deletion|Send an email to a registrant to confirm a series deletion|
|Instance Modification|Send an email to a registrant to confirm an instance modification|
|Series Modification|Send an email to a registrant to confirm a series modification|

More can be added using the Registrant API by implementing
the `hook_recurring_events_registration_notification_types_alter` hook.

Users with appropriate permissions are also able to resend emails to registrants
in the case that an email was not delivered. This is available from the
registrant list pages.

Users with appropriate permissions can also contact all registrants of an event
through a form where they can specify the subject and body of the email, again
with tokens available. Users can specify whether to contact just registrants,
just waitlisted users, or both.

### Hooks, extensibility and APIs

The Recurring Events Registration module exposes its own hooks to use to modify
core functionality. These hooks are defined
in: `recurring_events_registration.api.php`.

### Dependencies

This registration submodule only relies on the main recurring\_events module
being enabled:

-   recurring\_events:recurring\_events
