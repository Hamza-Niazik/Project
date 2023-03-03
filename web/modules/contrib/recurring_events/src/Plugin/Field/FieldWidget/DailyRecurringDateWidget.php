<?php

namespace Drupal\recurring_events\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeDefaultWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\recurring_events\Plugin\RecurringEventsFieldTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Plugin implementation of the 'daily recurring date' widget.
 *
 * @FieldWidget (
 *   id = "daily_recurring_date",
 *   label = @Translation("Daily recurring date widget"),
 *   field_types = {
 *     "daily_recurring_date"
 *   }
 * )
 */
class DailyRecurringDateWidget extends DateRangeDefaultWidget {

  use RecurringEventsFieldTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#type'] = 'container';
    $element['#states'] = [
      'visible' => [
        ':input[name="recur_type"]' => ['value' => 'daily_recurring_date'],
      ],
    ];
    $element['#element_validate'][] = [$this, 'validateForm'];

    $element['value']['#title'] = $this->t('Create Events Between');
    $element['value']['#weight'] = 1;
    $element['value']['#date_date_format'] = DateTimeItemInterface::DATE_STORAGE_FORMAT;
    $element['value']['#date_date_element'] = 'date';
    $element['value']['#date_time_format'] = '';
    $element['value']['#date_time_element'] = 'none';

    $element['end_value']['#title'] = $this->t('And');
    $element['end_value']['#weight'] = 2;
    $element['end_value']['#date_date_format'] = DateTimeItemInterface::DATE_STORAGE_FORMAT;
    $element['end_value']['#date_date_element'] = 'date';
    $element['end_value']['#date_time_format'] = '';
    $element['end_value']['#date_time_element'] = 'none';
    $times = $this->getTimeOptions();
    if ($times) {
      $element['time'] = [
        '#type' => 'select',
        '#title' => $this->t('Event Start Time'),
        '#options' => $times,
        '#default_value' => strtolower($items[$delta]->time ?: ''),
        '#weight' => 3,
      ];

      $element['end_time'] = [
        // @todo Remove the container and apply #states and #weight directly to
        // the element when https://www.drupal.org/project/drupal/issues/2419131
        // lands.
        '#type' => 'container',
        '#states' => [
          'invisible' => [
            ':input[name="daily_recurring_date[0][duration_or_end_time]"]' => ['value' => 'duration'],
          ],
        ],
        '#weight' => 4,
        'time' => [
          '#type' => 'select',
          '#title' => $this->t('Event End Time'),
          '#options' => $times,
          '#default_value' => strtolower($items[$delta]->end_time ?: ''),
        ],
      ];
    }
    else {
      $default_value = '';
      if ($items[$delta]->time) {
        $default_value = DrupalDateTime::createFromFormat('h:i A', strtoupper($items[$delta]->time));
      }
      $element['time'] = [
        '#type' => 'datetime',
        '#date_date_element' => 'none',
        '#date_time_element' => 'time',
        '#title' => $this->t('Event Start Time'),
        '#default_value' => $default_value,
        '#weight' => 3,
      ];

      $end_default_value = '';
      if ($items[$delta]->end_time) {
        $end_default_value = DrupalDateTime::createFromFormat('h:i A', strtoupper($items[$delta]->end_time));
      }

      $element['end_time'] = [
        // @todo Remove the container and apply #states and #weight directly to
        // the element when https://www.drupal.org/project/drupal/issues/2419131
        // lands.
        '#type' => 'container',
        '#weight' => 5,
        '#states' => [
          'invisible' => [
            ':input[name="daily_recurring_date[0][duration_or_end_time]"]' => ['value' => 'duration'],
          ],
        ],
        'time' => [
          '#type' => 'datetime',
          '#date_date_element' => 'none',
          '#date_time_element' => 'time',
          '#title' => $this->t('Event End Time'),
          '#default_value' => $end_default_value,
          '#states' => [
            'invisible' => [
              ':input[name="daily_recurring_date[0][duration_or_end_time]"]' => ['value' => 'duration'],
            ],
          ],
        ],
      ];
    }

    $durations = $this->getDurationOptions();
    $element['duration'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Duration'),
      '#options' => $durations,
      '#default_value' => $items[$delta]->duration ?: '',
      '#weight' => 5,
      '#states' => [
        'visible' => [
          ':input[name="daily_recurring_date[0][duration_or_end_time]"]' => ['value' => 'duration'],
        ],
      ],
    ];

    $element['duration_or_end_time'] = [
      '#type' => 'radios',
      '#default_value' => $items[$delta]->duration_or_end_time ?: 'duration',
      '#weight' => 3,
      '#options' => [
        'duration' => $this->t('Set Duration'),
        'end_time' => $this->t('Set End Time'),
      ],
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
      if (!empty($item['end_time']['time'])) {
        if ($item['end_time']['time'] instanceof DrupalDateTime) {
          $item['end_time'] = $item['end_time']['time']->format('h:i A');
        }
        else {
          $item['end_time'] = $item['end_time']['time'];
        }
      }
      if (empty($item['duration_or_end_time'])) {
        $item['duration_or_end_time'] = 'duration';
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
    if ($recur_type[0]['value'] === 'daily_recurring_date') {
      $values = $form_state->getValue('daily_recurring_date');
      if (empty($values[0])) {
        $form_state->setError($element, $this->t('Please configure the Daily Recurring Date settings'));
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

        if (empty($values['duration']) || !isset($complete_form['daily_recurring_date']['widget'][0]['duration']['#options'][$values['duration']])) {
          $form_state->setError($element['duration'], $this->t('Please select a duration from the list'));
        }
      }
    }
  }

}
