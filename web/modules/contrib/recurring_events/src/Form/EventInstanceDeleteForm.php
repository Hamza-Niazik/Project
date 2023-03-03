<?php

namespace Drupal\recurring_events\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Url;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Provides a form for deleting an eventinstance entity.
 *
 * @ingroup recurring_event
 */
class EventInstanceDeleteForm extends ContentEntityDeleteForm {

  /**
   * The untranslated event instance.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  public $untranslatedEventInstance;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Construct a EventInstanceDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info interface.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time interface.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, Messenger $messenger, DateFormatter $date_formatter) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->messenger = $messenger;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('messenger'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    if (!$entity->isDefaultTranslation()) {
      return $this
        ->t('Are you sure you want to delete the @language translation of the @entity-type %label?', [
          '@language' => $entity->language()->getName(),
          '@entity-type' => $this->getEntity()->getEntityType()->getSingularLabel(),
          '%label' => $this->entity->getEventSeries()->title->value,
        ]);
    }
    return $this->t('Are you sure you want to delete event instance for %name?', ['%name' => $this->entity->getEventSeries()->title->value]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();

    // Make sure that deleting a translation does not delete the whole entity.
    if ($entity->isDefaultTranslation()) {
      $start_date = $entity->date->start_date;
      return $this->t('Deleting this instance will remove only the instance on %date and not other events in this series. This action cannot be undone.', [
        '%date' => $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'Y-m-d h:i A'),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the eventinstance list.
   */
  public function getCancelUrl() {
    return new Url('entity.eventinstance.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();

    // Make sure that deleting a translation does not delete the whole entity.
    $this->untranslatedEventInstance = $entity->getUntranslated();
    if (!$entity->isDefaultTranslation()) {
      $this->untranslatedEventInstance->removeTranslation($entity->language()->getId());
      $this->untranslatedEventInstance->save();
      $this->message->addMessage($this->t('@language translation of the @type %label has been deleted.', [
        '@language' => $entity->language()->getName(),
        '@type' => 'Event',
        '%label' => $this->untranslatedEventInstance->getEventSeries()->title->value,
      ]));
      $form_state->setRedirectUrl($this->untranslatedEventInstance->toUrl('canonical'));
    }
    else {
      $series_instances = $entity->getEventSeries()->event_instances->referencedEntities();

      // Allow other modules to react prior to deleting a specific instance
      // after a date configuration change.
      \Drupal::moduleHandler()->invokeAll('recurring_events_pre_delete_instance', [$entity]);

      // Loop through all instances on the series and remove the reference to
      // this instance.
      if (!empty($series_instances)) {
        foreach ($series_instances as $instance) {
          if ($instance->id() == $entity->id()) {
            $instance->delete();
          }
        }
      }

      $entity->delete();

      // Allow other modules to react after deleting a specific instance after a
      // date configuration change.
      \Drupal::moduleHandler()->invokeAll('recurring_events_post_delete_instance', [$entity]);

      $start_date = $entity->date->start_date;
      \Drupal::logger('recurring_events')->notice('@type: deleted event instance of %title scheduled to begin on %date.',
        [
          '@type' => $this->entity->bundle(),
          '%title' => $this->entity->getEventSeries()->title->value,
          '%date' => $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'Y-m-d h:i A'),
        ]
      );

      $this->messenger->addMessage($this->t('The %title event instance starting on %date has been deleted.', [
        '%title' => $this->entity->getEventSeries()->title->value,
        '%date' => $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'Y-m-d h:i A'),
      ]));

      $form_state->setRedirect('entity.eventinstance.collection');
    }
    $this->logDeletionMessage();
  }

}
