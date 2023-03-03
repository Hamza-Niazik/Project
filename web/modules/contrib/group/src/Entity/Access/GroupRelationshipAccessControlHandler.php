<?php

namespace Drupal\group\Entity\Access;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\GroupRelationshipTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access controller for the GroupRelationship entity.
 *
 * @see \Drupal\group\Entity\GroupRelationship.
 */
class GroupRelationshipAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $groupRelationTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = new static($entity_type);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->groupRelationTypeManager = $container->get('group_relation_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    assert($entity instanceof GroupRelationshipInterface);
    $access_control = $this->groupRelationTypeManager->getAccessControlHandler($entity->getPluginId());
    return $access_control->relationshipAccess($entity, $operation, $account, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $relationship_type = $this->entityTypeManager->getStorage('group_relationship_type')->load($entity_bundle);
    assert($relationship_type instanceof GroupRelationshipTypeInterface);
    $access_control = $this->groupRelationTypeManager->getAccessControlHandler($relationship_type->getPluginId());
    return $access_control->relationshipCreateAccess($context['group'], $account, TRUE);
  }

}
