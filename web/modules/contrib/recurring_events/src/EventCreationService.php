<?php

namespace Drupal\recurring_events;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\recurring_events\Entity\EventInstance;
use Drupal\field_inheritance\Entity\FieldInheritanceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a service with helper functions useful during event creation.
 */
class EventCreationService {

  use StringTranslationTrait;

  /**
   * The translation interface.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  private $translation;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The field type plugin manager.
   *
   * @var Drupal\Core\Field\FieldTypePluginManager
   */
  protected $fieldTypePluginManager;

  /**
   * The entity field manager.
   *
   * @var Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The key value storage service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValueStore;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation interface.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger factory.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\Field\FieldTypePluginManager $field_type_plugin_manager
   *   The field type plugin manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   *   The key value storage service.
   */
  public function __construct(TranslationInterface $translation, Connection $database, LoggerChannelFactoryInterface $logger, Messenger $messenger, FieldTypePluginManager $field_type_plugin_manager, EntityFieldManager $entity_field_manager, ModuleHandler $module_handler, EntityTypeManagerInterface $entity_type_manager, KeyValueFactoryInterface $key_value) {
    $this->translation = $translation;
    $this->database = $database;
    $this->loggerFactory = $logger->get('recurring_events');
    $this->messenger = $messenger;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->keyValueStore = $key_value;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
      $container->get('database'),
      $container->get('logger.factory'),
      $container->get('messenger'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('entity_field.manager'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('keyvalue')
    );
  }

  /**
   * Check whether there have been form recurring configuration changes.
   *
   * @param Drupal\recurring_events\Entity\EventSeries $event
   *   The stored event series entity.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of an updated event series entity.
   *
   * @return bool
   *   TRUE if recurring config changes, FALSE otherwise.
   */
  public function checkForFormRecurConfigChanges(EventSeries $event, FormStateInterface $form_state) {
    $entity_config = $this->convertArrayLowercaseSorted(
      (array) $this->convertEntityConfigToArray($event));
    $form_config = $this->convertArrayLowercaseSorted(
      (array) $this->convertFormConfigToArray($form_state));
    return !(serialize($entity_config) === serialize($form_config));
  }

  /**
   * Check whether there have been original recurring configuration changes.
   *
   * @param Drupal\recurring_events\Entity\EventSeries $event
   *   The stored event series entity.
   * @param Drupal\recurring_events\Entity\EventSeries $original
   *   The original stored event series entity.
   *
   * @return bool
   *   TRUE if recurring config changes, FALSE otherwise.
   */
  public function checkForOriginalRecurConfigChanges(EventSeries $event, EventSeries $original) {
    $entity_config = $this->convertArrayLowercaseSorted(
      (array) $this->convertEntityConfigToArray($event));
    $original_config = $this->convertArrayLowercaseSorted(
      (array) $this->convertEntityConfigToArray($original));
    return !(serialize($entity_config) === serialize($original_config));
  }

  /**
   * Converts an EventSeries entity's recurring configuration to an array.
   *
   * @param Drupal\recurring_events\Entity\EventSeries $event
   *   The stored event series entity.
   *
   * @return array
   *   The recurring configuration as an array.
   */
  public function convertEntityConfigToArray(EventSeries $event) {
    $config = [];
    $config['type'] = $event->getRecurType();
    $config['excluded_dates'] = $event->getExcludedDates();
    $config['included_dates'] = $event->getIncludedDates();

    if ($config['type'] === 'custom') {
      $config['custom_dates'] = $event->getCustomDates();
    }
    else {
      $field_definition = $this->fieldTypePluginManager->getDefinition($config['type']);
      $field_class = $field_definition['class'];
      $config += $field_class::convertEntityConfigToArray($event);
    }

    $this->moduleHandler->alter('recurring_events_entity_config_array', $config);

    return $config;
  }

  /**
   * Converts a form state object's recurring configuration to an array.
   *
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of an updated event series entity.
   *
   * @return array
   *   The recurring configuration as an array.
   */
  public function convertFormConfigToArray(FormStateInterface $form_state) {
    $config = [];

    $user_timezone = new \DateTimeZone(date_default_timezone_get());
    $utc_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
    $user_input = $form_state->getValues();

    $config['type'] = $user_input['recur_type'][0]['value'];

    $config['excluded_dates'] = [];
    if (!empty($user_input['excluded_dates'])) {
      $config['excluded_dates'] = $this->getDatesFromForm($user_input['excluded_dates']);
    }

    $config['included_dates'] = [];
    if (!empty($user_input['included_dates'])) {
      $config['included_dates'] = $this->getDatesFromForm($user_input['included_dates']);
    }

    if ($config['type'] === 'custom') {
      foreach ($user_input['custom_date'] as $key => $custom_date) {
        if (!is_numeric($key)) {
          continue;
        }
        $start_date = $end_date = NULL;

        if (!empty($custom_date['value']) && !empty($custom_date['end_value'])) {

          $start_timestamp = $custom_date['value']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
          $end_timestamp = $custom_date['end_value']->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
          $start_date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $start_timestamp, $utc_timezone);
          $end_date = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $end_timestamp, $utc_timezone);

          $config['custom_dates'][] = [
            'start_date' => $start_date,
            'end_date' => $end_date,
          ];
        }
      }
    }
    else {
      $field_definition = $this->fieldTypePluginManager->getDefinition($config['type']);
      $field_class = $field_definition['class'];
      $config += $field_class::convertFormConfigToArray($form_state);
    }

