<?php

namespace Drupal\Tests\group\Kernel\QueryAlter;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\group\QueryAccess\GroupRelationshipQueryAlter;

/**
 * Tests the behavior of relationship query alter.
 *
 * @coversDefaultClass \Drupal\group\QueryAccess\GroupRelationshipQueryAlter
 * @group group
 */
class GroupRelationshipQueryAlterTest extends QueryAlterTestBase {

  /**
   * {@inheritdoc}
   */
  protected $entityTypeId = 'group_relationship';

  /**
   * {@inheritdoc}
   */
  protected $relationshipsAffectAccess = FALSE;

  /**
   * The plugin ID to use in testing.
   *
   * @var string
   */
  protected $pluginId = 'user_relation';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
  }

  /**
   * {@inheritdoc}
   */
  public function queryAccessProvider() {
    $cases = parent::queryAccessProvider();

    // Only view own is supported.
    foreach (['outsider', 'insider', 'individual'] as $scope) {
      unset($cases["single-$scope-any-own-view"]);
      unset($cases["single-$scope-own-view"]);
    }

    // The mixed admin cases would add ALL installed plugins when the role is
    // flagged as admin, so to narrow down the tests and only keep the ones with
    // the admin permission.
    foreach (['view', 'update', 'delete', 'unsupported'] as $operation) {
      foreach (['outsider', 'insider', 'individual'] as $copy_key) {
        unset($cases["single-admin-$copy_key-$operation"]);
      }

      unset(
        $cases["mixed-outsider-insideradmin-any-" . $operation],
        $cases["mixed-outsider-individualadmin-any-" . $operation],
        $cases["mixed-insider-individualadmin-any-" . $operation]
      );
    }

    // All cases with access check the plugin ID.
    foreach ($cases as $key => $case) {
      if ($case['has_access']) {
        $cases[$key]['joins_data_table'] = TRUE;
      }
    }

    return $cases;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAlterClass() {
    return GroupRelationshipQueryAlter::class;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPermission($operation, $scope, $unpublished = FALSE) {
    if ($operation === 'unsupported') {
      return FALSE;
    }
    if ($operation === 'view') {
      return "$operation $this->pluginId relationship";
    }
    return "$operation $scope $this->pluginId relationship";
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
  protected function setUpContent(GroupTypeInterface $group_type) {
    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->save($storage->createFromPlugin($group_type, $this->pluginId));
    return $this->createGroup(['type' => $group_type->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinTable() {
    return 'group_relationship';
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinLeftField() {
    return 'id';
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinRightField() {
    return 'id';
  }

  /**
   * {@inheritdoc}
   */
  protected function addNoAccessConditions(SelectInterface $query) {
    $query->alwaysFalse();
  }

  /**
   * {@inheritdoc}
   */
  protected function addSynchronizedConditions(array $allowed_ids, ConditionInterface $conditions, $outsider) {
    $sub_condition = $conditions->andConditionGroup();
    $sub_condition->condition('group_relationship_field_data.group_type', $allowed_ids, 'IN');
    $sub_condition->condition('group_relationship_field_data.plugin_id', $this->pluginId);
    if ($outsider) {
      $sub_condition->isNull('gcfd.entity_id');
    }
    else {
      $sub_condition->isNotNull('gcfd.entity_id');
    }
    $conditions->condition($sub_condition);
  }

  /**
   * {@inheritdoc}
   */
  protected function addIndividualConditions(array $allowed_ids, ConditionInterface $conditions) {
    $sub_condition = $conditions->andConditionGroup();
    $sub_condition->condition('group_relationship_field_data.gid', $allowed_ids, 'IN');
    $sub_condition->condition('group_relationship_field_data.plugin_id', $this->pluginId);
    $conditions->condition($sub_condition);
  }

}
