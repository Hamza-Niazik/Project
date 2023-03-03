<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Defines an abstract test base for group kernel tests.
 */
abstract class GroupKernelTestBase extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity', 'flexible_permissions', 'group', 'options', 'variationcache'];

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->pluginManager = $this->container->get('group_relation_type.manager');

    $this->installEntitySchema('group');
    $this->installEntitySchema('group_relationship');
    $this->installEntitySchema('group_config_wrapper');
    $this->installConfig(['group']);

    // Make sure we do not use user 1.
    $this->createUser();
    $this->setCurrentUser($this->createUser());
  }

  /**
   * Gets the current user so you can run some checks against them.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  protected function getCurrentUser() {
    return $this->container->get('current_user')->getAccount();
  }

  /**
   * Creates a group.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\Group
   *   The created group entity.
   */
  protected function createGroup(array $values = []) {
    $storage = $this->entityTypeManager->getStorage('group');
    $group = $storage->create($values + [
      'label' => $this->randomString(),
    ]);
    $group->enforceIsNew();
    $storage->save($group);
    return $group;
  }

  /**
   * Creates a group type.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\GroupType
   *   The created group type entity.
   */
  protected function createGroupType(array $values = []) {
    $storage = $this->entityTypeManager->getStorage('group_type');
    $group_type = $storage->create($values + [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ]);
    $storage->save($group_type);
    return $group_type;
  }

  /**
   * Creates a group role.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\group\Entity\GroupRole
   *   The created group role entity.
   */
  protected function createGroupRole(array $values = []) {
    $storage = $this->entityTypeManager->getStorage('group_role');
    $group_role = $storage->create($values + [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ]);
    $storage->save($group_role);
    return $group_role;
  }

}
