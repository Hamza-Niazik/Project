<?php

namespace Drupal\recurring_events\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Plugin implementation of the 'weekly recurring date' widget.
 *
 * @FieldWidget (
 *   id = "weekly_recurring_date",
 *   label = @Translation("Weekly recurring date widget"),
 *   field_types = {
 *     "weekly_recurring_date"
 *   }
 * )
 */
class WeeklyRecurringDateWidget extends DailyRecurringDateWidget {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#type'] = 'container';
    $element['#states'] = [
      'visible' => [
        ':input[name="recur_type"]' => ['value' => 'weekly_recurring_date'],
      ],
    ];
    $element['#element_validate'][] = [$this, 'validateForm'];

    $days = $this->getDayOptions();
    $element['days'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Days of the Week'),
      '#options' => $days,
      '#default_value' => $items[$delta]->days ? explode(',', $items[$delta]->days) : [],
      '#weight' => 5,
    ];

    unset($element['end_time']['#states']);
    unset($element['end_time']['time']['#states']);
    unset($element['duration']['#states']);
    $element['end_time']['#states']['invisible'][':input[name="weekly_recurring_date[0][duration_or_end_time]"]'] = ['value' => 'duration'];
    $element['end_time']['time']['#states']['invisible'][':input[name="weekly_recurring_date[0][duration_or_end_time]"]'] = ['value' => 'duration'];
    $element['duration']['#states']['visible'][':input[name="weekly_recurring_date[0][duration_or_end_time]"]'] = ['value' => 'duration'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
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

      $item['days'] = array_filter($item['days']);
      if (!empty($item['days'])) {
        $item['days'] = implode(',', $item['days']);
      }
      else {
        $item['days'] = '';
      }

    }
    $values = parent::massageFormValues($values, $form, $form_state);
    return $values;
  }

  /**
   * Return day options for events.
   *
   * @return array
   *   An array of days suitable for a checkbox field.
   */
  protected function getDayOptions() {
    $config = \Drupal::config('recurring_events.eventseries.config');
    $days = explode(',', $config->get('days'));
    // All labels should have a capital first letter as they are proper nouns.
    $day_labels = array_map('ucwords', $days);
    $days = array_combine($days, $day_labels);

    \Drupal::moduleHandler()->alter('recurring_events_days', $days);

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
    if ($recur_type[0]['value'] === 'weekly_recurring_date') {
      $values = $form_state->getValue('weekly_recurring_date');
      if (empty($values[0])) {
        $form_state->setError($element, $this->t('Please configure the Weekly Recurring Date settings'));
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

        if (empty($values['duration']) || !isset($complete_form['weekly_recurring_date']['widget'][0]['duration']['#options'][$values['duration']])) {
          $form_state->setError($element['duration'], $this->t('Please select a duration from the list'));
        }

        $filtered_days = array_filter($values['days'], function ($value) {
          return !empty($value);
        });

        if (empty($values['days']) || empty($filtered_days)) {
          $form_state->setError($element['days'], $this->t('Please select week days from the list'));
        }

      }
    }
  }

}
