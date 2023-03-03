<?php

namespace Drupal\group\QueryAccess;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupPermissionCalculatorInterface;
use Drupal\group\PermissionScopeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Base class for query alter classes.
 *
 * @internal
 */
abstract class QueryAlterBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group permission calculator.
   *
   * @var \Drupal\group\Access\GroupPermissionCalculatorInterface
   */
  protected $permissionCalculator;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The query to alter.
   *
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $query;

  /**
   * The entity type to alter the query for.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The query cacheable metadata.
   *
   * @var \Drupal\Core\Cache\CacheableMetadata
   */
  protected $cacheableMetadata;

  /**
   * The base table alias.
   *
   * @var string|false
   */
  protected $baseTableAlias = FALSE;

  /**
   * The data table alias.
   *
   * @var string|false
   */
  protected $dataTableAlias = FALSE;

  /**
   * The alias for the join to the memberships.
   *
   * @var string|false
   */
  protected $joinAliasMemberships = FALSE;

  /**
   * Constructs a new QueryAlterBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Access\GroupPermissionCalculatorInterface $permission_calculator
   *   The group permission calculator.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupPermissionCalculatorInterface $permission_calculator, RendererInterface $renderer, RequestStack $request_stack, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->permissionCalculator = $permission_calculator;
    $this->renderer = $renderer;
    $this->requestStack = $request_stack;
    $this->currentUser = $current_user;
    $this->cacheableMetadata = new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('group_permission.calculator'),
      $container->get('renderer'),
      $container->get('request_stack'),
      $container->get('current_user')
    );
  }

  /**
   * Alters the select query for the given entity type.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The select query.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   */
  public function alter(SelectInterface $query, EntityTypeInterface $entity_type) {
    $this->query = $query;
    $this->entityType = $entity_type;
    $this->doAlter($query->getMetaData('op') ?: 'view');
    $this->applyCacheability();
  }

  /**
   * Actually alters the select query.
   *
   * @param string $operation
   *   The query operation.
   */
  abstract protected function doAlter($operation);

  /**
   * Adds conditions for all scopes given a set of IDs where access is array.
   *
   * @param array $allowed_ids
   *   A set of scope identifiers where access is granted for each scope. Keys
   *   are scope names and values are determined by the implementing class.
   * @param \Drupal\Core\Database\Query\ConditionInterface $parent_condition
   *  The parent condition to add the subconditions to.
   */
  protected function addScopedConditions(array $allowed_ids, ConditionInterface $parent_condition) {
    $scope_conditions = $this->ensureOrConjunction($parent_condition);

    // Add the group types where synchronized access is granted.
    foreach ([PermissionScopeInterface::OUTSIDER_ID, PermissionScopeInterface::INSIDER_ID] as $scope) {
      if (!empty($allowed_ids[$scope])) {
        $this->addSynchronizedConditions($allowed_ids[$scope], $scope_conditions, $scope);
      }
    }

    // Add the groups where individual access is granted.
    if (!empty($allowed_ids[PermissionScopeInterface::INDIVIDUAL_ID])) {
      $this->addIndividualConditions($allowed_ids[PermissionScopeInterface::INDIVIDUAL_ID], $scope_conditions);
    }
  }

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

    $parent->condition($or_group = $this->query->orConditionGroup());
    return $or_group;
  }

  /**
   * Adds conditions for a synchronized scope.
   *
   * @param array $allowed_ids
   *   The IDs to grant access to, as ddefined by the implementing class.
   * @param \Drupal\Core\Database\Query\ConditionInterface $scope_conditions
   *  The condition group to add the access checks to.
   * @param string $scope
   *   The name of the synchronized scope, either 'outsider' or 'insider'.
   */
  abstract protected function addSynchronizedConditions(array $allowed_ids, ConditionInterface $scope_conditions, $scope);

  /**
   * Adds conditions for the individual scope.
   *
   * @param array $allowed_ids
   *   The IDs to grant access to, as ddefined by the implementing class.
   * @param \Drupal\Core\Database\Query\ConditionInterface $scope_conditions
   *  The condition group to add the access checks to.
   */
  abstract protected function addIndividualConditions(array $allowed_ids, ConditionInterface $scope_conditions);

  /**
   * Ensures the query has a base table.
   *
   * @return string
   *   The base table alias.
   */
  protected function ensureBaseTable() {
    if ($this->baseTableAlias === FALSE) {
      foreach ($this->query->getTables() as $alias => $table) {
        if ($table['join type'] === NULL) {
          $this->baseTableAlias = $alias;
          break;
        }
      }
    }

    return $this->baseTableAlias;
  }

  /**
   * Ensures the query is joined against the data table.
   *
   * @return string
   *   The data table alias.
   */
  protected function ensureDataTable() {
    if ($this->dataTableAlias === FALSE) {
      $base_table = $this->ensureBaseTable();

      if (!$data_table = $this->entityType->getDataTable()) {
        $data_table = $base_table;
        $data_table_found = TRUE;
      }
      else {
        $data_table_found = FALSE;

        foreach ($this->query->getTables() as $alias => $table) {
          if (!$data_table_found && ($table['join type'] === 'INNER' || $alias === $base_table) && $table['table'] === $data_table) {
            $data_table = $alias;
            $data_table_found = TRUE;
            break;
          }
        }
      }

      // If the data table wasn't added to the query yet, add it here.
      if (!$data_table_found) {
        $id_key = $this->entityType->getKey('id');
        $this->dataTableAlias = $this->query->join($data_table, $data_table, "$base_table.$id_key=$data_table.$id_key");
      }
      else {
        $this->dataTableAlias = $data_table;
      }
    }

    return $this->dataTableAlias;
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
   * Ensures the query is joined with the memberships.
   *
   * @return string
   *   The membership join alias.
   */
  protected function ensureMembershipJoin() {
    if ($this->joinAliasMemberships === FALSE) {
      $table = $this->getMembershipJoinTable();
      $l_field = $this->getMembershipJoinLeftField();
      $r_field = $this->getMembershipJoinRightField();

      // Join the memberships of the current user.
      $group_relationship_data_table = $this->entityTypeManager->getDefinition('group_relationship')->getDataTable();
      $this->joinAliasMemberships = $this->query->leftJoin(
        $group_relationship_data_table,
        'gcfd',
        "$table.$l_field=%alias.$r_field AND %alias.plugin_id='group_membership' AND %alias.entity_id=:account_id",
        [':account_id' => $this->currentUser->id()]
      );
    }

    return $this->joinAliasMemberships;
  }

  /**
   * Applies the cacheablity metadata to the current request.
   */
  protected function applyCacheability() {
    $request = $this->requestStack->getCurrentRequest();
    if ($request->isMethodCacheable() && $this->renderer->hasRenderContext() && $this->hasCacheableMetadata()) {
      $build = [];
      $this->cacheableMetadata->applyTo($build);
      $this->renderer->render($build);
    }
  }

  /**
   * Check if the cacheable metadata is not empty.
   *
   * An empty cacheable metadata object has no context, tags, and is permanent.
   *
   * @return bool
   *   TRUE if there is cacheability metadata, otherwise FALSE.
   */
  protected function hasCacheableMetadata() {
    return $this->cacheableMetadata->getCacheMaxAge() !== Cache::PERMANENT
      || count($this->cacheableMetadata->getCacheContexts()) > 0
      || count($this->cacheableMetadata->getCacheTags()) > 0;
  }

}
