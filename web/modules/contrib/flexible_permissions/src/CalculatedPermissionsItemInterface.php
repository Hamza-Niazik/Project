<?php

namespace Drupal\flexible_permissions;

/**
 * Defines the calculated permissions item interface.
 */
interface CalculatedPermissionsItemInterface {

  /**
   * Returns the scope of the calculated permissions item.
   *
   * @return string
   *   The scope name.
   */
  public function getScope();

  /**
   * Returns the identifier within the scope.
   *
   * @return string|int
   *   The identifier.
   */
  public function getIdentifier();

  /**
   * Returns the permissions for the calculated permissions item.
   *
   * @return string[]
   *   The permission names.
   */
  public function getPermissions();

  /**
   * Returns whether this item grants admin privileges in its scope.
   *
   * @return bool
   *   Whether this item grants admin privileges.
   */
  public function isAdmin();

  /**
   * Returns whether this item has a given permission.
   *
   * This should take ::isAdmin() into account.
   *
   * @param string $permission
   *   The permission name.
   *
   * @return bool
   *   Whether this item has the permission.
   */
  public function hasPermission($permission);

}