    $this->moduleHandler->alter('recurring_events_form_config_array', $config);

    return $config;
  }

  /**
   * Normalize an array for equality checks, without having to worry about order
   * or casing discrepencies.
   *
   * @param array $input
   *   The array to clean and sort.
   *
   * @return array
   *   A cleaned array.
   */
  public static function convertArrayLowercaseSorted(array $input) {
    foreach ($input as $key => $val) {
      if (is_object($val)) {
        $input[$key] = self::convertArrayLowercaseSorted((array) $val);
      }
      if (is_array($val)) {
        $input[$key] = self::convertArrayLowercaseSorted($val);
      }
      if (is_string($val)) {
        $input[$key] = strtolower($val);
      }
    }
    uksort($input, 'strcmp');
    return $input;
  }

  /**
   * Build diff array between stored entity and form state.
   *
   * @param Drupal\recurring_events\Entity\EventSeries $event
   *   The stored event series entity.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   (Optional) The form state of an updated event series entity.
   * @param Drupal\recurring_events\Entity\EventSeries $edited
   *   (Optional) The edited event series entity.
   *
   * @return array
   *   An array of differences.
   */
  public function buildDiffArray(EventSeries $event, FormStateInterface $form_state = NULL, EventSeries $edited = NULL) {
    $diff = [];

    $entity_config = $this->convertEntityConfigToArray($event);
    $form_config = [];

    if (!is_null($form_state)) {
      $form_config = $this->convertFormConfigToArray($form_state);
    }
    if (!is_null($edited)) {
      $form_config = $this->convertEntityConfigToArray($edited);
    }

    if (empty($form_config)) {
      return $diff;
    }

    if ($entity_config['type'] !== $form_config['type']) {
      $diff['type'] = [
        'label' => $this->translation->translate('Recur Type'),
        'stored' => $entity_config['type'],
        'override' => $form_config['type'],
      ];
    }
    else {
      if ($entity_config['excluded_dates'] !== $form_config['excluded_dates']) {
        $entity_dates = $this->buildDateString($entity_config['excluded_dates']);
        $config_dates = $this->buildDateString($form_config['excluded_dates']);
        $diff['excluded_dates'] = [
          'label' => $this->translation->translate('Excluded Dates'),
          'stored' => $entity_dates,
          'override' => $config_dates,
        ];
      }
      if ($entity_config['included_dates'] !== $form_config['included_dates']) {
        $entity_dates = $this->buildDateString($entity_config['included_dates']);
        $config_dates = $this->buildDateString($form_config['included_dates']);
        $diff['included_dates'] = [
          'label' => $this->translation->translate('Included Dates'),
          'stored' => $entity_dates,
          'override' => $config_dates,
        ];
      }

      if ($entity_config['type'] === 'custom') {
        if ($entity_config['custom_dates'] !== $form_config['custom_dates']) {
          $stored_start_ends = $overridden_start_ends = [];

          $user_timezone = new \DateTimeZone(date_default_timezone_get());

          foreach ($entity_config['custom_dates'] as $date) {
            if (!empty($date['start_date']) && !empty($date['end_date'])) {
              $date['start_date']->setTimezone($user_timezone);
              $date['end_date']->setTimezone($user_timezone);
              $stored_start_ends[] = $date['start_date']->format('Y-m-d h:ia') . ' - ' . $date['end_date']->format('Y-m-d h:ia');
            }
          }

          foreach ($form_config['custom_dates'] as $date) {
            if (!empty($date['start_date']) && !empty($date['end_date'])) {
              $date['start_date']->setTimezone($user_timezone);
              $date['end_date']->setTimezone($user_timezone);
              $overridden_start_ends[] = $date['start_date']->format('Y-m-d h:ia') . ' - ' . $date['end_date']->format('Y-m-d h:ia');
            }
          }

          $diff['custom_dates'] = [
            'label' => $this->translation->translate('Custom Dates'),
            'stored' => implode(', ', $stored_start_ends),
            'override' => implode(', ', $overridden_start_ends),
          ];
        }
      }
      else {
        $field_definition = $this->fieldTypePluginManager->getDefinition($entity_config['type']);
        $field_class = $field_definition['class'];
        $diff += $field_class::buildDiffArray($entity_config, $form_config);
      }
    }

    $this->moduleHandler->alter('recurring_events_diff_array', $diff);

    return $diff;
  }

  /**
   * Clear out existing event instances..
   *
   * @param Drupal\recurring_events\Entity\EventSeries $event
   *   The event series entity.
   */
  public function clearEventInstances(EventSeries $event) {
    // Allow other modules to react prior to the deletion of all instances.
    $this->moduleHandler->invokeAll('recurring_events_save_pre_instances_deletion', [
      $event
    ]);

    // Find all the instances and delete them.
    $instances = $event->event_instances->referencedEntities();
    if (!empty($instances)) {
      foreach ($instances as $instance) {
        // Allow other modules to react prior to deleting a specific
        // instance after a date configuration change.
        $this->moduleHandler->invokeAll('recurring_events_save_pre_instance_deletion', [
          $event,
          $instance,
        ]);

        $instance->delete();

        // Allow other modules to react after deleting a specific instance
        // after a date configuration change.
        $this->moduleHandler->invokeAll('recurring_events_save_post_instance_deletion', [
          $event,
          $instance,
        ]);
      }
      $this->messenger->addStatus($this->translation->translate('A total of %count existing event instances were removed', [
        '%count' => count($instances),
      ]));
    }

    // Allow other modules to react after the deletion of all instances.
    $this->moduleHandler->invokeAll('recurring_events_save_post_instances_deletion', [
      $event
    ]);

    $this->entityTypeManager->getStorage('eventseries')->resetCache([$event->id()]);
  }

  /**
   * Create the event instances from the form state.
   *
   * @param Drupal\recurring_events\Entity\EventSeries $event
   *   The stored event series entity.
   */
  public function createInstances(EventSeries $event) {
    $form_data = $this->convertEntityConfigToArray($event);
    $event_instances = [];

    if (!empty($form_data['type'])) {
      if ($form_data['type'] === 'custom') {
        if (!empty($form_data['custom_dates'])) {
          $events_to_create = [];
          foreach ($form_data['custom_dates'] as $date_range) {
            // Set this event to be created.
            $events_to_create[$date_range['start_date']->format('r')] = [
              'start_date' => $date_range['start_date'],
              'end_date' => $date_range['end_date'],
            ];
          }

          // Allow modules to alter the array of event instances before they
          // get created.
          $this->moduleHandler->alter('recurring_events_event_instances_pre_create', $events_to_create, $event);

          if (!empty($events_to_create)) {
            foreach ($events_to_create as $custom_event) {
              $instance = $this->createEventInstance($event, $custom_event['start_date'], $custom_event['end_date']);
              $this->configureDefaultInheritances($instance, $event->id());
              $event_instances[] = $instance;
            }
          }
        }
      }
      else {
        $field_definition = $this->fieldTypePluginManager->getDefinition($form_data['type']);
        $field_class = $field_definition['class'];
        $events_to_create = $field_class::calculateInstances($form_data);

        // Allow modules to alter the array of event instances before they
        // get created.
        $this->moduleHandler->alter('recurring_events_event_instances_pre_create', $events_to_create, $event);

        if (!empty($events_to_create)) {
          foreach ($events_to_create as $event_to_create) {
            $instance = $this->createEventInstance($event, $event_to_create['start_date'], $event_to_create['end_date']);
            $this->configureDefaultInheritances($instance, $event->id());
            $event_instances[] = $instance;
          }
        }
      }
    }

    // Create a message to indicate how many instances were changed.
    $this->messenger->addMessage($this->translation->translate('A total of %items event instances were created as part of this event series.', [
      '%items' => count($event_instances),
    ]));
  }

  /**
   * Create an event instance from an event series.
   *
   * @param Drupal\recurring_events\Entity\EventSeries $event
   *   The stored event series entity.
   * @param Drupal\Core\Datetime\DrupalDateTime $start_date
   *   The start date and time of the event.
   * @param Drupal\Core\Datetime\DrupalDateTime $end_date
   *   The end date and time of the event.
   *
   * @return \Drupal\recurring_events\Entity\EventInstance
   *   The created event instance entity object.
   */
  public function createEventInstance(EventSeries $event, DrupalDateTime $start_date, DrupalDateTime $end_date) {
    $data = [
      'eventseries_id' => $event->id(),
      'date' => [
        'value' => $start_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
        'end_value' => $end_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
      ],
      'type' => $event->getType(),
    ];

    $this->moduleHandler->alter('recurring_events_event_instance', $data);

    $entity = $this->entityTypeManager->getStorage('eventinstance')->create($data);
    $entity->save();

    return $entity;
  }

  /**
   * Configure the default field inheritances for event instances.
   *
   * @param Drupal\recurring_events\Entity\EventInstance $instance
   *   The event instance.
   * @param int $series_id
   *   The event series entity ID.
   */
  public function configureDefaultInheritances(EventInstance $instance, int $series_id = NULL) {
    if (is_null($series_id)) {
      $series_id = $instance->eventseries_id->target_id;
    }

    if (!empty($series_id)) {
      // Configure the field inheritances for this instance.
      $entity_type = $instance->getEntityTypeId();
      $bundle = $instance->bundle();

      $inherited_fields = $this->entityTypeManager->getStorage('field_inheritance')->loadByProperties([
        'sourceEntityType' => 'eventseries',
        'destinationEntityType' => $entity_type,
        'destinationEntityBundle' => $bundle,
      ]);

      if (!empty($inherited_fields)) {
        $state_key = $entity_type . ':' . $instance->uuid();
        $state = $this->keyValueStore->get('field_inheritance');
        $state_values = $state->get($state_key);
        if (empty($state_values)) {
          $state_values = [
            'enabled' => TRUE,
          ];
          if (!empty($inherited_fields)) {
            foreach ($inherited_fields as $inherited_field) {
              $name = $inherited_field->idWithoutTypeAndBundle();
              $state_values[$name] = [
                'entity' => $series_id,
              ];
            }
          }
          $state->set($state_key, $state_values);
        }
      }
    }
  }

  /**
   * When adding a new field inheritance, add the default values for it.
   *
   * @param Drupal\recurring_events\Entity\EventInstance $instance
   *   The event instance for which to configure default inheritance values.
   * @param Drupal\field_inheritance\Entity\FieldInheritanceInterface $field_inheritance
   *   The field inheritance being created or updated.
   */
  public function addNewDefaultInheritance(EventInstance $instance, FieldInheritanceInterface $field_inheritance) {
    $state_key = 'eventinstance:' . $instance->uuid();
    $state = $this->keyValueStore->get('field_inheritance');
    $state_values = $state->get($state_key);
    $name = $field_inheritance->idWithoutTypeAndBundle();

    if (!empty($state_values[$name])) {
      return;
    }

    $state_values[$name] = [
      'entity' => $instance->eventseries_id->target_id,
    ];

    $state->set($state_key, $state_values);
  }

  /**
   * Get exclude/include dates from form.
   *
   * @param array $field
   *   The field from which to retrieve the dates.
   *
   * @return array
   *   An array of dates.
   */
  private function getDatesFromForm(array $field) {
    $dates = [];

    if (!empty($field)) {
      foreach ($field as $key => $date) {
        if (!is_numeric($key)) {
          continue;
        }
        if (!empty($date['value']) && !empty($date['end_value'])) {
          $dates[] = [
            'value' => $date['value']->format('Y-m-d'),
            'end_value' => $date['end_value']->format('Y-m-d'),
          ];
        }
      }
    }
    return $dates;
  }

  /**
   * Build a string from excluded or included date ranges.
   *
   * @var array $config
   *   The configuration from which to build a string.
   *
   * @return string
   *   The formatted date string.
   */
  private function buildDateString(array $config) {
    $string = '';

    $string_parts = [];
    if (!empty($config)) {
      foreach ($config as $date) {
        $range = $this->translation->translate('@start_date to @end_date', [
          '@start_date' => $date['value'],
          '@end_date' => $date['end_value'],
        ]);
        $string_parts[] = '(' . $range . ')';
      }

      $string = implode(', ', $string_parts);
    }
    return $string;
  }

  /**
   * Retrieve the recur field types.
   *
   * @param bool $allow_alter
   *   Allow altering of the field types.
   *
   * @return array
   *   An array of field types.
   */
  public function getRecurFieldTypes($allow_alter = TRUE) {
    // Build an array of recur type field options based on FieldTypes that
    // implement the Drupal\recurring_events\RecurringEventsFieldTypeInterface
    // interface. Allow for other modules to customize this list with an alter
    // hook.
    $recur_fields = [];
    $fields = $this->entityFieldManager->getBaseFieldDefinitions('eventseries');
    foreach ($fields as $field) {
      $field_definition = $this->fieldTypePluginManager->getDefinition($field->getType());
      $class = new \ReflectionClass($field_definition['class']);
      if ($class->implementsInterface('\Drupal\recurring_events\RecurringEventsFieldTypeInterface')) {
        $recur_fields[$field->getName()] = $field->getLabel();
      }
    }

    $recur_fields['custom'] = $this->t('Custom/Single Event');
    if ($allow_alter) {
      $this->moduleHandler->alter('recurring_events_recur_field_types', $recur_fields);
    }
    return $recur_fields;
  }

  /**
   * Update instance status.
   *
   * @param Drupal\recurring_events\Entity\EventInstance $instance
   *   The event instance for which to update the status.
   * @param Drupal\recurring_events\Entity\EventSeries $event
   *   The event series entity.
   */
  public function updateInstanceStatus(EventInstance $instance, EventSeries $event) {
    $original_event = $event->original;
    $field_name = 'status';

    if ($this->moduleHandler->moduleExists('workflows')) {
      if ($event->hasField('moderation_state') && $instance->hasField('moderation_state')) {
        $series_query = $this->entityTypeManager->getStorage('workflow')->getQuery();
        $series_query->condition('type_settings.entity_types.eventseries.*', $event->bundle());
        $series_workflows = $series_query->execute();
        $series_workflows = array_keys($series_workflows);
        $series_workflow = reset($series_workflows);

        $instance_query = $this->entityTypeManager->getStorage('workflow')->getQuery();
        $instance_query->condition('type_settings.entity_types.eventinstance.*', $instance->bundle());
        $instance_workflows = $instance_query->execute();
        $instance_workflows = array_keys($instance_workflows);
        $instance_workflow = reset($instance_workflows);

        // We only want to mimic moderation state if the series and instance use
        // the same workflows, otherwise we cannot guarantee the states match.
        if ($instance_workflow === $series_workflow) {
          $field_name = 'moderation_state';
        }
        else {
          return FALSE;
        }
      }
    }

    $new_state = $event->get($field_name)->getValue();
    $instance_state = $instance->get($field_name)->getValue();

    if (!empty($original_event)) {
      $original_state = $original_event->get($field_name)->getValue();
    }
    else {
      $instance->set($field_name, $new_state);
      return TRUE;
    }

    // If the instance state matches the original state of the series we want
    // to also update the instance state.
    if ($instance_state === $original_state) {
      $instance->set($field_name, $new_state);
      return TRUE;
    }

    return FALSE;

  }

}
