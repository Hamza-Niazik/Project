<?php

namespace Drupal\flexible_permissions;

use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Defines the calculated permissions interface.
 */
interface CalculatedPermissionsInterface extends CacheableDependencyInterface {

  /**
   * Retrieves a single calculated permission item from a given scope.
   *
   * @param $scope
   *   The scope name to retrieve the item for.
   * @param $identifier
   *   The scope identifier to retrieve the item for.
   *
   * @return \Drupal\flexible_permissions\CalculatedPermissionsItemInterface|false
   *   The calculated permission item or FALSE if it could not be found.
   */
  public function getItem($scope, $identifier);

  /**
   * Retrieves all of the calculated permission items, regardless of scope.
   *
   * @return \Drupal\flexible_permissions\CalculatedPermissionsItemInterface[]
   *   A list of calculated permission items.
   */
  public function getItems();

  /**
   * Retrieves all of the calculated permission items for the given scope.
   *
   * @param string $scope
   *   The scope name to retrieve the items for.
   *
   * @return \Drupal\flexible_permissions\CalculatedPermissionsItemInterface[]
   *   A list of calculated permission items for the given scope.
   */
  public function getItemsByScope($scope);

}
