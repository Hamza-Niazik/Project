<?php

namespace Drupal\recurring_events_reminders\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\text\Plugin\Field\FieldType\TextLongItem;

/**
 * Plugin implementation of the 'registration_reminder' field type.
 *
 * @FieldType (
 *   id = "registration_reminders",
 *   label = @Translation("Event Registration Reminders"),
 *   description = @Translation("Stores an event registration reminders configuration"),
 *   default_widget = "registration_reminders",
 *   default_formatter = "registration_reminders"
 * )
 */
class RegistrationReminders extends TextLongItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['reminder'] = [
      'type' => 'int',
      'default' => 0,
      'unsigned' => TRUE,
    ];

    $schema['columns']['reminder_amount'] = [
      'type' => 'int',
      'default' => 0,
      'unsigned' => TRUE,
    ];

    $schema['columns']['reminder_units'] = [
      'type' => 'varchar',
      'length' => 255,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $reminder = $this->get('reminder')->getValue();
    $reminder_amount = $this->get('reminder_amount')->getValue();
    $reminder_units = $this->get('reminder_units')->getValue();

    return parent::isEmpty() &&
      empty($reminder) &&
      empty($reminder_amount) &&
      empty($reminder_units);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['reminder'] = DataDefinition::create('boolean')
      ->setLabel(t('Enable Email Reminder'))
      ->setDescription(t('Select whether to enable email reminders for registrants.'));

    $properties['reminder_amount'] = DataDefinition::create('integer')
      ->setLabel(t('Reminder Time'))
      ->setDescription(t('Select when the reminder should be sent out.'));

    $properties['reminder_units'] = DataDefinition::create('string')
      ->setLabel(t('Reminder Time Units'))
      ->setDescription(t('Select when the reminder should be sent out (units).'));

    return $properties;
  }

}
