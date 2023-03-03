<?php

namespace Drupal\recurring_events_registration\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Render\Renderer;
use Drupal\recurring_events_registration\RegistrationCreationService;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;

/**
 * Provides a form for deleting Registrant entities.
 *
 * @ingroup recurring_events_registration
 */
class RegistrantDeleteForm extends ContentEntityDeleteForm {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The creation service.
   *
   * @var \Drupal\recurring_events_registration\RegistrationCreationService
   */
  protected $creationService;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a RegistrantDeleteForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The renderer service.
   * @param \Drupal\recurring_events_registration\RegistrationCreationService $creation_service
   *   The creation service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, Messenger $messenger, Renderer $renderer, RegistrationCreationService $creation_service, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);
    $this->messenger = $messenger;
    $this->renderer = $renderer;
    $this->creationService = $creation_service;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
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
      $container->get('renderer'),
      $container->get('recurring_events_registration.creation_service'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Cancel Your Registration');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    /** @var \Drupal\recurring_events_registration\Entity\Registrant $entity */
    $entity = $this->entity;

    $build['cancel'] = [
      '#type' => 'container',
      '#weight' => -99,
      'title' => [
        '#type' => 'markup',
        '#prefix' => '<h2 class="registration-register-title">',
        '#markup' => $this->t('Cancel Event Registration'),
        '#suffix' => '</h2>',
      ],
      'intro' => [
        '#type' => 'markup',
        '#prefix' => '<p class=registration-register-intro">',
        '#markup' => $this->t('You are cancelling your registration for %email for %event. Once you do this, there may no longer be any spaces left for this event and you may not be able to register again.', [
          '%email' => $entity->email->value,
          '%event' => $entity->getEventSeries()->title->value,
        ]),
        '#suffix' => '</p>',
      ],
    ];

    return $this->renderer->render($build);
  }

  /**
   * {@inheritdoc}
   *
   * If the delete command is canceled, return to the eventinstance list.
   */
  public function getCancelUrl() {
    return new Url('entity.eventinstance.canonical', ['eventinstance' => $this->getEntity()->getEventInstance()->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Go Back - Keep Registration');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Confirm Cancellation');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\recurring_events_registration\Entity\Registrant $entity */
    $entity = $this->entity;
    $entity->delete();
    $eventinstance = $entity->getEventInstance();
    $eventseries = $entity->getEventSeries();

    $form_state->setRedirectUrl($eventinstance->toUrl('canonical'));

    $this->creationService->setEventInstance($eventinstance);
    if ($this->creationService->hasWaitlist() && $entity->waitlist->value == '0') {
      $this->creationService->promoteFromWaitlist();
    }

    $this->messenger->addMessage($this->getDeletionMessage());
    $this->logDeletionMessage();

    // Invalidate tags to ensure that views count fields are updated.
    $tags = [];
    switch ($this->creationService->getRegistrationType()) {
      case 'series':
        $tags[] = 'eventseries:' . $eventseries->id();
        break;

      case 'instance':
      default:
        $tags[] = 'eventinstance:' . $eventinstance->id();
        break;
    }
    $this->cacheTagsInvalidator->invalidateTags($tags);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    /** @var \Drupal\recurring_events\EventInterface $entity */
    $entity = $this->getEntity();

    return $this->t('Your registration for %email for %event has been cancelled.', [
      '%email' => $entity->email->value,
      '%event' => $entity->getEventSeries()->title->value,
    ]);
  }

}
