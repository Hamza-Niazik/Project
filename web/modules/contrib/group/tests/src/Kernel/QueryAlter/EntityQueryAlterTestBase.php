<?php

namespace Drupal\Tests\group\Kernel\QueryAlter;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\group\QueryAccess\EntityQueryAlter;

/**
 * Base class for testing \Drupal\group\QueryAccess\EntityQueryAlter.
 */
abstract class EntityQueryAlterTestBase extends QueryAlterTestBase {

  /**
   * The plugin ID to use in testing.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * {@inheritdoc}
   */
  protected function getPermission($operation, $scope, $unpublished = FALSE) {
    if ($operation === 'unsupported') {
      return FALSE;
    }
    $status = $unpublished ? 'unpublished ' : '';
    return "$operation $scope $status$this->pluginId entity";
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdminPermission() {
    return "administer $this->pluginId";
  }

  /**
   * {@inheritdoc}
   */
  protected function getAlterClass() {
    return EntityQueryAlter::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function joinExtraTables(SelectInterface $query) {
    // Joins the group_relationship table to check for plugins that provide access.
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $base_table = $entity_type->getBaseTable();
    $id_key = $entity_type->getKey('id');
    $query->leftJoin(
      'group_relationship_field_data',
      'gcfd',
      "$base_table.$id_key=%alias.entity_id AND %alias.plugin_id IN (:plugin_ids_in_use[])",
      [':plugin_ids_in_use[]' => [$this->pluginId]]
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinTable() {
    return 'gcfd';
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinLeftField() {
    return 'gid';
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinRightField() {
    return 'gid';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpContent(GroupTypeInterface $group_type) {
    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->save($storage->createFromPlugin($group_type, $this->pluginId));
  }

  /**
   * {@inheritdoc}
   */
  protected function addNoAccessConditions(SelectInterface $query) {
    $query->isNull('gcfd.entity_id');
  }

  /**
   * {@inheritdoc}
   */
  protected function addWrapperConditionGroup(SelectInterface $query) {
    $query->condition($is_grouped_conditions = $query->orConditionGroup());
    $is_grouped_conditions->isNull('gcfd.entity_id');
    return $is_grouped_conditions;
  }

  /**
   * {@inheritdoc}
   */
  protected function addSynchronizedConditions(array $allowed_ids, ConditionInterface $conditions, $outsider) {
    $conditions->condition($type_conditions = $conditions->andConditionGroup());
    $type_conditions->condition('gcfd.group_type', $allowed_ids, 'IN');
    if ($outsider) {
      $type_conditions->isNull('gcfd_2.entity_id');
    }
    else {
      $type_conditions->isNotNull('gcfd_2.entity_id');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function addIndividualConditions(array $allowed_ids, ConditionInterface $conditions) {
    $conditions->condition('gcfd.gid', $allowed_ids, 'IN');
  }

}
