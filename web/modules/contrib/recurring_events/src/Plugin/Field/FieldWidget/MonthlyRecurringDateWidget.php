<?php

namespace Drupal\recurring_events\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Plugin implementation of the 'monthly recurring date' widget.
 *
 * @FieldWidget (
 *   id = "monthly_recurring_date",
 *   label = @Translation("Monthly recurring date widget"),
 *   field_types = {
 *     "monthly_recurring_date"
 *   }
 * )
 */
class MonthlyRecurringDateWidget extends WeeklyRecurringDateWidget {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['#type'] = 'container';
    $element['#states'] = [
      'visible' => [
        ':input[name="recur_type"]' => ['value' => 'monthly_recurring_date'],
      ],
    ];
    $element['#element_validate'][] = [$this, 'validateForm'];

    $element['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Event Recurrence Schedule'),
      '#options' => [
        'weekday' => $this->t('Recur on Day of Week'),
        'monthday' => $this->t('Recur on Day of Month'),
      ],
      '#default_value' => $items[$delta]->type ?: '',
      '#weight' => 5,
    ];

    $element['day_occurrence'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Day Occurrence'),
      '#options' => [
        'first' => $this->t('First'),
        'second' => $this->t('Second'),
        'third' => $this->t('Third'),
        'fourth' => $this->t('Fourth'),
        'last' => $this->t('Last'),
      ],
      '#default_value' => $items[$delta]->day_occurrence ? explode(',', $items[$delta]->day_occurrence) : [],
      '#states' => [
        'visible' => [
          ':input[name="monthly_recurring_date[0][type]"]' => ['value' => 'weekday'],
        ],
      ],
      '#weight' => 6,
    ];

    $days = $this->getDayOptions();
    $element['days'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Days of the Week'),
      '#options' => $days,
      '#default_value' => $items[$delta]->days ? explode(',', $items[$delta]->days) : [],
      '#states' => [
        'visible' => [
          ':input[name="monthly_recurring_date[0][type]"]' => ['value' => 'weekday'],
        ],
      ],
      '#weight' => 7,
    ];

    $month_days = $this->getMonthDayOptions();
    $element['day_of_month'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Days of the Month'),
      '#options' => $month_days,
      '#default_value' => $items[$delta]->day_of_month ? explode(',', $items[$delta]->day_of_month) : [],
      '#states' => [
        'visible' => [
          ':input[name="monthly_recurring_date[0][type]"]' => ['value' => 'monthday'],
        ],
      ],
      '#weight' => 8,
    ];

    unset($element['end_time']['#states']);
    unset($element['end_time']['time']['#states']);
    unset($element['duration']['#states']);
    $element['end_time']['#states']['invisible'][':input[name="monthly_recurring_date[0][duration_or_end_time]"]'] = ['value' => 'duration'];
    $element['end_time']['time']['#states']['invisible'][':input[name="monthly_recurring_date[0][duration_or_end_time]"]'] = ['value' => 'duration'];
    $element['duration']['#states']['visible'][':input[name="monthly_recurring_date[0][duration_or_end_time]"]'] = ['value' => 'duration'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {

      $item['day_occurrence'] = array_filter($item['day_occurrence']);
      if (!empty($item['day_occurrence'])) {
        $item['day_occurrence'] = implode(',', $item['day_occurrence']);
      }
      else {
        $item['day_occurrence'] = '';
      }

      $item['day_of_month'] = array_filter($item['day_of_month']);
      if (!empty($item['day_of_month'])) {
        $item['day_of_month'] = implode(',', $item['day_of_month']);
      }
      else {
        $item['day_of_month'] = '';
      }
    }

    $values = parent::massageFormValues($values, $form, $form_state);
    return $values;
  }

  /**
   * Return day of month options for events.
   *
   * @return array
   *   An array of days of month suitable for a checkbox field.
   */
  protected function getMonthDayOptions() {
    $days = [];
    $start = date('Y') . '-01-01';
    $date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATE_STORAGE_FORMAT, $start);

    for ($x = 1; $x <= 31; $x++) {
      $days[$x] = $date->format('jS');
      $date->modify('+1 day');
    }

    $days[-1] = $this->t('Last');

    \Drupal::moduleHandler()->alter('recurring_events_month_days', $days);

    return $days;
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
    if ($recur_type[0]['value'] === 'monthly_recurring_date') {
      $values = $form_state->getValue('monthly_recurring_date');
      if (empty($values[0])) {
        $form_state->setError($element, $this->t('Please configure the Monthly Recurring Date settings'));
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

        if (empty($values['duration']) || !isset($complete_form['monthly_recurring_date']['widget'][0]['duration']['#options'][$values['duration']])) {
          $form_state->setError($element['duration'], $this->t('Please select a duration from the list'));
        }

        if (empty($values['type']) || !isset($complete_form['monthly_recurring_date']['widget'][0]['type']['#options'][$values['type']])) {
          $form_state->setError($element['type'], $this->t('Please select an event recurrence schedule type from the list'));
        }
        else {
          switch ($values['type']) {
            case 'weekday':
              $filtered_day_occurrences = array_filter($values['day_occurrence'], function ($value) {
                return !empty($value);
              });
              if (empty($values['day_occurrence']) || empty($filtered_day_occurrences)) {
                $form_state->setError($element['day_occurrence'], $this->t('Please select a day occurrence from the list'));
              }
              $filtered_days = array_filter($values['days'], function ($value) {
                return !empty($value);
              });
              if (empty($values['days']) || empty($filtered_days)) {
                $form_state->setError($element['days'], $this->t('Please select week days from the list'));
              }
              break;

            case 'monthday':
              $filtered_days = array_filter($values['day_of_month'], function ($value) {
                return !empty($value);
              });
              if (empty($values['day_of_month']) || empty($filtered_days)) {
                $form_state->setError($element['day_of_month'], $this->t('Please select days of the month from the list'));
              }
              break;
          }
        }
      }
    }
  }

}
