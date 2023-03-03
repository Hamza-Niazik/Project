<?php

namespace Drupal\recurring_events\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeDefaultWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\recurring_events\Plugin\RecurringEventsFieldTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\recurring_events\Plugin\Field\FieldType\ConsecutiveRecurringDate;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Plugin implementation of the 'consecutive recurring date' widget.
 *
 * @FieldWidget (
 *   id = "consecutive_recurring_date",
 *   label = @Translation("Consecutive recurring date widget"),
 *   field_types = {
 *     "consecutive_recurring_date"
 *   }
 * )
 */
class ConsecutiveRecurringDateWidget extends DateRangeDefaultWidget {

  use RecurringEventsFieldTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $config = \Drupal::config('recurring_events.eventseries.config');

    $element['#type'] = 'container';
    $element['#states'] = [
      'visible' => [
        ':input[name="recur_type"]' => ['value' => 'consecutive_recurring_date'],
      ],
    ];
    $element['#element_validate'][] = [$this, 'validateThreshold'];
    $element['#element_validate'][] = [$this, 'validateForm'];

    $element['value']['#title'] = $this->t('Create Events Between');
    $element['value']['#weight'] = 1;
    $element['value']['#date_date_format'] = DateTimeItemInterface::DATE_STORAGE_FORMAT;
    $element['value']['#date_date_element'] = 'date';
    $element['value']['#date_time_format'] = '';
    $element['value']['#date_time_element'] = 'none';
    $element['value']['#ajax'] = [
      'callback' => [$this, 'changeDuration'],
      'event' => 'change',
      'wrapper' => 'eventseries-edit-form',
    ];

    $element['end_value']['#title'] = $this->t('And');
    $element['end_value']['#weight'] = 2;
    $element['end_value']['#date_date_format'] = DateTimeItemInterface::DATE_STORAGE_FORMAT;
    $element['end_value']['#date_date_element'] = 'date';
    $element['end_value']['#date_time_format'] = '';
    $element['end_value']['#date_time_element'] = 'none';
    $element['end_value']['#ajax'] = [
      'callback' => [$this, 'changeDuration'],
      'event' => 'change',
      'wrapper' => 'eventseries-edit-form',
    ];

    $times = $this->getTimeOptions();
    if ($times) {
      $time_keys = array_keys($times);
      $start_time = reset($time_keys);
      $end_time = end($time_keys);
      $element['time'] = [
        '#type' => 'select',
        '#title' => $this->t('First Event Starts At'),
        '#options' => $times,
        '#default_value' => $items[$delta]->time ?? $start_time,
        '#weight' => 3,
        '#ajax' => [
          'callback' => [$this, 'changeDuration'],
          'event' => 'change',
          'wrapper' => 'eventseries-edit-form',
        ],
      ];

      $element['end_time'] = [
        '#type' => 'select',
        '#title' => $this->t('Final Event Starts At'),
        '#options' => $times,
        '#default_value' => $items[$delta]->end_time ?? $end_time,
        '#weight' => 4,
        '#ajax' => [
          'callback' => [$this, 'changeDuration'],
          'event' => 'change',
          'wrapper' => 'eventseries-edit-form',
        ],
      ];
    }
    else {
      $default_start = '';
      if ($items[$delta]->time) {
        $default_start = ($items[$delta]->time instanceof DrupalDateTime) ? $items[$delta]->time : DrupalDateTime::createFromFormat('h:i A', strtoupper($items[$delta]->time));
      }

      $default_end = '';
      if ($items[$delta]->end_time) {
        $default_end = ($items[$delta]->end_time instanceof DrupalDateTime) ? $items[$delta]->end_time : DrupalDateTime::createFromFormat('h:i A', strtoupper($items[$delta]->end_time));
      }

      $element['time'] = [
        '#type' => 'datetime',
        '#date_date_element' => 'none',
        '#date_time_element' => 'time',
        '#title' => $this->t('First Event Starts At'),
        '#default_value' => $default_start,
        '#weight' => 3,
        '#ajax' => [
          'callback' => [$this, 'changeDuration'],
          'event' => 'change',
          'wrapper' => 'eventseries-edit-form',
        ],
      ];

      $element['end_time'] = [
        '#type' => 'datetime',
        '#date_date_element' => 'none',
        '#date_time_element' => 'time',
        '#title' => $this->t('Final Event Starts At'),
        '#default_value' => $default_end,
        '#weight' => 4,
        '#ajax' => [
          'callback' => [$this, 'changeDuration'],
          'event' => 'change',
          'wrapper' => 'eventseries-edit-form',
        ],
      ];
    }

    $units = $this->getUnitOptions();

