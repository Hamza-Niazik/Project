<?php

namespace Drupal\group\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;

/**
 * Defines the Group role configuration entity.
 *
 * @ConfigEntityType(
 *   id = "group_role",
 *   label = @Translation("Group role"),
 *   label_singular = @Translation("group role"),
 *   label_plural = @Translation("group roles"),
 *   label_count = @PluralTranslation(
 *     singular = "@count group role",
 *     plural = "@count group roles"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\group\Entity\Storage\GroupRoleStorage",
 *     "access" = "Drupal\group\Entity\Access\GroupRoleAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupRoleForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupRoleForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\group\Entity\Routing\GroupRoleRouteProvider",
 *     },
 *     "list_builder" = "Drupal\group\Entity\Controller\GroupRoleListBuilder",
 *   },
 *   admin_permission = "administer group",
 *   config_prefix = "role",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "weight" = "weight",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/group/types/manage/{group_type}/roles/add",
 *     "collection" = "/admin/group/types/manage/{group_type}/roles",
 *     "delete-form" = "/admin/group/types/manage/{group_type}/roles/{group_role}/delete",
 *     "edit-form" = "/admin/group/types/manage/{group_type}/roles/{group_role}",
 *     "permissions-form" = "/admin/group/types/manage/{group_type}/roles/{group_role}/permissions"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
 *     "admin",
 *     "scope",
 *     "global_role",
 *     "group_type",
 *     "permissions"
 *   },
 *   lookup_keys = {
 *     "scope",
 *     "global_role",
 *     "group_type"
 *   },
 *   constraints = {
 *     "GroupRoleScope" = {}
 *   }
 * )
 */
class GroupRole extends ConfigEntityBase implements GroupRoleInterface {

  /**
   * The machine name of the group role.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the group role.
   *
   * @var string
   */
  protected $label;

  /**
   * The weight of the group role in administrative listings.
   *
   * @var int
   */
  protected $weight;

  /**
   * Whether the group role is an admin role.
   *
   * @var bool
   */
  protected $admin = FALSE;

  /**
   * The scope the role is intended for.
   *
   * Supported values are: 'outsider', 'insider' or 'individual'.
   *
   * @var string
   */
  protected $scope;

  /**
   * The global role ID this group role synchronizes with.
   *
   * Only applies for group roles with a scope value of 'outsider' or 'insider'.
   *
   * @var string
   */
  protected $global_role;

  /**
   * The ID of the group type this role belongs to.
   *
   * @var string
   */
  protected $group_type;

  /**
   * The permissions belonging to the group role.
   *
   * @var string[]
   */
  protected $permissions = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight');
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isAdmin() {
    return $this->admin;
  }

  /**
   * {@inheritdoc}
   */
  public function isAnonymous() {
    return $this->scope == PermissionScopeInterface::OUTSIDER_ID && $this->global_role == RoleInterface::ANONYMOUS_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function isOutsider() {
    return $this->scope == PermissionScopeInterface::OUTSIDER_ID && $this->global_role != RoleInterface::ANONYMOUS_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function isMember() {
    return $this->scope == PermissionScopeInterface::INSIDER_ID || $this->scope == PermissionScopeInterface::INDIVIDUAL_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function getScope() {
    return $this->scope;
  }

  /**
   * {@inheritdoc}
   */
  public function getGlobalRole() {
    if (isset($this->global_role)) {
      return $this->entityTypeManager()->getStorage('user_role')->load($this->global_role);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getGlobalRoleId() {
    return $this->global_role ?? FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupType() {
    return $this->entityTypeManager()->getStorage('group_type')->load($this->group_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupTypeId() {
    return $this->group_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions() {
    return $this->permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    return $this->isAdmin() || in_array($permission, $this->permissions);
  }

  /**
   * {@inheritdoc}
   */
  public function grantPermission($permission) {
    return $this->grantPermissions([$permission]);
  }

  /**
   * {@inheritdoc}
   */
  public function grantPermissions($permissions) {
    if (!$this->isAdmin()) {
      $this->permissions = array_unique(array_merge($this->permissions, $permissions));
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function revokePermission($permission) {
    return $this->revokePermissions([$permission]);
  }

  /**
   * {@inheritdoc}
   */
  public function revokePermissions($permissions) {
    if (!$this->isAdmin()) {
      $this->permissions = array_diff($this->permissions, $permissions);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function changePermissions(array $permissions = []) {
    if (!$this->isAdmin()) {
      // Grant new permissions to the role.
      $grant = array_filter($permissions);
      if (!empty($grant)) {
        $this->grantPermissions(array_keys($grant));
      }

      // Revoke permissions from the role.
      $revoke = array_diff_assoc($permissions, $grant);
      if (!empty($revoke)) {
        $this->revokePermissions(array_keys($revoke));
      }
    }

    return $this;
  }

  /**
   * Returns the group permission handler.
   *
   * @return \Drupal\group\Access\GroupPermissionHandler
   *   The group permission handler.
   */
  protected function getPermissionHandler() {
    return \Drupal::service('group.permissions');
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['group_type'] = $this->getGroupTypeId();
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $this->addDependency('config', $this->getGroupType()->getConfigDependencyName());
    if ($role = $this->getGlobalRole()) {
      $this->addDependency('config', $role->getConfigDependencyName());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    parent::postLoad($storage, $entities);
    // Sort the queried roles by their weight.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, 'static::sort');
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // All group roles need to indicate their scope.
    if (!isset($this->scope)) {
      throw new EntityMalformedException('All group roles require a scope.');
    }

    // Individual roles do not synchronize to a global role.
    if ($this->scope === PermissionScopeInterface::INDIVIDUAL_ID) {
      $this->global_role = NULL;
    }
    // Other scopes need to indicate their target global role.
    elseif (!isset($this->global_role)) {
      throw new EntityMalformedException('Group roles within the outsider or insider scope require a global role to be set.');
    }

    // Anonymous users cannot be members, so avoid this weird scenario.
    if ($this->scope === PermissionScopeInterface::INSIDER_ID && $this->global_role === RoleInterface::ANONYMOUS_ID) {
      throw new EntityMalformedException('Anonymous users cannot be members so you may not create an insider role for the "Anonymous user" role.');
    }

    // No need to store permissions for an admin role.
    if ($this->isAdmin()) {
      $this->permissions = [];
    }

    if (!isset($this->weight) && ($group_roles = $storage->loadMultiple())) {
      // Set a role weight to make this new role last.
      $max = array_reduce($group_roles, function($max, $group_role) {
        return $max > $group_role->weight ? $max : $group_role->weight;
      });

      $this->weight = $max + 1;
    }

    if (!$this->isSyncing()) {
      // Permissions are always ordered alphabetically to avoid conflicts in the
      // exported configuration.
      sort($this->permissions);
    }
  }

}
