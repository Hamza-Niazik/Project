<?php

namespace Drupal\recurring_events_registration;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\recurring_events\Entity\EventInstance;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\Core\Messenger\Messenger;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a service with helper functions for registration creation.
 */
class RegistrationCreationService {

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
   * The entity storage for registrants.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Event instance entity.
   *
   * @var \Drupal\recurring_events\Entity\EventInstance
   */
  protected $eventInstance;

  /**
   * Event series entity.
   *
   * @var \Drupal\recurring_events\Entity\EventSeries
   */
  protected $eventSeries;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(TranslationInterface $translation, Connection $database, LoggerChannelFactoryInterface $logger, Messenger $messenger, EntityTypeManagerInterface $entity_type_manager, ModuleHandler $module_handler, Token $token) {
    $this->translation = $translation;
    $this->database = $database;
    $this->loggerFactory = $logger->get('recurring_events_registration');
    $this->messenger = $messenger;
    $this->storage = $entity_type_manager->getStorage('registrant');
    $this->moduleHandler = $module_handler;
    $this->token = $token;
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
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('token')
    );
  }

  /**
   * Set the event entities.
   *
   * @param Drupal\recurring_events\Entity\EventInstance $event_instance
   *   The event instance.
   */
  public function setEventInstance(EventInstance $event_instance) {
    $this->eventInstance = $event_instance;
    $this->eventSeries = $event_instance->getEventSeries();
  }

  /**
   * Set the event series, helpful to get a fresh copy of the entity.
   *
   * @param Drupal\recurring_events\Entity\EventSeries $event_series
   *   The event series.
   */
  public function setEventSeries(EventSeries $event_series) {
    $this->eventSeries = $event_series;
  }

  /**
   * Get the event instance.
   *
   * @return Drupal\recurring_events\Entity\EventInstance
   *   The event instance.
   */
  public function getEventInstance() {
    return $this->eventInstance;
  }

  /**
   * Get the event series.
   *
   * @return Drupal\recurring_events\Entity\EventSeries
   *   The event series.
   */
  public function getEventSeries() {
    return $this->eventSeries;
  }

  /**
   * Retreive all registered parties.
   *
   * @param bool $include_nonwaitlisted
   *   Whether or not to include non-waitlisted registrants.
   * @param bool $include_waitlisted
   *   Whether or not to include waitlisted registrants.
   * @param int $uid
   *   The user ID for whom to retrieve registrants.
   *
   * @return array
   *   An array of registrants.
   */
  public function retrieveRegisteredParties($include_nonwaitlisted = TRUE, $include_waitlisted = TRUE, $uid = FALSE) {
    $parties = [];
    $properties = [];

    if ($include_nonwaitlisted && !$include_waitlisted) {
      $properties['waitlist'] = 0;
    }
    elseif (!$include_nonwaitlisted && $include_waitlisted) {
      $properties['waitlist'] = 1;
    }

    if (!$include_waitlisted) {
      $properties['waitlist'] = 0;
    }

    if ($uid) {
      $properties['user_id'] = $uid;
    }

    switch ($this->getRegistrationType()) {
      case 'series':
        if (!empty($this->eventSeries->id())) {
          $properties['eventseries_id'] = $this->eventSeries->id();
        }
        break;

      case 'instance':
        if (!empty($this->eventInstance->id())) {
          $properties['eventinstance_id'] = $this->eventInstance->id();
        }
        break;
    }
    $results = $this->storage->loadByProperties($properties);

    if (!empty($results)) {
      $parties = $results;
    }
    return $parties;
  }

  /**
   * Retreive all registered parties for a series.
   *
   * @param bool $future_only
   *   Whether to only return registered parties for future events.
   *
   * @return array
   *   An array of registrants.
   */
  public function retrieveAllSeriesRegisteredParties($future_only = FALSE) {
    $parties = [];

    if (($future_only && $this->eventSeriesHasFutureInstances()) || !$future_only) {
      $properties = [
        'eventseries_id' => $this->eventSeries->id(),
      ];

      $results = $this->storage->loadByProperties($properties);

      if (!empty($results)) {
        $parties = $results;
      }
    }
    return $parties;
  }

  /**
   * Check if this event series has events in the future.
   *
   * @return bool
   *   Whether the event series has future instances or not.
   */
  public function eventSeriesHasFutureInstances() {
    $future_instances = FALSE;

    $instances = $this->eventSeries->event_instances->referencedEntities();
    if (!empty($instances)) {
      foreach ($instances as $instance) {
        $end_date = $instance->date->end_date->getTimestamp();
        if ($end_date > time()) {
          return TRUE;
        }
      }
    }

    return $future_instances;
  }

  /**
   * Check if this event instance is a future event.
   *
   * @return bool
   *   Whether the event instance is in the future.
   */
  public function eventInstanceIsInFuture() {
    return $this->eventInstance->date->end_date->getTimestamp() > time();
  }

  /**
   * Get registration availability.
   *
   * @return int
   *   The number of spaces available for registration.
   */
  public function retrieveAvailability() {
    $availability = 0;
    $parties = $this->retrieveRegisteredParties(TRUE, FALSE);

    $capacity = $this->eventSeries->event_registration->capacity;
    if (empty($capacity)) {
      // Set capacity to unlimited if no capacity is specified.
      return -1;
    }
    $availability = $capacity - count($parties);
    if ($availability < 0) {
      $availability = 0;
    }
    return $availability;
  }

  /**
   * Get whether this event has a waitlist.
   *
   * @return bool
   *   Whether or not there is a waitlist for this event.
   */
  public function hasWaitlist() {
    $waitlist = FALSE;
    if (!empty($this->eventSeries->event_registration->waitlist)) {
      $waitlist = (bool) $this->eventSeries->event_registration->waitlist;
    }
    return $waitlist;
  }

  /**
   * Get whether this event has registration.
   *
   * @return bool
   *   Whether or not registration is open for this event.
   */
  public function hasRegistration() {
    $registration = FALSE;
    if (!empty($this->eventSeries->event_registration->registration)) {
      $registration = (bool) $this->eventSeries->event_registration->registration;
    }
    return $registration;
  }

  /**
   * Get registration date range.
   *
   * @return array
   *   The registration date range array.
   */
  public function getRegistrationDateRange() {
    $date_range = [];

    $value = $this->eventSeries->event_registration->getValue();
    if (!empty($value)) {
      $date_range['value'] = $value[array_key_first($value)]['value'];
      $date_range['end_value'] = $value[array_key_first($value)]['end_value'];
    }

    return $date_range;
  }

  /**
   * Has the user registered for this event before.
   *
   * @param int $uid
   *   The ID of the user.
   *
   * @return bool
   *   Whether this user has already registered for this event.
   */
  public function hasUserRegisteredById($uid) {
    $registrants = $this->retrieveRegisteredParties(TRUE, TRUE, $uid);
    return !empty($registrants);
  }

  /**
   * Retreive all waitlisted users.
   *
   * @return array
   *   An array of Drupal\recurring_events_registration\Entity\Registrant users.
   */
  public function retrieveWaitlistedParties() {
    $parties = [];
    $registrants = $this->retrieveRegisteredParties(FALSE, TRUE);
    if (!empty($registrants)) {
      $parties = $registrants;
    }
    return $parties;
  }

  /**
   * Retreive first user on the waitlist.
   *
   * @return \Drupal\recurring_events_registration\Entity\RegistrantInterface
   *   A fully loaded registrant entity.
   */
  public function retrieveFirstWaitlistParty() {
    $waitlisted_users = $this->retrieveWaitlistedParties();
    if (!empty($waitlisted_users)) {
      /** @var Drupal\recurring_events_registration\Entity\RegistrantInterface */
      $first = reset($waitlisted_users);
      $this->moduleHandler->alter('recurring_events_registration_first_waitlist', $first);
      return $first;
    }
    return NULL;
  }

  /**
   * Get registration type.
   *
   * @return string
   *   The type of registration: series, or instance.
   */
  public function getRegistrationType() {
    $type = FALSE;

    if (!empty($this->eventSeries->event_registration->registration_type)) {
      $type = $this->eventSeries->event_registration->registration_type;
    }

    return $type;
  }

  /**
   * Get instance registration open schedule type.
   *
   * @return string
   *   The type of open registration schedule: now, start, or custom.
   */
  public function getInstanceRegistrationOpenScheduleType() {
    $type = FALSE;

    if (!empty($this->eventSeries->event_registration->instance_schedule_open)) {
      $type = $this->eventSeries->event_registration->instance_schedule_open;
    }

    return $type;
  }

  /**
   * Get instance registration close schedule type.
   *
   * @return string
   *   The type of close registration schedule: start, end, or custom.
   */
  public function getInstanceRegistrationCloseScheduleType() {
    $type = FALSE;

    if (!empty($this->eventSeries->event_registration->instance_schedule_close)) {
      $type = $this->eventSeries->event_registration->instance_schedule_close;
    }

    return $type;
  }

  /**
   * Get registration dates type.
   *
   * @return string
   *   The type of registration dates: open, or scheduled.
   */
  public function getRegistrationDatesType() {
    $type = FALSE;

    if (!empty($this->eventSeries->event_registration->registration_dates)) {
      $type = $this->eventSeries->event_registration->registration_dates;
    }

    return $type;
  }

  /**
   * Get instance registration open time modifier.
   *
   * @return string
   *   The modifier for the opening time relative to the event start.
   */
  public function getInstanceRegistrationOpenTimeModifier() {
    $modifier = FALSE;

    if (!empty($this->eventSeries->event_registration->instance_schedule_open_amount) && !empty($this->getInstanceRegistrationOpenTimeUnit())) {
      $modifier = $this->eventSeries->event_registration->instance_schedule_open_amount . ' ' . $this->getInstanceRegistrationOpenTimeUnit();
      $modifier = '- ' . $modifier;
    }

    return $modifier;
  }

  /**
   * Get instance registration open time unit.
   *
   * @return string
   *   The unit used to define the open registration time.
   */
  public function getInstanceRegistrationOpenTimeUnit() {
    $unit = FALSE;

    if (!empty($this->eventSeries->event_registration->instance_schedule_open_units)) {
      $unit = $this->eventSeries->event_registration->instance_schedule_open_units;
    }

    return $unit;
  }

  /**
   * Get instance registration close time modifier.
   *
   * @return string
   *   The modifier for the closing time relative to the event start.
   */
  public function getInstanceRegistrationCloseTimeModifier() {
    $modifier = FALSE;

    if (!empty($this->eventSeries->event_registration->instance_schedule_close_amount) && !empty($this->getInstanceRegistrationCloseTimeUnit())) {
      $modifier = $this->eventSeries->event_registration->instance_schedule_close_amount . ' ' . $this->getInstanceRegistrationCloseTimeUnit();
      switch ($this->eventSeries->event_registration->instance_schedule_close_type) {
        case 'after':
          $modifier = '+ ' . $modifier;
          break;

        case 'before':
        default:
          $modifier = '- ' . $modifier;
          break;
      }
    }

    return $modifier;
  }

  /**
   * Get instance registration close time unit.
   *
   * @return string
   *   The unit used to define the close registration time.
   */
  public function getInstanceRegistrationCloseTimeUnit() {
    $unit = FALSE;

    if (!empty($this->eventSeries->event_registration->instance_schedule_close_units)) {
      $unit = $this->eventSeries->event_registration->instance_schedule_close_units;
    }

    return $unit;
  }

  /**
   * Is registration open for this event?
   *
   * @return bool
   *   Whether or not registration is open for this event.
   */
  public function registrationIsOpen() {
    $registration = FALSE;
    if ($this->hasRegistration()) {
      $now = new DrupalDateTime();

      $reg_open_close_dates = $this->registrationOpeningClosingTime();

      if (!empty($reg_open_close_dates)) {
        $registration = (
          $now->getTimestamp() >= $reg_open_close_dates['reg_open']->getTimestamp()
          && $now->getTimestamp() < $reg_open_close_dates['reg_close']->getTimestamp()
        );
      }
    }
    return $registration;
  }

  /**
   * Get registration opening date and time.
   *
   * @return array
   *   An array of drupal date time objects for when registration opens/closes.
   */
  public function registrationOpeningClosingTime() {
    $reg_dates = FALSE;

    // Does this event even have registration?
    if ($this->hasRegistration()) {
      // Grab the type of registration and the type of dates.
      $reg_type = $this->getRegistrationType();
      $reg_dates_type = $this->getRegistrationDatesType();

      $timezone = new \DateTimeZone(date_default_timezone_get());
      $utc_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);

      $now = new DrupalDateTime();

      switch ($reg_dates_type) {
        case 'open':
          // For series, the event registration should close when the first
          // event in that series begins. For instance registration the event
          // registration should close when that instance begins.
          switch ($reg_type) {
            case 'series':
              $event_date = $this->eventSeries->getSeriesStart();
              break;

            case 'instance':
              $event_date = $this->eventInstance->date->start_date;
              break;
          }

          $event_date->setTimezone($timezone);

          $reg_dates = [
            'reg_open' => $now,
            'reg_close' => $event_date,
          ];
          break;

        case 'scheduled':
          // The two registration types are 'series' or 'instance'.
          switch ($reg_type) {
            case 'series':
              $reg_date_range = $this->getRegistrationDateRange();

              if (!empty($reg_date_range)) {
                if (!empty($reg_date_range['value'])) {
                  $reg_start = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $reg_date_range['value'], $utc_timezone);
                  $reg_start->setTimezone($timezone);
                }
                if (!empty($reg_date_range['end_value'])) {
                  $reg_end = DrupalDateTime::createFromFormat(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $reg_date_range['end_value'], $utc_timezone);
                  $reg_end->setTimezone($timezone);
                }
              }
              break;

            case 'instance':
              $event_start_date = $this->eventInstance->date->start_date;
              $event_end_date = $this->eventInstance->date->end_date;

              // Calculate registration opening time.
              switch ($this->getInstanceRegistrationOpenScheduleType()) {
                case 'now':
                  $reg_start = new DrupalDateTime();
                  break;

                case 'start':
                  $reg_start = clone $event_start_date;
                  break;

                case 'custom':
                  $open_time_modifier = $this->getInstanceRegistrationOpenTimeModifier();
                  $reg_start = clone $event_start_date;
                  $reg_start->modify($open_time_modifier);
                  break;
              }

              // Calculate registration closing time.
              switch ($this->getInstanceRegistrationCloseScheduleType()) {
                case 'start':
                  $reg_end = clone $event_start_date;
                  break;

                case 'end':
                  $reg_end = clone $event_end_date;
                  break;

                case 'custom':
                  $close_time_modifier = $this->getInstanceRegistrationCloseTimeModifier();
                  $reg_end = clone $event_start_date;
                  $reg_end->modify($close_time_modifier);
                  break;
              }

              break;
          }

          $reg_dates = [
            'reg_open' => $reg_start,
            'reg_close' => $reg_end,
          ];
          break;
      }
    }
    return $reg_dates;
  }

  /**
   * Promote a registrant from the waitlist.
   */
  public function promoteFromWaitlist() {
    if (!$this->hasWaitlist()) {
      return;
    }

    if ($this->retrieveAvailability() > 0) {
      $first_waitlist = $this->retrieveFirstWaitlistParty();
      if (!empty($first_waitlist)) {
        $first_waitlist->setWaitlist('0');
        $first_waitlist->save();

        $key = 'promotion_notification';
        $reg_type = $this->getRegistrationType();
        $future_event = FALSE;
        switch ($reg_type) {
          case 'series':
            $future_event = $this->eventSeriesHasFutureInstances();
            break;

          case 'instance':
            $future_event = $this->eventInstanceIsInFuture();
            break;
        }
        if ($future_event) {
          recurring_events_registration_send_notification($key, $first_waitlist);
        }
      }
    }
  }

  /**
   * Retrieve the tokens available for a registrant.
   */
  public function getAvailableTokens($relevant_tokens = ['registrant']) {
    if ($this->moduleHandler->moduleExists('token')) {
      $token_help = [
        '#theme' => 'token_tree_link',
        '#token_types' => $relevant_tokens,
      ];
    }
    else {
      $all_tokens = $this->token->getInfo();
      $tokens = [];
      foreach ($relevant_tokens as $token_prefix) {
        if (!empty($all_tokens['tokens'][$token_prefix])) {
          foreach ($all_tokens['tokens'][$token_prefix] as $token_key => $value) {
            $tokens[] = '[' . $token_prefix . ':' . $token_key . ']';
          }
        }
      }

      $token_text = $this->translation->translate('Available tokens are: @tokens', [
        '@tokens' => implode(', ', $tokens),
      ]);

      $token_help = [
        '#type' => 'markup',
        '#markup' => $token_text->render(),
      ];
    }

    return $token_help;
  }

  /**
   * Has the email address registered for this event before.
   *
   * @param string $email
   *   The email address of the user.
   * @param int $ignored_registrant_id
   *   The ID of the registrant to ignore during duplicate email checks.
   *
   * @return bool|int
   *   Return the registration ID if it exists otherwise return FALSE.
   */
  public function hasUserRegisteredByEmail($email, $ignored_registrant_id = NULL) {
    // Look through this series' registrations.
    $existing_registration_id = FALSE;
    $current_series_registrations = $this->retrieveAllSeriesRegisteredParties();
    foreach ($current_series_registrations as $registration_id => $registration_record) {
      if ($registration_id === $ignored_registrant_id) {
        continue;
      }
      if ($this->cleanEmailAddress($email) == $this->cleanEmailAddress($registration_record->get('email')->value)) {
        if ($this->getRegistrationType() === 'instance') {
          // Compare the event instance ID and email address.
          if (($this->eventInstance->id() == $registration_record->get('eventinstance_id')->target_id)) {
            // Remember the existing registration ID and stop looking.
            $existing_registration_id = $registration_id;
            break;
          }
        }
        else {
          $existing_registration_id = $registration_id;
          break;
        }
      }
    }
    return $existing_registration_id;
  }

  /**
   * Clean the email address to handle plus- and dot-addressing.
   *
   * @param string $email
   *   The email address of the user.
   *
   * @return string
   *   The cleaned email address
   */
  public function cleanEmailAddress($email) {
    $email_address_parts = (isset($email) ? explode('@', $email) : ['', '']);
    $email_address_clean = str_replace('.', '', $email_address_parts[0]) . '@' . $email_address_parts[1];
    $email_address_clean = preg_replace('/\+.*@/', '@', $email_address_clean);
    return (isset($email) ? strtolower($email_address_clean) : NULL);
  }

  /**
   * Do email addresses have to be unique for this event?
   *
   * @return bool
   *   Whether or not unique email addresses are enforced for this event.
   */
  public function registrationUniqueEmailAddress() {
    return $this->eventSeries->event_registration->unique_email_address;
  }

  /**
   * Which roles are allowed to register for this event? Comma delimited.
   *
   * @return array
   *   An array of roles that are allowed to register for this event.
   */
  public function registrationPermittedRoles() {
    // Remove extra spaces from the list of roles
    $permitted_roles_string = str_replace(' ', '', $this->eventSeries->event_registration->permitted_roles);

    // Convert the string into an array of roles
    $permitted_roles = [];
    if (!empty($permitted_roles_string)) {
      if (strpos($permitted_roles_string, ','))
        $permitted_roles = explode(',', $permitted_roles_string);
      else {
        $permitted_roles[] = $permitted_roles_string;
      }
    }
    return $permitted_roles;
  }


}
