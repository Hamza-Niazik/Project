<?php

/**
 * @file
 * Views functionality for the recurring_events_registration module.
 */

/**
 * Implements hook_views_data_alter().
 */
function recurring_events_registration_views_data_alter(array &$data) {
  // Set the default field for a view based on registrants.
  $data['registrant']['table']['base']['defaults']['field'] = 'email';

  $data['eventinstance_field_data']['registration_availability'] = [
    'title' => t('Registration Availability'),
    'field' => [
      'title' => t('Registration Availability'),
      'help' => t('The number of registration spaces available for an event instance.'),
      'field' => 'event_registration__capacity',
      'id' => 'eventinstance_registration_availability',
      'click sortable' => FALSE,
    ],
    'filter' => [
      'title' => t('Registration Availability'),
      'help' => t('Whether the event instance has availability.'),
      'field' => 'event_registration__capacity',
      'id' => 'eventinstance_registration_availability',
    ],
  ];

  $data['eventinstance_field_data']['registration_availability_count'] = [
    'title' => t('Registration Availability Count'),
    'filter' => [
      'title' => t('Registration Availability Count'),
      'help' => t('The number of registration spaces available for an event instance.'),
      'field' => 'event_registration__capacity',
      'id' => 'eventinstance_registration_availability_count',
    ],
    'argument' => [
      'title' => t('Registration Availability Count'),
      'help' => t('The number of registration spaces available for an event instance.'),
      'field' => 'event_registration__capacity',
      'id' => 'eventinstance_registration_availability_count',
      'filter' => 'intval',
    ],
  ];

  $data['eventinstance_field_data']['capacity'] = [
    'title' => t('Capacity'),
    'field' => [
      'title' => t('Capacity'),
      'help' => t('The number of registration spaces available for an event instance'),
      'id' => 'eventinstance_capacity',
      'click sortable' => FALSE,
    ],
  ];

  $data['eventinstance_field_data']['registration_count'] = [
    'title' => t('Registration Count'),
    'field' => [
      'title' => t('Registration Count'),
      'help' => t('The number of registrations for an event instance.'),
      'id' => 'eventinstance_registration_count',
      'click sortable' => FALSE,
    ],
  ];

  $data['eventinstance_field_data']['waitlist_count'] = [
    'title' => t('Waitlist Count'),
    'field' => [
      'title' => t('Waitlist Count'),
      'help' => t('The number of waitlisted registrants for an event instance.'),
      'id' => 'eventinstance_waitlist_count',
      'click sortable' => FALSE,
    ],
  ];

  // We do not want people adding the event_registration__ fields to views as
  // they will not work. Instead for any fields necessary we create them above.
  foreach ($data['eventseries_field_data'] as $field_name => $field) {
    if (strpos($field_name, 'event_registration__') === 0) {
      unset($data['eventseries_field_data'][$field_name]);
    }
  }
  foreach ($data['eventseries_field_revision'] as $field_name => $field) {
    if (strpos($field_name, 'event_registration__') === 0) {
      unset($data['eventseries_field_revision'][$field_name]);
    }
  }
}
