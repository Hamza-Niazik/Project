<?php

namespace Drupal\recurring_events_registration\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;

/**
 * Plugin implementation of the 'event_registration' field type.
 *
 * @FieldType (
 *   id = "event_registration",
 *   label = @Translation("Event Registration"),
 *   description = @Translation("Stores an event registration configuration"),
 *   default_widget = "event_registration",
 *   default_formatter = "event_registration"
 * )
 */
class EventRegistration extends DateRangeItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['registration'] = [
      'type' => 'int',
      'default' => 0,
      'unsigned' => TRUE,
    ];

    $schema['columns']['registration_type'] = [
      'type' => 'varchar',
      'length' => 255,
    ];

    $schema['columns']['registration_dates'] = [
      'type' => 'varchar',
      'length' => 255,
    ];

    $schema['columns']['capacity'] = [
      'type' => 'int',
      'unsigned' => TRUE,
    ];

    $schema['columns']['waitlist'] = [
      'type' => 'int',
      'default' => 0,
      'unsigned' => TRUE,
    ];

    $schema['columns']['instance_schedule_open'] = [
      'type' => 'varchar',
      'length' => 255,
    ];

    $schema['columns']['instance_schedule_open_amount'] = [
      'type' => 'int',
      'default' => 0,
      'unsigned' => TRUE,
    ];

    $schema['columns']['instance_schedule_open_units'] = [
      'type' => 'varchar',
      'length' => 255,
    ];

    $schema['columns']['instance_schedule_close'] = [
      'type' => 'varchar',
      'length' => 255,
    ];

    $schema['columns']['instance_schedule_close_amount'] = [
      'type' => 'int',
      'default' => 0,
      'unsigned' => TRUE,
    ];

    $schema['columns']['instance_schedule_close_units'] = [
      'type' => 'varchar',
      'length' => 255,
    ];

    $schema['columns']['instance_schedule_close_type'] = [
      'type' => 'varchar',
      'length' => 255,
    ];

    $schema['columns']['unique_email_address'] = [
      'type' => 'int',
      'default' => 0,
      'unsigned' => TRUE,
    ];

    $schema['columns']['permitted_roles'] = [
      'type' => 'varchar',
      'length' => 1023,
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $registration = $this->get('registration')->getValue();
    $registration_type = $this->get('registration_type')->getValue();
    $registration_dates = $this->get('registration_dates')->getValue();
    $capacity = $this->get('capacity')->getValue();
    $waitlist = $this->get('waitlist')->getValue();
    $instance_schedule_open = $this->get('instance_schedule_open')->getValue();
    $instance_schedule_open_amount = $this->get('instance_schedule_open_amount')->getValue();
    $instance_schedule_open_units = $this->get('instance_schedule_open_units')->getValue();
    $instance_schedule_close = $this->get('instance_schedule_close')->getValue();
    $instance_schedule_close_amount = $this->get('instance_schedule_close_amount')->getValue();
    $instance_schedule_close_units = $this->get('instance_schedule_close_units')->getValue();
    $instance_schedule_close_type = $this->get('instance_schedule_close_type')->getValue();
    $unique_email_address = $this->get('unique_email_address')->getValue();
    $permitted_roles = $this->get('permitted_roles')->getValue();
    return parent::isEmpty() && empty($registration) && empty($registration_type)
      && empty($registration_dates) && empty($capacity) && empty($waitlist)
      && empty($instance_schedule_open) && empty($instance_schedule_open_amount)
      && empty($instance_schedule_open_units) && empty($instance_schedule_close)
      && empty($instance_schedule_close_amount) && empty($instance_schedule_close_units)
      && empty($instance_schedule_close_type) && empty($unique_email_address)
      && empty($permitted_roles);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    // We use the date fields for series registration only, so they do not need
    // to be required.
    $properties['value']->setRequired(FALSE);
    $properties['end_value']->setRequired(FALSE);

    $properties['registration'] = DataDefinition::create('boolean')
      ->setLabel(t('Enable Registration'))
      ->setDescription(t('Select whether to enable registration for this series.'));

    $properties['registration_type'] = DataDefinition::create('string')
      ->setLabel(t('Registration Type'))
      ->setDescription(t('Select which type of registration applies to this event.'));

    $properties['registration_dates'] = DataDefinition::create('string')
      ->setLabel(t('Registration Dates'))
      ->setDescription(t('Select whether to enable open or scheduled registration.'));

    $properties['capacity'] = DataDefinition::create('integer')
      ->setLabel(t('Capacity'))
      ->setDescription(t('Enter the number of registrants that can attend the event.'));

    $properties['waitlist'] = DataDefinition::create('boolean')
      ->setLabel(t('Waitlist'))
      ->setDescription(t('Select whether to enable a waitlist.'));

    $properties['instance_schedule_open'] = DataDefinition::create('string')
      ->setLabel(t('Instance Registration Open'))
      ->setDescription(t('Select when to open registrations.'));

    $properties['instance_schedule_open_amount'] = DataDefinition::create('integer')
      ->setLabel(t('Instance Registration Open Time'))
      ->setDescription(t('Select when to open registrations (number).'));

    $properties['instance_schedule_open_units'] = DataDefinition::create('string')
      ->setLabel(t('Instance Registration Open Unit'))
      ->setDescription(t('Select when to open registrations (units).'));

    $properties['instance_schedule_close'] = DataDefinition::create('string')
      ->setLabel(t('Instance Registration Close'))
      ->setDescription(t('Select when to close registrations.'));

    $properties['instance_schedule_close_amount'] = DataDefinition::create('integer')
      ->setLabel(t('Instance Registration Close Time'))
      ->setDescription(t('Select when to close registrations (number).'));

    $properties['instance_schedule_close_units'] = DataDefinition::create('string')
      ->setLabel(t('Instance Registration Close Unit'))
      ->setDescription(t('Select when to close registrations (units).'));

    $properties['instance_schedule_close_type'] = DataDefinition::create('string')
      ->setLabel(t('Instance Registration Close Type'))
      ->setDescription(t('Select when to close registrations (type).'));

    $properties['unique_email_address'] = DataDefinition::create('boolean')
      ->setLabel(t('Restrict registration to once per email address?'))
      ->setDescription(t('Select whether to prevent a single email address from registering multiple times for the same event.'));

    $properties['permitted_roles'] = DataDefinition::create('string')
    ->setLabel(t('Which roles can register for this series?'))
    ->setDescription(t('Provide a comma-separated list of machine names for the roles that have permission to register for this series.  Leave blank to allow anybody to register.'));

    return $properties;
  }

}
