CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended Modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Heading field module adds a new field type containing a text field and a
heading size. The field will be formatted as a HTML heading (h1 - h6).

It adds also a string/text field formatter so the field can be formatted as a
heading of a specific size.

 * For a full description of the module visit:
   https://www.drupal.org/project/heading

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/heading


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Heading field module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

Add a heading field to an entity:

    1. Navigate to the field configuration of the entity the heading should be
       added to.
    2. Add a new field, select "Heading" as type.
    3. A default value can be set and there is an option to limit the
       allowed heading sizes.
    4. Save configuration.

Format a string/text field as a heading:

    1. Navigate to the display configuration of the entity containing the
       string/text field.
    2. Select "Heading" as the format.
    3. Click on the cog wheel to change the heading size.
    4. Save configuration.

MAINTAINERS
-----------

 * Peter Decuyper (zero2one) - https://www.drupal.org/u/zero2one

Supporting organizations:

 * Digipolis Gent - https://www.drupal.org/digipolis-gent
 * Serial Graphics - https://www.drupal.org/serial-graphics
