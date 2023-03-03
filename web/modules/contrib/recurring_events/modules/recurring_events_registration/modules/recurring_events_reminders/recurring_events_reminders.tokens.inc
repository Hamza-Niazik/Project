<?php

/**
 * @file
 * Tokens for the recurring_events_reminders module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function recurring_events_reminders_token_info() {
  $eventseries = [];
  $eventseries_type = [
    'name' => t('Event Series'),
    'description' => t('Tokens for the eventseries entity type.'),
    'needs-data' => 'eventseries',
  ];

  $eventseries['reminder_message'] = [
    'name' => t('Event Series Reminder Message'),
    'description' => t('The reminder message to send to registrants.'),
  ];

  return [
    'types' => [
      'eventseries' => $eventseries_type,
    ],
    'tokens' => [
      'eventseries' => $eventseries,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function recurring_events_reminders_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'eventseries' && !empty($data['eventseries'])) {
    $event_series = $data['eventseries'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'reminder_message':
          $replacements[$original] = $event_series->registration_reminders->value;
          break;
      }
    }
  }
  return $replacements;
}
