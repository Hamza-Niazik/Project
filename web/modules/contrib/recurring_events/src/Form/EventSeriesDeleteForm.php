<?php

namespace Drupal\recurring_events\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Render\Renderer;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Provides a form for deleting an eventseries entity.
 *
 * @ingroup recurring_events
 */
class EventSeriesDeleteForm extends ContentEntityDeleteForm {

  /**
   * The untranslated eventseries.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  public $untranslatedEvent;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('messenger'),
      $container->get('renderer'),
      $container->get('config.factory')
    );
  }

  /**
   * Construct an EventSeriesDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info interface.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time interface.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   The config factory service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, Messenger $messenger, Renderer $renderer, ConfigFactory $config) {
    $this->messenger = $messenger;
    $this->renderer = $renderer;
    $this->config = $config;
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
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
          '%label' => $this->getEntity()->title->value,
        ]);
    }
    return $this->t('Are you sure you want to delete event %name and its instances?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if ($this->entity->isDefaultTranslation()) {
      $instances = $this->entity->event_instances->referencedEntities();
      $description = [];

      if (!empty($instances)) {
        $description = [];
        $description['description'] = [
          '#type' => 'markup',
          '#markup' => $this->t('This event series contains %count events taking place on the following dates:', ['%count' => count($instances)]),
        ];

        $options = [];
        $timezone = new \DateTimeZone(date_default_timezone_get());
        foreach ($instances as $instance) {
          $date = $instance->date->start_date;
          $date->setTimezone($timezone);
          $options[] = $instance->toLink($date->format($this->config->get('recurring_events.eventseries.config')->get('date_format')));
        }

        $description['instances'] = [
          '#theme' => 'item_list',
          '#list_type' => 'ul',
          '#title' => $this->t('Event Instances'),
          '#items' => $options,
        ];

        $description['end'] = [
          '#type' => 'markup',
          '#markup' => $this->t('Deleting this event series will remove all associated event instances.'),
        ];
      }

      $description['last'] = [
        '#type' => 'markup',
        '#markup' => $this->t('<p>This action cannot be undone.</p>'),
      ];

      return $this->renderer->render($description);
    }
    return '';
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the eventinstance list.
   */
  public function getCancelUrl() {
    return new Url('entity.eventseries.collection');
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
    $this->untranslatedEvent = $entity->getUntranslated();
    if (!$entity->isDefaultTranslation()) {
      $this->untranslatedEvent->removeTranslation($entity->language()->getId());
      $this->untranslatedEvent->save();
      $this->messenger->addMessage($this->t('@language translation of the @type %label has been deleted.', [
        '@language' => $entity->language()->getName(),
        '@type' => 'Event',
        '%label' => $this->untranslatedEvent->title->value,
      ]));
      $form_state->setRedirectUrl($this->untranslatedEvent->toUrl('canonical'));
    }
    else {
      $entity->delete();

      \Drupal::logger('recurring_events')->notice('@type: deleted %title.',
        [
          '@type' => $this->entity->bundle(),
          '%title' => $this->entity->value,
        ]
      );

      $this->messenger->addMessage($this->t('The %title event series and all the instances have been deleted.', [
        '%title' => $this->entity->title->value,
      ]));

      $form_state->setRedirect('entity.eventseries.collection');
    }
    $this->logDeletionMessage();
  }

}
