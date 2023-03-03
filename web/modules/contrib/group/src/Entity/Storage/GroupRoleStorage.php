<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\group\PermissionScopeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the storage handler class for group role entities.
 *
 * This extends the base storage class, adding required special handling for
 * loading group role entities based on user and group information.
 */
class GroupRoleStorage extends ConfigEntityStorage implements GroupRoleStorageInterface {

  /**
   * Static cache of a user's group role IDs.
   *
   * @var array
   */
  protected $userGroupRoleIds = [];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group membership loader.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $groupMembershipLoader;

  /**
   * Constructs a GroupRoleStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\GroupMembershipLoaderInterface $group_membership_loader
   *   The group membership loader.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache backend.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupMembershipLoaderInterface $group_membership_loader, EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager, $memory_cache);
    $this->entityTypeManager = $entity_type_manager;
    $this->groupMembershipLoader = $group_membership_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('group.membership_loader'),
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doPreSave(EntityInterface $entity) {
    // Entity storage does not validate constraints by default.
    $violations = $entity->getTypedData()->validate();
    foreach ($violations as $violation) {
      throw new EntityMalformedException($violation->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadByUserAndGroup(AccountInterface $account, GroupInterface $group, $include_synchronized = TRUE) {
    $uid = $account->id();
    $gid = $group->id();
    $key = $include_synchronized ? 'include' : 'exclude';

    if (!isset($this->userGroupRoleIds[$uid][$gid][$key])) {
      $ids = [];

      // Get the IDs from the 'group_roles' field, without loading the roles.
      if ($membership = $this->groupMembershipLoader->load($group, $account)) {
        foreach ($membership->getGroupRelationship()->group_roles as $group_role_ref) {
          $ids[] = $group_role_ref->target_id;
        }
      }

      if ($include_synchronized) {
        $roles = $account->getRoles();
        $query = $this->getQuery()
          ->condition('scope', $membership ? PermissionScopeInterface::INSIDER_ID : PermissionScopeInterface::OUTSIDER_ID)
          ->condition('global_role', $roles, 'IN');
        $ids = array_merge($ids, $query->execute());
      }

      $this->userGroupRoleIds[$uid][$gid][$key] = $ids;
    }

    return $this->loadMultiple($this->userGroupRoleIds[$uid][$gid][$key]);
  }

  /**
   * {@inheritdoc}
   */
  public function resetUserGroupRoleCache(AccountInterface $account, GroupInterface $group = NULL) {
    $uid = $account->id();
    if (isset($group)) {
      unset($this->userGroupRoleIds[$uid][$group->id()]);
    }
    else {
      unset($this->userGroupRoleIds[$uid]);
    }
  }

}
