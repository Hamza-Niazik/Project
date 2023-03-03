<?php

namespace Drupal\recurring_events_registration\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\recurring_events\Entity\EventInstance;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\recurring_events_registration\Plugin\Field\ComputedRegistrantTitleFieldItemList;

/**
 * Defines the Registrant entity.
 *
 * @ingroup recurring_events_registration
 *
 * @ContentEntityType(
 *   id = "registrant",
 *   label = @Translation("Registrant"),
 *   bundle_label = @Translation("Registrant type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\recurring_events_registration\RegistrantListBuilder",
 *     "views_data" = "Drupal\recurring_events_registration\Entity\RegistrantViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\recurring_events_registration\Form\RegistrantForm",
 *       "add" = "Drupal\recurring_events_registration\Form\RegistrantForm",
 *       "edit" = "Drupal\recurring_events_registration\Form\RegistrantForm",
 *       "delete" = "Drupal\recurring_events_registration\Form\RegistrantDeleteForm",
 *       "anon-edit" = "Drupal\recurring_events_registration\Form\RegistrantForm",
 *       "anon-delete" = "Drupal\recurring_events_registration\Form\RegistrantDeleteForm"
 *     },
 *     "access" = "Drupal\recurring_events_registration\RegistrantAccessControlHandler",
 *   },
 *   base_table = "registrant",
 *   revision_table = "registrant_revision",
 *   show_revision_ui = TRUE,
 *   translatable = FALSE,
 *   fieldable = TRUE,
 *   admin_permission = "administer registrant entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "label" = "title",
 *     "bundle" = "bundle",
 *     "status" = "status",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message"
 *   },
 *   links = {
 *     "canonical" = "/events/{eventinstance}/registrations/{registrant}",
 *     "edit-form" = "/events/{eventinstance}/registrations/{registrant}/edit",
 *     "delete-form" = "/events/{eventinstance}/registrations/{registrant}/delete",
 *     "anon-edit-form" = "/events/{eventinstance}/registrations/{registrant}/{uuid}/edit",
 *     "anon-delete-form" = "/events/{eventinstance}/registrations/{registrant}/{uuid}/delete"
 *   },
 *   bundle_entity_type = "registrant_type",
 *   field_ui_base_route = "entity.registrant_type.edit_form"
 * )
 */
class Registrant extends EditorialContentEntityBase implements RegistrantInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
      'bundle' => !empty(\Drupal::request()->attributes->get('eventinstance')) ? \Drupal::request()->attributes->get('eventinstance')->getType() : 'default',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    if (!$update) {
      $key = 'registration_notification';
      if ($this->getWaitlist()) {
        $key = 'waitlist_notification';
      }
      recurring_events_registration_send_notification($key, $this);
    }

    if ($update) {
      // if originally on waitlist and was promoted, send the promotion notification
      if ($this->original->getWaitlist() && !$this->getWaitlist()) {
        $key = 'promotion_notification';
        recurring_events_registration_send_notification($key, $this);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setRevisionable(TRUE)
      ->setDescription(t('The user ID of author of the Registrant entity.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
          'match_limit' => 10,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email Address'))
      ->setRevisionable(TRUE)
      ->setDescription(t('The email address of the registrant'))
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => -6,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the registrant entity.'))
      ->setReadOnly(TRUE);

    $fields['bundle'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Bundle'))
      ->setDescription(t('The registrant type.'))
      ->setSetting('target_type', 'registrant_type')
      ->setReadOnly(TRUE);

    $fields['eventseries_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Event Series ID'))
      ->setDescription(t('The ID of the eventseries entity.'))
      ->setSetting('target_type', 'eventseries');

    $fields['eventinstance_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Event Instance ID'))
      ->setDescription(t('The ID of the eventinstance entity.'))
      ->setSetting('target_type', 'eventinstance');

    $fields['waitlist'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Waitlist'))
      ->setDescription(t('Whether this registrant is waitlisted.'));

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The type of registration this is: series or instance'))
      ->setSettings([
        'default_value' => 'series',
        'max_length' => 255,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setRevisionable(TRUE)
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setReadOnly(TRUE)
      ->setComputed(TRUE)
      ->setClass(ComputedRegistrantTitleFieldItemList::class);

    $fields['status']
      ->setLabel(t('Status'))
      ->setDescription(t('Is this registration complete?'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 120,
      ])
      ->setDefaultValue(0)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

  /**
   * Get the event series.
   *
   * @return Drupal\recurring_events\Entity\EventSeries
   *   The event series entity.
   */
  public function getEventSeries() {
    return $this->get('eventseries_id')->entity;
  }

  /**
   * Set the event series ID.
   *
   * @param Drupal\recurring_events\Entity\EventSeries $event
   *   The event series entity.
   *
   * @return Drupal\recurring_events_registration\Entity\RegistrantInterface
   *   The registrant entity.
   */
  public function setEventSeries(EventSeries $event) {
    $this->set('eventseries_id', $event->id());
    return $this;
  }

  /**
   * Get the event.
   *
   * @return Drupal\recurring_events\Entity\EventInstance
   *   The eventinstance entity.
   */
  public function getEventInstance() {
    return $this->get('eventinstance_id')->entity;
  }

  /**
   * Set the event ID.
   *
   * @param Drupal\recurring_events\Entity\EventInstance $event
   *   The eventinstance entity.
   *
   * @return Drupal\recurring_events_registration\Entity\RegistrantInterface
   *   The registrant entity.
   */
  public function setEventInstance(EventInstance $event) {
    $this->set('eventinstance_id', $event->id());
    return $this;
  }

  /**
   * Get registration type.
   *
   * @return string
   *   The type of registration, series or instance.
   */
  public function getRegistrationType() {
    return $this->get('type')->value;
  }

  /**
   * Set the registration type.
   *
   * @param string $type
   *   The type of registration, series or instance.
   *
   * @return Drupal\recurring_events_registration\Entity\RegistrantInterface
   *   The registrant entity.
   */
  public function setRegistrationType($type) {
    $this->set('type', $type);
    return $this;
  }

  /**
   * Get the event.
   *
   * @return int
   *   Whether the registrant is on the waitlist.
   */
  public function getWaitlist() {
    return $this->get('waitlist')->value;
  }

  /**
   * Set the waitlist.
   *
   * @param int $waitlist
   *   Whether the registrant is on the waitlist.
   *
   * @return Drupal\recurring_events_registration\Entity\RegistrantInterface
   *   The registrant entity.
   */
  public function setWaitlist($waitlist) {
    $this->set('waitlist', $waitlist);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['eventinstance'] = $this->getEventInstance()->id();
    $uri_route_parameters['registrant'] = $this->id();
    if ($rel == 'anon-edit-form' || $rel == 'anon-delete-form') {
      $uri_route_parameters['uuid'] = $this->uuid->value;
    }
    return $uri_route_parameters;
  }

}
