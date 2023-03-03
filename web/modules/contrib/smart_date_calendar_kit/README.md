CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module installs a Fullcalendar View preconfigure to display Event nodes, of
the kind defined by Smart Date Starter Kit. Although neither of these modules
are created with recurring events functionality enabled by default, adding this
is simply a matter of enabling the Smart Date Recur submodule and then updating
the Event content type's When field to allow recurring events at
/admin/structure/types/manage/event/fields/node.event.field_when

Note that there are extra permissions for using recurring options, so this may
require some adjustments to meet your site's requirements.

The Calendar view created is set to be a menu tab, so it will be shown in a tab
set with the view displays created by Smart Date Starter Kit.


INSTALLATION
------------

 * Install the Smart Date and Fullcalendar Views modules as you would normally
   install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.
 * Then, install the Smart Date Starter Kit module. This will import
   configuration for the Event content type and an associated list view.
 * Finally, install Smart Date Calendar Kit. This will import a view configured
   to show Smart Date events in a Fullcalendar presentation.

- OR -

If using composer and drush, run
`composer require drupal/smart_date_calendar_kit`
and then `drush en smart_date_calendar_kit`. This will download and install all
the necessary modules.

Post installation (by either method) you can safely uninstall both Smart Date
Starter Kit and Smart Date Calendar Kit without losing the configuration
imported.


REQUIREMENTS
------------

This module requires the Smart Date, Fullcalendar View, and Smart Date Starter
Kit modules.


CONFIGURATION
-------------

 * There isn't really any specific configuration per se, but you may want to
   tweak the settings for the view, for example to show linked events in a
   popup or change the default colour used to highlight events in the calendar.


MAINTAINERS
-----------

 * Current Maintainer: Martin Anderson-Clutz (mandclu) -
   https://www.drupal.org/u/mandclu
