<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Trait for group relation permission providers.
 *
 * This trait takes care of common logic for permission providers. Please make
 * sure your handler service asks for the entity_type.manager service and sets
 * to the $this->entityTypeManager property in its constructor.
 */
trait PermissionProviderTrait {

  use RelationHandlerTrait {
    init as traitInit;
  }

  /**
   * The entity type the plugin handler is for.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * Whether the target entity type implements the EntityOwnerInterface.
   *
   * @var bool
   */
  protected $implementsOwnerInterface;

  /**
   * Whether the target entity type implements the EntityPublishedInterface.
   *
   * @var bool
   */
  protected $implementsPublishedInterface;

  /**
   * Whether the plugin defines permissions for the target entity type.
   *
   * @var bool
   */
  protected $definesEntityPermissions;

  /**
   * {@inheritdoc}
   */
  public function init($plugin_id, GroupRelationTypeInterface $group_relation_type) {
    $this->traitInit($plugin_id, $group_relation_type);
    $this->entityType = $this->entityTypeManager()->getDefinition($group_relation_type->getEntityTypeId());
    $this->implementsOwnerInterface = $this->entityType->entityClassImplements(EntityOwnerInterface::class);
    $this->implementsPublishedInterface = $this->entityType->entityClassImplements(EntityPublishedInterface::class);
    $this->definesEntityPermissions = $group_relation_type->definesEntityAccess();
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminPermission() {
    if (!isset($this->parent)) {
      throw new \LogicException('Using PermissionProviderTrait without assigning a parent or overwriting the methods.');
    }
    return $this->parent->getAdminPermission();
  }

  /**
   * {@inheritdoc}
   */
  public function getPermission($operation, $target, $scope = 'any') {
    if (!isset($this->parent)) {
      throw new \LogicException('Using PermissionProviderTrait without assigning a parent or overwriting the methods.');
    }
    return $this->parent->getPermission($operation, $target, $scope);
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    if (!isset($this->parent)) {
      throw new \LogicException('Using PermissionProviderTrait without assigning a parent or overwriting the methods.');
    }
    return $this->parent->buildPermissions();
  }

  /**
   * Builds a permission with common translation arguments predefined.
   *
   * @param string $title
   *   The permission title.
   * @param string $description
   *   (optional) The permission description.
   *
   * @return array
   *   The permission with a default translatable markup replacement for both
   *   %plugin_name and %entity_type.
   */
  protected function buildPermission($title, $description = NULL) {
    $t_args = [
      '%plugin_name' => $this->groupRelationType->getLabel(),
      '%entity_type' => $this->entityType->getSingularLabel(),
    ];

    $permission['title'] = $title;
    $permission['title_args'] = $t_args;

    if (isset($description)) {
      $permission['description'] = $description;
      $permission['description_args'] = $t_args;
    }

    return $permission;
  }

}
