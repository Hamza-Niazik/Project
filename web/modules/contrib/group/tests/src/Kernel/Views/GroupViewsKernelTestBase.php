<?php

namespace Drupal\Tests\group\Kernel\Views;

use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\user\RoleInterface;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Defines an abstract test base for group kernel tests for Views.
 */
abstract class GroupViewsKernelTestBase extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity',
    'field',
    'flexible_permissions',
    'group',
    'group_test_views',
    'options',
    'text',
    'variationcache',
  ];

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group type to use in testing.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->installEntitySchema('user');
    $this->installEntitySchema('group');
    $this->installEntitySchema('group_type');
    $this->installEntitySchema('group_relationship');
    $this->installEntitySchema('group_relationship_type');
    $this->installConfig(['group', 'field']);

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->groupType = $this->createGroupType();

    // Allow anyone full group access so query alters don't deny access.
    $role_config = [
      'group_type' => $this->groupType->id(),
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'admin' => TRUE,
    ];
    $this->createGroupRole(['scope' => PermissionScopeInterface::OUTSIDER_ID] + $role_config);
    $this->createGroupRole(['scope' => PermissionScopeInterface::INSIDER_ID] + $role_config);

    // Make sure we do not use user 1.
    $this->createUser();

    // Set the current user so group creation can rely on it.
    $this->container->get('current_user')->setAccount($this->createUser());

    ViewTestData::createTestViews(get_class($this), ['group_test_views']);
  }

  /**
   * Retrieves the results for this test's view.
   *
   * @return \Drupal\views\ResultRow[]
   *   A list of view results.
   */
  protected function getViewResults() {
    $view = Views::getView(reset($this::$testViews));
    $view->setDisplay();

    if ($view->preview()) {
      return $view->result;
    }

    return [];
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
      'type' => $this->groupType->id(),
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

  /**
   * Creates a user.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\user\Entity\User
   *   The created user entity.
   */
  protected function createUser($values = []) {
    $account = $this->entityTypeManager->getStorage('user')->create($values + [
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);
    $account->enforceIsNew();
    $account->save();
    return $account;
  }

}
