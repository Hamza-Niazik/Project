<?php

namespace Drupal\group\Entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access controller for the ConfigWrapper entity.
 *
 * @see \Drupal\group\Entity\ConfigWrapper.
 */
class ConfigWrapperAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return AccessResult::forbidden('Config wrappers should only exist in code.')->setCacheMaxAge(Cache::PERMANENT);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::forbidden('Config wrappers should only exist in code.')->setCacheMaxAge(Cache::PERMANENT);
  }

}
