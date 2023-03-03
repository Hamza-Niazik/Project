<?php

namespace Drupal\Tests\group\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Provides a base class for Group functional tests.
 */
abstract class GroupBrowserTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group'];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A test user with group creation rights.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupCreator;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Make sure we do not use user 1.
    $this->createUser();

    // Create a user that will server as the group creator.
    $this->groupCreator = $this->createUser($this->getGlobalPermissions());
    $this->drupalLogin($this->groupCreator);
  }

  /**
   * Gets the global (site) permissions for the group creator.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getGlobalPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'access group overview',
    ];
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
  protected function createGroup($values = []) {
    $group = $this->entityTypeManager->getStorage('group')->create($values + [
      'label' => $this->randomMachineName(),
    ]);
    $group->enforceIsNew();
    $group->save();
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
