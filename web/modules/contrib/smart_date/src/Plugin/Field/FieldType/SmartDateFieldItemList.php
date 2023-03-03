<?php

namespace Drupal\smart_date\Plugin\Field\FieldType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeFieldItemList;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Represents a configurable entity smartdate field.
 */
class SmartDateFieldItemList extends DateTimeFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) {
    if (empty($this->getFieldDefinition()->getDefaultValueCallback())) {
      if ($this->getFieldDefinition()->getDefaultValueLiteral()) {
        $default_value = $this->getFieldDefinition()->getDefaultValueLiteral()[0];
      }
      else {
        $default_value = [];
      }

      $element = parent::defaultValuesForm($form, $form_state);

      $element['default_date_type']['#options']['next_hour'] = t('Next hour');

      unset($element['default_time_type']);

      $this->addDurationConfig($element, $default_value);

      return $element;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function addDurationConfig(array &$element, array $default_value) {
    $description = '<p>' . t('The possible durations this field can contain. Enter one value per line, in the format key|label.');
    $description .= '<br/>' . t('The key is the stored value, and must be numeric or "custom" to allow an arbitrary length. The label will be used in edit forms.');
    $description .= '<br/>' . t('The label is optional: if a line contains a single number, it will be used as key and label.') . '</p>';

    $element['default_duration_increments'] = [
      '#type' => 'textarea',
      '#title' => t('Allowed duration increments'),
      '#description' => $description,
      '#default_value' => isset($default_value['default_duration_increments']) ? $default_value['default_duration_increments'] : "30\n60|1 hour\n90\n120|2 hours\ncustom",
      '#required' => TRUE,
    ];

    $element['default_duration'] = [
      '#type' => 'textfield',
      '#title' => t('Default duration'),
      '#description' => t('Set which of the duration increments provided above that should be selected by default.'),
      '#default_value' => isset($default_value['default_duration']) ? $default_value['default_duration'] : '60',
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormValidate(array $element, array &$form, FormStateInterface $form_state) {
    $duration = $form_state->getValue([
      'default_value_input',
      'default_duration',
    ]) ?? '';
    if ($duration) {
      $increments = SmartDateListItemBase::parseValues($form_state->getValue([
        'default_value_input',
        'default_duration_increments',
      ]));
      // Handle a false result: will display the proper error later.
      if (!$increments) {
        $increments = [];
      }
      $increment_min = -1;
      // Iterate through returned array and throw an error for an invalid key.
      foreach ($increments as $key => $label) {
        if (intval($key) == 0 && $key !== '0' && $key !== 0 && $key !== 'custom') {
          $form_state->setErrorByName('default_value_input][default_duration_increments', $this->t('Invalid tokens in the allowed increments specified. Please provide either integers or "custom" as the key for each value.'));
          break;
        }
        else {
          $increment_min = ($increment_min < intval($key)) ? intval($key) : $increment_min;
        }
      }
      if (!in_array('custom', $increments)) {
        if ($increment_min < 0) {
          $form_state->setErrorByName('default_value_input][default_duration_increments', $this->t('Unable to parse valid durations from the allowed increments specified.'));
        }
        else {
          $messenger = \Drupal::messenger();
          $messenger->addMessage($this->t('No string to allow for custom values, so the provided increments will be strictly enforced.'), 'warning');
        }
      }
      if (!isset($increments[$duration])) {
        $form_state->setErrorByName('default_value_input][default_duration', $this->t('Please specify a default duration that is one of the provided options.'));
      }
    }
    // Use the parent class method to validate relative dates.
    DateTimeFieldItemList::defaultValuesFormValidate($element, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    $duration = $form_state->getValue([
      'default_value_input',
      'default_duration',
    ]) ?? '';
    $duration_increments = $form_state->getValue([
      'default_value_input',
      'default_duration_increments',
    ]) ?? '';
    if (strlen((string) $duration) && strlen((string) $duration_increments)) {
      if ($duration) {
        $form_state->setValueForElement($element['default_duration'], $duration);
      }
      if ($duration_increments) {
        $form_state->setValueForElement($element['default_duration_increments'], $duration_increments);
      }
      return [$form_state->getValue('default_value_input')];
    }
    // Use the parent class method to store current date configuration.
    DateTimeFieldItemList::defaultValuesFormSubmit($element, $form, $form_state);
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function processDefaultValue($default_value, FieldableEntityInterface $entity, FieldDefinitionInterface $definition) {
    // Explicitly call the base class so that we can get the default value
    // types.
    $default_value = FieldItemList::processDefaultValue($default_value, $entity, $definition);

    // No default set, so nothing to do.
    if (empty($default_value[0]['default_date_type'])) {
      return $default_value;
    }

    // A default date+time value should be in the format and timezone used
    // for date storage.
    $date = new DrupalDateTime($default_value[0]['default_date'], DateTimeItemInterface::STORAGE_TIMEZONE);

    // If using 'next_hour' for 'default_date_type', do custom processing.
    if ($default_value[0]['default_date_type'] == 'next_hour') {
      $date->modify('+1 hour');
      // After conversion to timestamp, we round up, so offset for this.
      $min = (int) $date->format('i') + 1;
      $date->modify('-' . $min . ' minutes');
    }

    $value = $date->getTimestamp();
    // Round up to the next minute.
    $second = $date->format("s");
    if ($second > 0) {
      $value += 60 - $second;
    }
    // Calculate the end value.
    $duration = (int) $default_value[0]['default_duration'];
    $end_value = $value + ($duration * 60);

    $default_value = [
      [
        'value' => $value,
        'end_value' => $end_value,
        'date' => $date,
      ],
    ];

    return $default_value;
  }

}
