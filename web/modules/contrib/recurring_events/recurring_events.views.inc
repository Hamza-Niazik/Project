<?php

/**
 * @file
 * Views functionality for the recurring_events module.
 */

/**
 * Implements hook_views_data_alter().
 */
function recurring_events_views_data_alter(array &$data) {
  // Create a field to show the number of instances of an eventseries.
  $data['eventseries_field_data']['eventseries_instance_count'] = [
    'title' => t('Event series instance count'),
    'field' => [
      'title' => t('Event series instance count'),
      'help' => t('The number of event instances in a series.'),
      'id' => 'eventseries_instance_count',
      'click sortable' => FALSE,
    ],
  ];

  // Create a field to show the start date of an event series.
  $data['eventseries_field_data']['eventseries_start_date'] = [
    'title' => t('Event series start date'),
    'field' => [
      'title' => t('Event series start date'),
      'help' => t('The date on which an event first occurs.'),
      'id' => 'eventseries_start_date',
      'click sortable' => FALSE,
    ],
  ];

  $data['eventseries_field_data']['event_instances_target_id'] = [
    'title' => t('Event Instances'),
    'help' => t('Display the event instances associated with the series'),
    'relationship' => [
      // Views name of the table being joined to from foo.
      'base' => 'eventinstance_field_data',
      // Database field name in example_table for the join.
      'base field' => 'eventseries_id',
      // Real database field name in foo for the join, to override
      // 'unique_dummy_name'.
      'field' => 'id',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      'label' => t('Event Series Instances'),
    ],
  ];

  $data['eventseries_field_data']['term_eventseries_tid_depth'] = [
    'help' => t('Display event series if it has the selected taxonomy terms, or children of the selected terms.'),
    'real field' => 'id',
    'argument' => [
      'title' => t('Event Series has taxonomy term ID (with depth)'),
      'id' => 'taxonomy_index_tid_eventseries_depth',
      'accept depth modifier' => TRUE,
    ],
    'filter' => [
      'title' => t('Event Series has taxonomy term ID (with depth)'),
      'id' => 'taxonomy_index_tid_eventseries_depth',
    ]
  ];

  // Set the default field for a view based on eventinstances.
  $data['eventinstance_field_data']['table']['base']['defaults']['field'] = 'id';

  $data['eventinstance_field_data']['term_eventinstance_tid_depth'] = [
    'help' => t('Display event instance if it has the selected taxonomy terms, or children of the selected terms.'),
    'real field' => 'id',
    'argument' => [
      'title' => t('Event Instance has taxonomy term ID (with depth)'),
      'id' => 'taxonomy_index_tid_eventinstance_depth',
      'accept depth modifier' => TRUE,
    ],
    'filter' => [
      'title' => t('Event Instance has taxonomy term ID (with depth)'),
      'id' => 'taxonomy_index_tid_eventinstance_depth',
    ]
  ];

  // @todo Remove these declarations when
  // https://www.drupal.org/project/drupal/issues/2489476 is resolved.
  $table_name = 'eventinstance_field_data';

  $fields = [
    'Event Date' => 'date__value',
    'Event Date (End)' => 'date__end_value',
  ];

  $arguments = [
    // Argument type => help text.
    'year' => t('Date in the form of YYYY.'),
    'month' => t('Date in the form of MM (01 - 12).'),
    'day' => t('Date in the form of DD (01 - 31).'),
    'week' => t('Date in the form of WW (01 - 53).'),
    'year_month' => t('Date in the form of YYYYMM.'),
    'full_date' => t('Date in the form of CCYYMMDD.'),
  ];

  foreach ($fields as $field => $field_name) {
    $data[$table_name][$field_name]['filter']['id'] = 'datetime';
    $data[$table_name][$field_name]['filter']['field_name'] = 'date';
    $data[$table_name][$field_name]['sort']['id'] = 'datetime';
    $data[$table_name][$field_name]['sort']['field_name'] = 'date';
    $data[$table_name][$field_name]['argument']['id'] = 'datetime';
    $data[$table_name][$field_name]['argument']['field_name'] = 'date';

    $group = $data[$table_name][$field_name]['group'] ?? '';

    foreach ($arguments as $argument_type => $help_text) {
      $data[$table_name][$field_name . '_' . $argument_type] = [
        'title' => t('@label (@argument)', [
          '@label' => $field,
          '@argument' => $argument_type,
        ]),
        'help' => $help_text,
        'argument' => [
          'field' => $field_name,
          'id' => 'datetime_' . $argument_type,
          'entity_type' => 'eventinstance',
          'field_name' => 'date',
        ],
        'group' => $group,
      ];
    }
  }

  // We do not want people adding the recurrence fields to views as they will
  // not work. Instead for any fields necessary we create them above.
  $fields = [
    'consecutive_recurring_date__',
    'daily_recurring_date__',
    'weekly_recurring_date__',
    'monthly_recurring_date__',
  ];
  foreach ($fields as $field) {
    foreach ($data['eventseries_field_data'] as $field_name => $field_info) {
      if (strpos($field_name, $field) === 0) {
        unset($data['eventseries_field_data'][$field_name]);
      }
    }

    foreach ($data['eventseries_field_revision'] as $field_name => $field_info) {
      if (strpos($field_name, $field) === 0) {
        unset($data['eventseries_field_revision'][$field_name]);
      }
    }
  }
}
