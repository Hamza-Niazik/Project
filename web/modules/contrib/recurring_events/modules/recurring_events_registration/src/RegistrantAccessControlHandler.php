<?php

namespace Drupal\recurring_events_registration;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Component\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Access controller for the Registrant entity.
 *
 * @see \Drupal\recurring_events_registration\Entity\Registrant.
 */
class RegistrantAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The creation service.
   *
   * @var \Drupal\recurring_events_registration\RegistrationCreationService
   */
  protected $creationService;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * RegistrantAccessControlHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\recurring_events_registration\RegistrationCreationService $creation_service
   *   The creation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeInterface $entity_type, RegistrationCreationService $creation_service, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type);
    $this->creationService = $creation_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('recurring_events_registration.creation_service'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\recurring_events_registration\Entity\RegistrantInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view registrant entities');

      case 'update':
        if ($account->id() !== $entity->getOwnerId()) {
          return AccessResult::allowedIfHasPermission($account, 'edit registrant entities');
        }
        return AccessResult::allowedIfHasPermissions($account, [
          'edit registrant entities',
          'edit own registrant entities',
        ], 'OR');

      case 'delete':
        if ($account->id() !== $entity->getOwnerId()) {
          return AccessResult::allowedIfHasPermission($account, 'delete registrant entities');
        }
        return AccessResult::allowedIfHasPermissions($account, [
          'delete registrant entities',
          'delete own registrant entities',
        ], 'OR');

      case 'resend':
        return AccessResult::allowedIfHasPermission($account, 'resend registrant emails');

      case 'anon-update':
      case 'anon-delete':
        return $this->checkAnonymousAccess($entity, $operation, $account);
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $params = \Drupal::request()->attributes->all();
    if (!empty($params['eventinstance'])) {
      if (is_string($params['eventinstance']) || is_numeric($params['eventinstance'])) {
        $params['eventinstance'] = $this->entityTypeManager->getStorage('eventinstance')->load($params['eventinstance']);
      }
      $this->creationService->setEventInstance($params['eventinstance']);
      if ($this->creationService->hasRegistration()) {
        return AccessResult::allowedIfHasPermission($account, 'add registrant entities');
      }
    }

    return AccessResult::neutral();
  }

  /**
   * Check if the user can edit or delete this registrant anonymously.
   *
   * @param Drupal\Core\Entity\EntityInterface $registrant
   *   The registrant to be edited.
   * @param string $operation
   *   The operation being attempted.
   * @param Drupal\Core\Session\AccountInterface $account
   *   The user attempting to gain access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkAnonymousAccess(EntityInterface $registrant, $operation, AccountInterface $account) {
    $params = \Drupal::request()->attributes->all();
    if (!empty($params['uuid'])) {
      $uuid = $params['uuid'];
      // We should not be allowed to edit anonymously if the registrant belongs
      // to a user.
      if ($registrant->getOwnerId() !== '0') {
        return AccessResult::forbidden('This registrant cannot be edited using a UUID.');
      }

      // If UUID was not passed in, then this is an invalid request.
      if (empty($uuid)) {
        return AccessResult::forbidden('No UUID was specified.');
      }

      // If this is not a valid UUID, then this is an invalid request.
      if (!Uuid::isValid($uuid)) {
        return AccessResult::forbidden('The provided UUID is invalid.');
      }

      // If the UUID specified is not for the registrant being edited.
      if ($uuid !== $registrant->uuid->value) {
        return AccessResult::forbidden('The provided UUID is not valid for this registrant');
      }

      switch ($operation) {
        case 'anon-update':
          return AccessResult::allowedIfHasPermission($account, 'edit registrant entities anonymously');

        case 'anon-delete':
          return AccessResult::allowedIfHasPermission($account, 'delete registrant entities anonymously');
      }

    }
    return AccessResult::forbidden();
  }

}
