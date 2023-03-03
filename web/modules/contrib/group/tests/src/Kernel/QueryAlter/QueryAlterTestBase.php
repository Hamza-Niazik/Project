<?php

namespace Drupal\Tests\group\Kernel\QueryAlter;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\user\RoleInterface;

/**
 * Base class for testing query alters.
 */
abstract class QueryAlterTestBase extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test_plugin', 'node'];

  /**
   * The entity type ID to use in testing.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Whether the entity type supports publishing.
   *
   * @var bool
   */
  protected $isPublishable = FALSE;

  /**
   * Whether the entity type supports ownership.
   *
   * @var bool
   */
  protected $isOwnable = TRUE;

  /**
   * Whether access might be different if relationships do (not) exist.
   *
   * @var bool
   */
  protected $relationshipsAffectAccess = TRUE;

  /**
   * Tests query access in various scenarios.
   *
   * @param string $operation
   *   The operation to test access for.
   * @param bool $operation_supported
   *   Whether the operation is supported.
   * @param bool $has_access
   *   Whether the user should gain access.
   * @param bool $is_outsider_admin
   *   Whether the user is a group admin via an outsider role.
   * @param bool $is_insider_admin
   *   Whether the user is a group admin via an insider role.
   * @param bool $is_individual_admin
   *   Whether the user is a group admin via an individual role.
   * @param string[] $outsider_permissions
   *   The user's group permissions in the outsider scope.
   * @param string[] $insider_permissions
   *   The user's group permissions in the insider scope.
   * @param string[] $individual_permissions
   *   The user's group permissions in the individual scope.
   * @param bool $joins_member_table
   *   Whether the query is expected to join the membership table.
   * @param bool $joins_data_table
   *   Whether the query is expected to join the data table.
   * @param bool $checks_status
   *   Whether the query is expected to check the entity status.
   * @param bool $checks_owner
   *   Whether the query is expected to check the entity owner.
   * @param int $status
   *   (optional) The status value to check for. Defaults to 1.
   * @param bool $has_relationships
   *   (optional) If relationships exist for the entity type. Defaults to TRUE.
   *
   * @covers ::getConditions
   * @dataProvider queryAccessProvider
   */
  public function testQueryAccess(
    $operation,
    $operation_supported,
    $has_access,
    $is_outsider_admin,
    $is_insider_admin,
    $is_individual_admin,
    array $outsider_permissions,
    array $insider_permissions,
    array $individual_permissions,
    $joins_member_table,
    $joins_data_table,
    $checks_status,
    $checks_owner,
    $status = 1,
    $has_relationships = TRUE
  ) {
    if ($checks_status || $checks_owner) {
      $this->assertTrue($joins_data_table, 'Data table should be checked for status or owner.');
    }

    $definition = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $data_table = $definition->getDataTable() ?: $definition->getBaseTable();
    $group_type = $this->createGroupType();

    if ($individual_permissions || $is_individual_admin) {
      $group_role = $this->createGroupRole([
        'group_type' => $group_type->id(),
        'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
        'permissions' => $is_individual_admin ? [] : $individual_permissions,
        'admin' => $is_individual_admin,
      ]);
      $group_type->set('creator_roles', [$group_role->id()]);
      $group_type->save();
    }

    if ($outsider_permissions || $is_outsider_admin) {
      $this->createGroupRole([
        'group_type' => $group_type->id(),
        'scope' => PermissionScopeInterface::OUTSIDER_ID,
        'global_role' => RoleInterface::AUTHENTICATED_ID,
        'permissions' => $is_outsider_admin ? [] : $outsider_permissions,
        'admin' => $is_outsider_admin,
      ]);
    }

    if ($insider_permissions || $is_insider_admin) {
      $this->createGroupRole([
        'group_type' => $group_type->id(),
        'scope' => PermissionScopeInterface::INSIDER_ID,
        'global_role' => RoleInterface::AUTHENTICATED_ID,
        'permissions' => $is_insider_admin ? [] : $insider_permissions,
        'admin' => $is_insider_admin,
      ]);
    }

    if ($has_relationships) {
      $group = $this->setUpContent($group_type);
    }

    $query = $this->createAlterableQuery($operation);
    $control = $this->createAlterableQuery($operation);

    $this->alterQuery($query);
    if ($operation_supported && $has_relationships) {
      $this->joinExtraTables($control);

      if (!$has_access) {
        $this->addNoAccessConditions($control);
      }
      else {
        if ($definition->getDataTable() && $joins_data_table) {
          $this->joinTargetEntityDataTable($control);
        }

        if ($joins_member_table) {
          $this->joinMemberships($control);
        }

        $scope_conditions = $this->addWrapperConditionGroup($control);

        // Now that the roles are set up, equate admin flag to admin permission.
        $admin_permission = $this->getAdminPermission();
        $is_individual_admin = $is_individual_admin || in_array($admin_permission, $individual_permissions, TRUE);
        $is_outsider_admin = $is_outsider_admin || in_array($admin_permission, $outsider_permissions, TRUE);
        $is_insider_admin = $is_insider_admin || in_array($admin_permission, $insider_permissions, TRUE);

        $checks_individual_permissions = $individual_permissions && !$is_individual_admin;
        $checks_outsider_permissions = $outsider_permissions && !$is_outsider_admin;
        $checks_insider_permissions = $insider_permissions && !$is_insider_admin;

        $checks_non_admin = $checks_individual_permissions || $checks_outsider_permissions || $checks_insider_permissions;
        $checks_admin = $is_individual_admin || $is_outsider_admin || $is_insider_admin;

        if (($checks_status || $checks_owner) && $checks_non_admin && $checks_admin) {
          $scope_conditions = $this->ensureOrConjunction($scope_conditions);
        }

        if ($is_individual_admin) {
          $scope_conditions = $this->ensureOrConjunction($scope_conditions);
          $this->addIndividualConditions([$group->id()], $scope_conditions);
        }

        if ($is_outsider_admin) {
          $scope_conditions = $this->ensureOrConjunction($scope_conditions);
          $this->addSynchronizedConditions([$group_type->id()], $scope_conditions, TRUE);
        }

        if ($is_insider_admin) {
          $scope_conditions = $this->ensureOrConjunction($scope_conditions);
          $this->addSynchronizedConditions([$group_type->id()], $scope_conditions, FALSE);
        }

        if ($checks_status) {
          $status_key = $definition->getKey('published');
          $scope_conditions->condition($status_group = $control->andConditionGroup());
          $status_group->condition("$data_table.$status_key", $status);
          $status_group->condition($scope_conditions = $control->orConditionGroup());
        }

        if ($checks_owner) {
          $owner_key = $definition->getKey('owner');
          $scope_conditions->condition($owner_conditions = $control->andConditionGroup());
          $owner_conditions->condition("$data_table.$owner_key", $this->getCurrentUser()->id());
          $owner_conditions->condition($scope_conditions = $control->orConditionGroup());
        }

        if ($checks_individual_permissions) {
          $scope_conditions = $this->ensureOrConjunction($scope_conditions);
          $this->addIndividualConditions([$group->id()], $scope_conditions);
        }

        if ($checks_outsider_permissions) {
          $scope_conditions = $this->ensureOrConjunction($scope_conditions);
          $this->addSynchronizedConditions([$group_type->id()], $scope_conditions, TRUE);
        }

        if ($checks_insider_permissions) {
          $scope_conditions = $this->ensureOrConjunction($scope_conditions);
          $this->addSynchronizedConditions([$group_type->id()], $scope_conditions, FALSE);
        }
      }
    }

    $this->assertEqualsCanonicalizing($control->getTables(), $query->getTables());
    $this->assertEqualsCanonicalizing($control->conditions(), $query->conditions());
  }

  /**
   * Data provider for testQueryAccess().
   *
   * @return array
   *   A list of testQueryAccess method arguments.
   */
  public function queryAccessProvider() {
    foreach (['view', 'update', 'delete', 'unsupported'] as $operation) {
      $checks_status = $this->isPublishable && $operation === 'view';

      if ($this->relationshipsAffectAccess) {
        // Case when there is no relationship for the entity type.
        $cases["no-relationships-$operation"] = [
          'operation' => $operation,
          'operation_supported' => $operation !== 'unsupported',
          'has_access' => FALSE,
          'is_outsider_admin' => FALSE,
          'is_insider_admin' => FALSE,
          'is_individual_admin' => FALSE,
          'outsider_permissions' => [],
          'insider_permissions' => [],
          'individual_permissions' => [],
          'joins_member_table' => FALSE,
          'joins_data_table' => FALSE,
          'checks_status' => FALSE,
          'checks_owner' => FALSE,
          'status' => 1,
          'has_relationships' => FALSE,
        ];
      }

      // Case when nothing grants access.
      $cases["no-access-$operation"] = [
        'operation' => $operation,
        'operation_supported' => $operation !== 'unsupported',
        'has_access' => FALSE,
        'is_outsider_admin' => FALSE,
        'is_insider_admin' => FALSE,
        'is_individual_admin' => FALSE,
        'outsider_permissions' => [],
        'insider_permissions' => [],
        'individual_permissions' => [],
        'joins_member_table' => FALSE,
        'joins_data_table' => FALSE,
        'checks_status' => FALSE,
        'checks_owner' => FALSE,
      ];

      // Single any vs own access for outsider, insider and individual.
      $single_base = [
        'operation' => $operation,
        'operation_supported' => $operation !== 'unsupported',
        'has_access' => TRUE,
        'is_outsider_admin' => FALSE,
        'is_insider_admin' => FALSE,
        'is_individual_admin' => FALSE,
        'outsider_permissions' => [],
        'insider_permissions' => [],
        'individual_permissions' => [],
        'joins_member_table' => FALSE,
        'joins_data_table' => $checks_status,
        'checks_status' => $checks_status,
        'checks_owner' => FALSE,
      ];

      // Add the own permission (if applicable) to prove it's never checked.
      $single_permissions = [$this->getPermission($operation, 'any')];
      if ($this->isOwnable) {
        $single_permissions[] = $this->getPermission($operation, 'own');
      }
      $single_permissions = array_filter($single_permissions);

      // Do the same for unpublished, if applicable.
      if ($checks_status) {
        $unpub_permissions = [$this->getPermission($operation, 'any', TRUE)];
        if ($this->isOwnable) {
          $unpub_permissions[] = $this->getPermission($operation, 'own', TRUE);
        }
        $unpub_permissions = array_filter($unpub_permissions);
      }

      foreach (['outsider', 'insider', 'individual'] as $copy_key) {
        $cases["single-$copy_key-any-$operation"] = $single_base;
        $cases["single-$copy_key-any-$operation"]["${copy_key}_permissions"] = $single_permissions;
        $cases["single-$copy_key-any-$operation"]['joins_member_table'] = $copy_key !== 'individual';

        if ($checks_status) {
          $cases["single-$copy_key-any-unpublished-$operation"] = $cases["single-$copy_key-any-$operation"];
          $cases["single-$copy_key-any-unpublished-$operation"]['status'] = 0;
          $cases["single-$copy_key-any-unpublished-$operation"]["${copy_key}_permissions"] = $unpub_permissions;
        }

        if ($this->isOwnable) {
          // Only having the owner permission should run the check.
          $cases["single-$copy_key-own-$operation"] = $cases["single-$copy_key-any-$operation"];
          $cases["single-$copy_key-own-$operation"]["${copy_key}_permissions"] = [$this->getPermission($operation, 'own')];
          $cases["single-$copy_key-own-$operation"]['joins_data_table'] = TRUE;
          $cases["single-$copy_key-own-$operation"]['checks_owner'] = TRUE;

          if ($checks_status) {
            $cases["single-$copy_key-own-unpublished-$operation"] = $cases["single-$copy_key-own-$operation"];
            $cases["single-$copy_key-own-unpublished-$operation"]['status'] = 0;
            $cases["single-$copy_key-own-unpublished-$operation"]["${copy_key}_permissions"] = [$this->getPermission($operation, 'own', TRUE)];
          }
        }

        // Single admin access for outsider, insider and individual. Please note
        // admin access does not need to check for status nor ownership.
        $cases["single-admin-$copy_key-$operation"] = $single_base;
        $cases["single-admin-$copy_key-$operation"]['joins_member_table'] = $copy_key !== 'individual';
        $cases["single-admin-$copy_key-$operation"]['joins_data_table'] = FALSE;
        $cases["single-admin-$copy_key-$operation"]['checks_status'] = FALSE;
        $cases["single-admin-$copy_key-$operation"]["is_${copy_key}_admin"] = TRUE;

        // Admin permission access for outsider, insider and individual. Behaves
        // the same as the admin flag, but only when permission is supported.
        if ($admin_permission = $this->getAdminPermission()) {
          $cases["single-adminpermission-$copy_key-$operation"] = $cases["single-admin-$copy_key-$operation"];
          $cases["single-adminpermission-$copy_key-$operation"]["is_${copy_key}_admin"] = FALSE;
          // Add in regular permissions to prove they aren't checked.
          $cases["single-adminpermission-$copy_key-$operation"]["${copy_key}_permissions"] = array_merge([$admin_permission], $single_permissions);
        }
      }

      // Mixed scope access for outsider, insider and individual.
      $cases["mixed-outsider-insider-any-" . $operation] = $cases["single-outsider-any-$operation"];
      $cases["mixed-outsider-insider-any-" . $operation]['insider_permissions'] = $single_permissions;
      $cases["mixed-outsider-individual-any-" . $operation] = $cases["single-outsider-any-$operation"];
      $cases["mixed-outsider-individual-any-" . $operation]['individual_permissions'] = $single_permissions;
      $cases["mixed-insider-individual-any-" . $operation] = $cases["single-insider-any-$operation"];
      $cases["mixed-insider-individual-any-" . $operation]['individual_permissions'] = $single_permissions;

      // Mixed scope access where one scope has admin rights.
      $cases["mixed-outsider-insideradmin-any-" . $operation] = $cases["single-outsider-any-$operation"];
      $cases["mixed-outsider-insideradmin-any-" . $operation]['is_insider_admin'] = TRUE;
      $cases["mixed-outsider-individualadmin-any-" . $operation] = $cases["single-outsider-any-$operation"];
      $cases["mixed-outsider-individualadmin-any-" . $operation]['is_individual_admin'] = TRUE;
      $cases["mixed-insider-individualadmin-any-" . $operation] = $cases["single-insider-any-$operation"];
      $cases["mixed-insider-individualadmin-any-" . $operation]['is_individual_admin'] = TRUE;

      // Again add in admin permission, but only when permission is supported.
      if ($admin_permission = $this->getAdminPermission()) {
        // Add in regular permissions to prove they aren't checked.
        $admin_permissions = array_merge([$admin_permission], $single_permissions);

        $cases["mixed-outsider-insideradminpermission-any-" . $operation] = $cases["mixed-outsider-insideradmin-any-" . $operation];
        $cases["mixed-outsider-insideradminpermission-any-" . $operation]['is_insider_admin'] = FALSE;
        $cases["mixed-outsider-insideradminpermission-any-" . $operation]['insider_permissions'] = $admin_permissions;
        $cases["mixed-outsider-individualadminpermission-any-" . $operation] = $cases["mixed-outsider-individualadmin-any-" . $operation];
        $cases["mixed-outsider-individualadminpermission-any-" . $operation]['is_individual_admin'] = FALSE;
        $cases["mixed-outsider-individualadminpermission-any-" . $operation]['individual_permissions'] = $admin_permissions;
        $cases["mixed-insider-individualadminpermission-any-" . $operation] = $cases["mixed-insider-individualadmin-any-" . $operation];
        $cases["mixed-insider-individualadminpermission-any-" . $operation]['is_individual_admin'] = FALSE;
        $cases["mixed-insider-individualadminpermission-any-" . $operation]['individual_permissions'] = $admin_permissions;
      }
    }

    return $cases;
  }

  /**
   * Gets the permission name for the given operation and scope.
   *
   * @param string $operation
   *   The operation.
   * @param string $scope
   *   The operation scope (any or own).
   * @param bool $unpublished
   *   Whether to check for the unpublished permission. Defaults to FALSE.
   *
   * @return string
   *   The permission name.
   */
  abstract protected function getPermission($operation, $scope, $unpublished = FALSE);

  /**
   * Gets the admin permission name.
   *
   * @return string|false
   *   The admin permission name or FALSE if there is none.
   */
  abstract protected function getAdminPermission();

  /**
   * Builds and returns a query that will be altered.
   *
   * @param string $operation
   *   The operation for the query.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The alterable query.
   */
  protected function createAlterableQuery($operation) {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $query = \Drupal::database()->select($entity_type->getBaseTable());
    $query->addMetaData('op', $operation);
    $query->addMetaData('entity_type', $this->entityTypeId);
    return $query;
  }

  /**
   * Alters the query.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to alter.
   */
  protected function alterQuery(SelectInterface $query) {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);
    \Drupal::service('class_resolver')
      ->getInstanceFromDefinition($this->getAlterClass())
      ->alter($query, $entity_type);
  }

  /**
   * Retrieves the namespaced alter class name.
   *
   * @return string
   *   The namespaced alter class name.
   */
  abstract protected function getAlterClass();

  /**
   * Makes sure a ConditionInterface has the OR conjunction.
   *
   * @param \Drupal\Core\Database\Query\ConditionInterface $parent
   *  The parent ConditionInterface to potentially add the OR group to.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface
   *   An OR condition group attached to the parent in case the parent did not
   *   already use said conjunction or the passed in parent if it did.
   */
  protected function ensureOrConjunction(ConditionInterface $parent) {
    $conditions_array = $parent->conditions();
    if ($conditions_array['#conjunction'] === 'OR') {
      return $parent;
    }

    $parent->condition($scope_conditions = $parent->orConditionGroup());
    return $scope_conditions;
  }

  /**
   * Joins any extra tables required for access checks.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to add the join(s) to.
   */
  protected function joinExtraTables(SelectInterface $query) {}

  /**
   * Joins the target entity data table.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to add the join to.
   */
  protected function joinTargetEntityDataTable(SelectInterface $query) {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $base_table = $entity_type->getBaseTable();
    $data_table = $entity_type->getDataTable();
    $id_key = $entity_type->getKey('id');
    $query->join(
      $data_table,
      $data_table,
      "$base_table.$id_key=$data_table.$id_key",
    );
  }

  /**
   * Joins the relationship field data table for memberships.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to add the join to.
   */
  protected function joinMemberships(SelectInterface $query) {
    $table = $this->getMembershipJoinTable();
    $l_field = $this->getMembershipJoinLeftField();
    $r_field = $this->getMembershipJoinRightField();

    $query->leftJoin(
      'group_relationship_field_data',
      'gcfd',
      "$table.$l_field=%alias.$r_field AND %alias.plugin_id='group_membership' AND %alias.entity_id=:account_id",
      [':account_id' => $this->getCurrentUser()->id()]
    );
  }

  /**
   * Retrieves the name of the table to join the memberships against.
   *
   * @return string
   *   The table name.
   */
  abstract protected function getMembershipJoinTable();

  /**
   * Retrieves the name of the field to join the memberships against.
   *
   * @return string
   *   The field name.
   */
  abstract protected function getMembershipJoinLeftField();

  /**
   * Retrieves the name of the field to join the memberships with.
   *
   * @return string
   *   The field name.
   */
  abstract protected function getMembershipJoinRightField();

  /**
   * Sets up the content for testing.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to create a group with content for.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The group containing the content.
   */
  abstract protected function setUpContent(GroupTypeInterface $group_type);

  /**
   * Adds a no access conditions to the query.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *  The query to add the access check to.
   */
  abstract protected function addNoAccessConditions(SelectInterface $query);

  /**
   * Adds and returns a wrapper condition group if necessary.
   *
   * This method allows subclasses to make more complex groups at the top level
   * of the query conditions.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *  The query to add the condition group to.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface
   *   The query or wrapper condition group.
   */
  protected function addWrapperConditionGroup(SelectInterface $query) {
    return $query;
  }

  /**
   * Adds conditions for the synchronized outsider scope.
   *
   * @param array $allowed_ids
   *   The IDs to grant access to.
   * @param \Drupal\Core\Database\Query\ConditionInterface $conditions
   *  The condition group to add the access checks to.
   * @param bool $outsider
   *  Whether the synchronzed scope is outsider (TRUE) or insider (FALSE).
   */
  abstract protected function addSynchronizedConditions(array $allowed_ids, ConditionInterface $conditions, $outsider);

  /**
   * Adds conditions for the individual scope.
   *
   * @param array $allowed_ids
   *   The IDs to grant access to.
   * @param \Drupal\Core\Database\Query\ConditionInterface $conditions
   *  The condition group to add the access checks to.
   */
  abstract protected function addIndividualConditions(array $allowed_ids, ConditionInterface $conditions);

}
