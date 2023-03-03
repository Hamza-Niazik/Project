<?php

namespace Drupal\group\QueryAccess;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\group\PermissionScopeInterface;

/**
 * Defines a class for altering group queries.
 *
 * @internal
 */
class GroupQueryAlter extends QueryAlterBase {

  /**
   * Whether we're dealing with the revision table.
   *
   * @var bool
   */
  protected $isRevisionTable = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function doAlter($operation) {
    // Do not restrict access on operations we do not support (yet). Same reason
    // as in GroupAccessControlHandler:checkAccess().
    if (!$permission = $this->getPermission($operation)) {
      // For 'view' we also need to see if unpublished is supported.
      if ($operation !== 'view' || !$this->getPermission('view', 'any', TRUE) && !$this->getPermission('view', 'own', TRUE)) {
        return;
      }
    }

    // If any new group is added, it might change access.
    $this->cacheableMetadata->addCacheTags(['group_list']);

    // Retrieve the full list of group permissions for the user.
    $this->cacheableMetadata->addCacheContexts(['user.group_permissions']);
    $calculated_permissions = $this->permissionCalculator->calculateFullPermissions($this->currentUser);

    $allowed_ids = $allowed_any_by_status_ids = $allowed_own_by_status_ids = [];
    foreach ($calculated_permissions->getItems() as $item) {
      if ($item->isAdmin()) {
        $allowed_ids[$item->getScope()][] = $item->getIdentifier();
      }
      elseif ($operation !== 'view') {
        if ($item->hasPermission($permission)) {
          $allowed_ids[$item->getScope()][] = $item->getIdentifier();
        }
      }
      else {
        if ($item->hasPermission($permission)) {
          $allowed_any_by_status_ids[1][$item->getScope()][] = $item->getIdentifier();
        }
        if (($view_any_unpub = $this->getPermission('view', 'any', TRUE)) && $item->hasPermission($view_any_unpub)) {
          $allowed_any_by_status_ids[0][$item->getScope()][] = $item->getIdentifier();
        }
        elseif (($view_own_unpub = $this->getPermission('view', 'own', TRUE)) && $item->hasPermission($view_own_unpub)) {
          $allowed_own_by_status_ids[0][$item->getScope()][] = $item->getIdentifier();
        }
      }
    }

    $has_regular_ids = !empty($allowed_ids);
    $has_status_ids = !empty($allowed_any_by_status_ids) || !empty($allowed_own_by_status_ids);

    // If no group type or group gave access, we deny access altogether.
    if (!$has_regular_ids && !$has_status_ids) {
      $this->query->alwaysFalse();
      return;
    }

    // If we only have regular IDs or status IDs, we can simply add those
    // conditions in their dedicated section below. However, if we have both, we
    // need to add both sections to an OR group to avoid two contradicting
    // membership checks to cancel each other out, leading to no results.
    $condition_attacher = $this->query;
    if ($has_regular_ids && $has_status_ids) {
      $condition_attacher = $this->ensureOrConjunction($this->query);

      // We're going to need a data table anyhow, might as well initialize it
      // here so all group type checks are added to the same table.
      $this->ensureDataTable();
    }

    if ($has_regular_ids) {
      $this->addScopedConditions($allowed_ids, $condition_attacher);
    }

    if ($has_status_ids) {
      foreach ([0, 1] as $status) {
        // Nothing gave access for this status so bail out entirely.
        if (empty($allowed_any_by_status_ids[$status]) && empty($allowed_own_by_status_ids[$status])) {
          continue;
        }

        $data_table = $this->ensureDataTable();
        $condition_attacher->condition($status_conditions = $this->query->andConditionGroup());
        $status_conditions->condition("$data_table.status", $status);
        $status_conditions->condition($status_sub_conditions = $this->query->orConditionGroup());

        if (!empty($allowed_any_by_status_ids[$status])) {
          $this->addScopedConditions($allowed_any_by_status_ids[$status], $status_sub_conditions);
        }

        if (!empty($allowed_own_by_status_ids[$status])) {
          $this->cacheableMetadata->addCacheContexts(['user']);
          $status_sub_conditions->condition($status_owner_conditions = $this->query->andConditionGroup());
          $status_owner_conditions->condition("$data_table.uid", $this->currentUser->id());
          $this->addScopedConditions($allowed_own_by_status_ids[$status], $status_owner_conditions);
        }
      }
    }
  }

  /**
   * Gets the permission name for the given operation and scope.
   *
   * @param string $operation
   *   The operation.
   * @param string $scope
   *   The operation scope ('any' or 'own'). Defaults to 'any'.
   * @param bool $unpublished
   *   Whether to check for the unpublished permission. Defaults to FALSE.
   *
   * @return string
   *   The permission name.
   */
  protected function getPermission($operation, $scope = 'any', $unpublished = FALSE) {
    // @todo We're using this to define operation support, as do we use the same
    //   logic in GroupAccessControlHandler. Ideally, centralize this somewhere.
    switch ($operation) {
      case 'view':
        if ($unpublished) {
          return "$operation $scope unpublished group";
        }
        return 'view group';

      case 'update':
        return 'edit group';

      case 'delete':
        return 'delete group';

      default:
        return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function addSynchronizedConditions(array $allowed_ids, ConditionInterface $scope_conditions, $scope) {
    $membership_alias = $this->ensureMembershipJoin();
    $table_with_type = $this->getTableWithType();

    $sub_condition = $this->query->andConditionGroup();
    $sub_condition->condition("$table_with_type.type", array_unique($allowed_ids), 'IN');
    if ($scope === PermissionScopeInterface::OUTSIDER_ID) {
      $sub_condition->isNull("$membership_alias.entity_id");
    }
    else {
      $sub_condition->isNotNull("$membership_alias.entity_id");
    }
    $scope_conditions->condition($sub_condition);
  }

  /**
   * {@inheritdoc}
   */
  protected function addIndividualConditions(array $allowed_ids, ConditionInterface $scope_conditions) {
    $base_table = $this->ensureBaseTable();
    $scope_conditions->condition("$base_table.id", array_unique($allowed_ids) , 'IN');
  }

  /**
   * Retrieves the best match for a table that has a group type column.
   *
   * @return string
   *   The table alias.
   */
  protected function getTableWithType() {
    // If the data table was joined, use that one. Alternatively, if we are
    // dealing with the revision table, we do not have a type column, so we need
    // to join the data table to have such a column available to us.
    if ($this->dataTableAlias || $this->isRevisionTable) {
      return $this->ensureDataTable();
    }
    return $this->ensureBaseTable();
  }

  /**
   * {@inheritdoc}
   */
  protected function ensureBaseTable() {
    if ($this->baseTableAlias === FALSE) {
      foreach ($this->query->getTables() as $alias => $table) {
        if ($table['join type'] === NULL) {
          $this->baseTableAlias = $alias;

          // Revision tables don't have the type column, so track this.
          if ($table['table'] === 'groups_revision') {
            $this->isRevisionTable = TRUE;
          }
          break;
        }
      }
    }

    return $this->baseTableAlias;
  }

  /**
   * {@inheritdoc}
   */
  protected function getMembershipJoinTable() {
    return $this->ensureBaseTable();
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
    return 'gid';
  }

}
