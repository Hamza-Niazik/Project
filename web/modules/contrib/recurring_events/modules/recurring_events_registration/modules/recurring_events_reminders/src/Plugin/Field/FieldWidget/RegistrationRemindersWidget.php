<?php

namespace Drupal\recurring_events_reminders\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\text\Plugin\Field\FieldWidget\TextareaWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\recurring_events_registration\RegistrationCreationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'event registration reminders' widget.
 *
 * @FieldWidget (
 *   id = "registration_reminders",
 *   label = @Translation("Event registration reminders widget"),
 *   field_types = {
 *     "registration_reminders"
 *   }
 * )
 */
class RegistrationRemindersWidget extends TextareaWidget {

  use StringTranslationTrait;

  /**
   * The registration creation service.
   *
   * @var \Drupal\recurring_events_registration\RegistrationCreationService
   */
  protected $creationService;

  /**
   * Constructs a WidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\recurring_events_registration\RegistrationCreationService $creation_service
   *   The registration creation service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, RegistrationCreationService $creation_service) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->fieldDefinition = $field_definition;
    $this->settings = $settings;
    $this->thirdPartySettings = $third_party_settings;
    $this->creationService = $creation_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('recurring_events_registration.creation_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $message = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['data'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Event Registration Reminders'),
      '#states' => [
        'visible' => [
          'input[name="event_registration[0][registration]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $element['data']['reminder'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Registration Reminders'),
      '#description' => $this->t('Select this box to enable registration reminders for this event.'),
      '#weight' => 0,
      '#default_value' => $items[$delta]->reminder ?: '',
    ];

    $element['data']['reminder_data'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          'input[name="registration_reminders[0][data][reminder]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];

    $element['data']['reminder_data']['reminder_amount'] = [
      '#type' => 'number',
      '#prefix' => $this->t('Send a reminder'),
      '#title' => '',
      '#weight' => 1,
      '#default_value' => $items[$delta]->reminder_amount ?? '1',
      '#min' => 0,
    ];

    $element['data']['reminder_data']['reminder_units'] = [
      '#type' => 'select',
      '#title' => '',
      '#weight' => 2,
      '#default_value' => $items[$delta]->reminder_units ?? 'month',
      '#options' => [
        'month' => $this->t('months'),
        'week' => $this->t('weeks'),
        'day' => $this->t('days'),
        'hour' => $this->t('hours'),
        'minute' => $this->t('minutes'),
      ],
      '#suffix' => $this->t('before the event starts'),
    ];

    $element['data']['reminder_data']['message'] = $message;
    $element['data']['reminder_data']['message']['#title'] = $this->t('Reminder Message');
    $element['data']['reminder_data']['message']['#weight'] = 99;

    $registrant_tokens = $this->creationService->getAvailableTokens([
      'registrant',
      'eventseries',
      'eventinstance',
    ]);
    $element['data']['reminder_data']['tokens'] = $registrant_tokens;
    $element['data']['reminder_data']['tokens']['#weight'] = 100;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      $item['reminder'] = $item['data']['reminder'] ?? 0;
      $item['reminder_amount'] = $item['data']['reminder_data']['reminder_amount'];
      $item['reminder_units'] = $item['data']['reminder_data']['reminder_units'];
      $item['value'] = $item['data']['reminder_data']['message']['value'] ?? '';
      $item['format'] = $item['data']['reminder_data']['message']['format'];
      unset($item['data']);
    }
    $values = parent::massageFormValues($values, $form, $form_state);
    return $values;
  }

}
