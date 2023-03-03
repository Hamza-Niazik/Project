<?php

namespace Drupal\recurring_events_registration;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\recurring_events\EventInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * AccessHandler class definition.
 */
class AccessHandler {
  /**
   * The translation interface.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  private $translation;

  /**
   * The registration creation service.
   *
   * @var \Drupal\recurring_events_registration\RegistrationCreationService
   */
  protected $creationService;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation interface.
   * @param \Drupal\recurring_events_registration\RegistrationCreationService $creation_service
   *   The registration creation service.
   * @param Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(TranslationInterface $translation, RegistrationCreationService $creation_service, CurrentRouteMatch $route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->translation = $translation;
    $this->creationService = $creation_service;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
      $container->get('recurring_events_registration.creation_service'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Access control based on whether event has registration.
   *
   * @return bool
   *   TRUE if event has registration, FALSE otherwise.
   */
  public function eventHasRegistration() {
    $has_registration = FALSE;
    $event_instance = $this->routeMatch->getParameter('eventinstance');
    if (!empty($event_instance)) {

      if (!$event_instance instanceof EventInterface && is_numeric($event_instance)) {
        $event_instance = $this->entityTypeManager->getStorage('eventinstance')->load($event_instance);
      }

      if ($event_instance instanceof EventInterface) {
        $this->creationService->setEventInstance($event_instance);
        $has_registration = $this->creationService->hasRegistration();
      }
    }
    return $has_registration;
  }

  /**
   * Access control based on whether the account has the right permission.
   *
   * @param Drupal\Core\Session\AccountInterface $account
   *   The current route.
   *
   * @return bool
   *   TRUE if user has access, FALSE otherwise.
   */
  public function userHasPermission(AccountInterface $account) {
    return $account->hasPermission('access registrant overview');
  }

  /**
   * Access control for the Event Registration List view.
   */
  public function eventRegistrationListAccess(AccountInterface $account, Route $route) {
    if (!$this->eventHasRegistration()) {
      return AccessResult::forbidden();
    }

    if (!$this->userHasPermission($account)) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowed();
  }

}