    $element['duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Event Duration'),
      '#default_value' => $items[$delta]->duration ?: 5,
      '#weight' => 5,
      '#ajax' => [
        'callback' => [$this, 'changeDuration'],
        'event' => 'change',
        'wrapper' => 'eventseries-edit-form',
      ],
    ];

    $element['duration_units'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Duration'),
      '#options' => $units,
      '#default_value' => $items[$delta]->duration_units ?: 'minute',
      '#weight' => 6,
      '#ajax' => [
        'callback' => [$this, 'changeDuration'],
        'event' => 'change',
        'wrapper' => 'eventseries-edit-form',
      ],
    ];

    // If the form was reloaded due to a trigger, then check the duration
    // configuration to ensure we're not going to be creating too many
    // instances.
    if ($config->get('threshold_warning')) {
      $trigger = $form_state->getTriggeringElement();
      if (!empty($trigger)) {
        $total = self::checkDuration($form_state);
        if ($total > $config->get('threshold_count')) {
          $message = $config->get('threshold_message');
          $message = str_replace('@total', $total, $message);
          $element['count_warning'] = [
            '#type' => 'markup',
            '#prefix' => '<span class="form-item--error-message">',
            '#markup' => $message,
            '#suffix' => '</span>',
            '#weight' => 6,
          ];
        }
      }
    }

    $element['buffer'] = [
      '#type' => 'number',
      '#title' => $this->t('Event Buffer'),
      '#default_value' => $items[$delta]->buffer ?: 0,
      '#weight' => 7,
    ];

    $element['buffer_units'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Buffer'),
      '#options' => $units,
      '#default_value' => $items[$delta]->buffer_units ?: 'minute',
      '#weight' => 8,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      if (!empty($item['time']) && $item['time'] instanceof DrupalDateTime) {
        $item['time'] = $item['time']->format('h:i A');
      }

      if (!empty($item['end_time']) && $item['end_time'] instanceof DrupalDateTime) {
        $item['end_time'] = $item['end_time']->format('h:i A');
      }

      if (empty($item['value'])) {
        $item['value'] = '';
      }
      elseif (!$item['value'] instanceof DrupalDateTime) {
        $item['value'] = substr($item['value'], 0, 10);
      }
      else {
        $item['value'];
      }
      if (empty($item['end_value'])) {
        $item['end_value'] = '';
      }
      elseif (!$item['end_value'] instanceof DrupalDateTime) {
        $item['end_value'] = substr($item['end_value'], 0, 10);
      }
      else {
        $item['end_value'];
      }

    }
    $values = parent::massageFormValues($values, $form, $form_state);
    return $values;
  }

  /**
   * Perform an AJAX reload of the form.
   */
  public function changeDuration(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    /** @var \Drupal\recurring_events\Entity\EventSeries $entity */
    $entity = $form_state->getformObject()->getEntity();
    $form_id = $form_state->getBuildInfo()['form_id'] == 'eventseries_' . $entity->bundle() . '_edit_form' ? 'eventseries-' . $entity->bundle() . '-edit-form' : 'eventseries-' . $entity->bundle() . '-add-form';
    $response->addCommand(new HtmlCommand('#' . $form_id, $form));
    return $response;
  }

  /**
   * Check for unreasonable duration and duration unit values.
   */
  public static function checkDuration(FormStateInterface $form_state) {
    $day_count = $time_count = $total = 0;
    $utc_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    $form_data = ConsecutiveRecurringDate::convertFormConfigToArray($form_state);
    if (!empty($form_data['start_date']) && !empty($form_data['end_date']) && !empty($form_data['duration'])) {
      $day_count = ConsecutiveRecurringDate::findDailyDatesBetweenDates($form_data['start_date'], $form_data['end_date'], TRUE);
      $time_parts = static::convertTimeTo24hourFormat($form_data['time']);
      if (!empty($time_parts)) {
        $form_data['start_date']->setTime($time_parts[0], $time_parts[1]);
        // Configure the right timezone.
        $form_data['start_date']->setTimezone($utc_timezone);
        $time_count = ConsecutiveRecurringDate::findSlotsBetweenTimes($form_data['start_date'], $form_data, TRUE);
      }
    }

    if (!empty($day_count) && !empty($time_count)) {
      $total = $day_count * $time_count;
    }

    return $total;
  }

  /**
   * Element validate callback to ensure that the threshold is not exceeded.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function validateThreshold(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $config = \Drupal::config('recurring_events.eventseries.config');

    if ($config->get('threshold_prevent_save')) {
      $total = $this->checkDuration($form_state);
      if ($total > $config->get('threshold_count')) {
        $message = $config->get('threshold_message');
        $message = str_replace('@total', $total, $message);
        $form_state->setError($element, $message);
      }
    }
  }

  /**
   * Element validate callback to ensure that widget values are valid.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function validateForm(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $recur_type = $form_state->getValue('recur_type');
    if (isset($recur_type[0]['value']) && $recur_type[0]['value'] === 'consecutive_recurring_date') {
      $values = $form_state->getValue('consecutive_recurring_date');
      if (empty($values[0])) {
        $form_state->setError($element, $this->t('Please configure the Consecutive Recurring Date settings'));
      }
      if (!empty($values[0])) {
        $values = $values[0];

        if (empty($values['value'])) {
          $form_state->setError($element['value'], $this->t('Please enter a start date'));
        }

        if (empty($values['end_value'])) {
          $form_state->setError($element['end_value'], $this->t('Please enter an end date'));
        }

        if (empty($values['time'])) {
          $form_state->setError($element['time'], $this->t('Please enter a start time'));
        }

        if (empty($values['end_time'])) {
          $form_state->setError($element['end_time'], $this->t('Please enter an end time'));
        }

        if (empty($values['duration']) || $values['duration'] < 1) {
          $form_state->setError($element['duration'], $this->t('Please enter a duration greater than 0'));
        }

        if (empty($values['duration_units']) || !isset($complete_form['consecutive_recurring_date']['widget'][0]['duration_units']['#options'][$values['duration_units']])) {
          $form_state->setError($element['duration_units'], $this->t('Please select a duration units value from the list'));
        }

        if (!isset($values['buffer']) || $values['buffer'] === '' || $values['buffer'] < 0) {
          $form_state->setError($element['buffer'], $this->t('Please enter a buffer greater than or equal to 0'));
        }

        if (empty($values['buffer_units']) || !isset($complete_form['consecutive_recurring_date']['widget'][0]['buffer_units']['#options'][$values['buffer_units']])) {
          $form_state->setError($element['buffer_units'], $this->t('Please select a buffer units value from the list'));
        }
      }
    }
  }

}
