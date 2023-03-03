<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;

/**
 * Tests that Group properly checks access for grouped entities.
 *
 * @group group
 */
class ContentEntityAccessTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test_plugin', 'node'];

  /**
   * The test entity storage to use in testing.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $storage;

  /**
   * The access control handler to use in testing.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessControlHandler;

  /**
   * The first group type to use in testing.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeA;

  /**
   * The second group type to use in testing.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupTypeB;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_with_owner');

    // Create the authenticated role.
    $this->createRole([], RoleInterface::AUTHENTICATED_ID);

    $this->storage = $this->entityTypeManager->getStorage('entity_test_with_owner');
    $this->accessControlHandler = $this->entityTypeManager->getAccessControlHandler('entity_test_with_owner');

    $this->groupTypeA = $this->createGroupType(['id' => 'foo', 'creator_membership' => FALSE]);
    $this->groupTypeB = $this->createGroupType(['id' => 'bar', 'creator_membership' => FALSE]);

    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->save($storage->createFromPlugin($this->groupTypeA, 'entity_test_relation'));
    $storage->save($storage->createFromPlugin($this->groupTypeB, 'entity_test_relation'));

    $this->setCurrentUser($this->createUser([], ['administer entity_test_with_owner content']));
  }

  /**
   * Tests that regular access checks work.
   */
  public function testRegularAccess() {
    $entity_1 = $this->createTestEntity();
    $this->assertTrue($this->accessControlHandler->access($entity_1, 'view'), 'Regular view access works.');
  }

  /**
   * Tests that unsupported operations are not checked.
   */
  public function testUnsupportedOperation() {
    $entity = $this->createTestEntity();
    $group = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group->addRelationship($entity, 'entity_test_relation');

    $result = $this->accessControlHandler->access($entity, 'take me to the moon', $this->createUser(), TRUE);
    $this->assertTrue($result->isNeutral(), 'Unsupported operations are not checked.');

    $result = $this->accessControlHandler->access($entity, 'view', $this->createUser(), TRUE);
    $this->assertTrue($result->isForbidden(), 'Supported operations are checked.');
  }

  /**
   * Tests that grouped test entities are properly hidden.
   */
  public function testMemberGroupAccessWithoutPermission() {
    $entity_1 = $this->createTestEntity();
    $entity_2 = $this->createTestEntity();

    $group = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group->addRelationship($entity_1, 'entity_test_relation');
    $group->addMember($this->getCurrentUser());

    $this->assertFalse($this->accessControlHandler->access($entity_1, 'view'), 'Cannot view the grouped test entity.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'view'), 'Only the ungrouped test entity shows up.');
  }

  /**
   * Tests that grouped test entities are properly hidden.
   */
  public function testNonMemberGroupAccessWithoutPermission() {
    $entity_1 = $this->createTestEntity();
    $entity_2 = $this->createTestEntity();

    $group = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group->addRelationship($entity_1, 'entity_test_relation');

    $this->assertFalse($this->accessControlHandler->access($entity_1, 'view'), 'Cannot view the grouped test entity.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'view'), 'Only the ungrouped test entity shows up.');
  }

  /**
   * Tests that grouped test entities are visible to members.
   */
  public function testMemberGroupAccessWithPermission() {
    $entity_1 = $this->createTestEntity();
    $entity_2 = $this->createTestEntity();

    $this->createGroupRole([
      'group_type' => $this->groupTypeA->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['administer entity_test_relation'],
    ]);

    $group = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group->addRelationship($entity_1, 'entity_test_relation');
    $group->addMember($this->getCurrentUser());

    $this->assertTrue($this->accessControlHandler->access($entity_1, 'view'), 'Members can see grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'view'), 'The ungrouped test entity can be viewed.');
  }

  /**
   * Tests that grouped test entities are visible to non-members.
   */
  public function testNonMemberGroupAccessWithPermission() {
    $entity_1 = $this->createTestEntity();
    $entity_2 = $this->createTestEntity();

    $this->createGroupRole([
      'group_type' => $this->groupTypeA->id(),
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['administer entity_test_relation'],
    ]);

    $group = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group->addRelationship($entity_1, 'entity_test_relation');
    $this->createGroup(['type' => $this->groupTypeA->id()]);

    $this->assertTrue($this->accessControlHandler->access($entity_1, 'view'), 'Outsiders can see grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'view'), 'The ungrouped test entity can be viewed.');
  }

  /**
   * Tests the viewing of any grouped entities for members.
   */
  public function testMemberViewAnyAccess() {
    $account = $this->createUser([], ['administer entity_test_with_owner content']);
    $entity_1 = $this->createTestEntity();
    $entity_2 = $this->createTestEntity();
    $entity_3 = $this->createTestEntity();

    $role_config = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view any entity_test_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($entity_1, 'entity_test_relation');
    $group_a->addMember($this->getCurrentUser());
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($entity_2, 'entity_test_relation');
    $group_b->addMember($this->getCurrentUser());
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($entity_1, 'view'), 'Members can see any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'view'), 'Members can see any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'view'), 'The ungrouped test entity can be viewed.');

    $this->setCurrentUser($account);
    $this->assertTrue($this->accessControlHandler->access($entity_1, 'view'), 'Members can see any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'view'), 'Members can see any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'view'), 'The ungrouped test entity can be viewed.');

    $this->setCurrentUser($this->createUser([], ['administer entity_test_with_owner content']));
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'view'), 'Non-members cannot see grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'view'), 'Non-members cannot see grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'view'), 'The ungrouped test entity can be viewed.');
  }

  /**
   * Tests the viewing of any grouped entities for non-members.
   */
  public function testNonMemberViewAnyAccess() {
    $account = $this->createUser([], ['administer entity_test_with_owner content']);
    $entity_1 = $this->createTestEntity();
    $entity_2 = $this->createTestEntity();
    $entity_3 = $this->createTestEntity();

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view any entity_test_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($entity_1, 'entity_test_relation');
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($entity_2, 'entity_test_relation');
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($entity_1, 'view'), 'Non-members can see any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'view'), 'Non-members can see any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'view'), 'The ungrouped test entity can be viewed.');

    $this->setCurrentUser($this->createUser([], ['administer entity_test_with_owner content']));
    $this->assertTrue($this->accessControlHandler->access($entity_1, 'view'), 'Non-members can see any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'view'), 'Non-members can see any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'view'), 'The ungrouped test entity can be viewed.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'view'), 'Members cannot see grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'view'), 'Members cannot see grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'view'), 'The ungrouped test entity can be viewed.');
  }

  /**
   * Tests the viewing of own grouped entities for members.
   */
  public function testMemberViewOwnAccess() {
    $account = $this->createUser([], ['administer entity_test_with_owner content']);
    $entity_1 = $this->createTestEntity();
    $entity_2 = $this->createTestEntity(['uid' => $account->id()]);
    $entity_3 = $this->createTestEntity();

    $role_config = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view own entity_test_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($entity_1, 'entity_test_relation');
    $group_a->addMember($this->getCurrentUser());
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($entity_2, 'entity_test_relation');
    $group_b->addMember($this->getCurrentUser());
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($entity_1, 'view'), 'Members can see their own grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'view'), 'Members cannot see grouped test entities they do not own.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'view'), 'The ungrouped test entity can be viewed.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'view'), 'Members cannot see grouped test entities they do not own.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'view'), 'Members can see their own grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'view'), 'The ungrouped test entity can be viewed.');

    $this->setCurrentUser($this->createUser([], ['administer entity_test_with_owner content']));
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'view'), 'Members cannot see grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'view'), 'Members cannot see grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'view'), 'The ungrouped test entity can be viewed.');
  }

  /**
   * Tests the viewing of own grouped entities for non-members.
   */
  public function testNonMemberViewOwnAccess() {
    $account = $this->createUser([], ['administer entity_test_with_owner content']);
    $entity_1 = $this->createTestEntity();
    $entity_2 = $this->createTestEntity(['uid' => $account->id()]);
    $entity_3 = $this->createTestEntity();

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view own entity_test_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($entity_1, 'entity_test_relation');
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($entity_2, 'entity_test_relation');
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($entity_1, 'view'), 'Non-members can see their own grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'view'), 'Non-members cannot see grouped test entities they do not own.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'view'), 'The ungrouped test entity can be viewed.');

    $this->setCurrentUser($this->createUser([], ['administer entity_test_with_owner content']));
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'view'), 'Non-members cannot see grouped test entities they do not own.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'view'), 'Non-members cannot see grouped test entities they do not own.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'view'), 'The ungrouped test entity can be viewed.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'view'), 'Members cannot see grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'view'), 'Members cannot see grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'view'), 'The ungrouped test entity can be viewed.');
  }

  /**
   * Tests the updating of any grouped entities for members.
   */
  public function testMemberUpdateAnyAccess() {
    $account = $this->createUser([], ['administer entity_test_with_owner content']);
    $entity_1 = $this->createTestEntity();
    $entity_2 = $this->createTestEntity();
    $entity_3 = $this->createTestEntity();

    $role_config = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['update any entity_test_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($entity_1, 'entity_test_relation');
    $group_a->addMember($this->getCurrentUser());
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($entity_2, 'entity_test_relation');
    $group_b->addMember($this->getCurrentUser());
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($entity_1, 'update'), 'Members can update any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'update'), 'Members can update any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'update'), 'The ungrouped test entity can be updated.');

    $this->setCurrentUser($account);
    $this->assertTrue($this->accessControlHandler->access($entity_1, 'update'), 'Members can update any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'update'), 'Members can update any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'update'), 'The ungrouped test entity can be updated.');

    $this->setCurrentUser($this->createUser([], ['administer entity_test_with_owner content']));
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'update'), 'Non-members cannot update grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'update'), 'Non-members cannot update grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'update'), 'The ungrouped test entity can be updated.');
  }

  /**
   * Tests the updating of any grouped entities for non-members.
   */
  public function testNonMemberUpdateAnyAccess() {
    $account = $this->createUser([], ['administer entity_test_with_owner content']);
    $entity_1 = $this->createTestEntity();
    $entity_2 = $this->createTestEntity();
    $entity_3 = $this->createTestEntity();

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['update any entity_test_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($entity_1, 'entity_test_relation');
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($entity_2, 'entity_test_relation');
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($entity_1, 'update'), 'Non-members can update any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'update'), 'Non-members can update any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'update'), 'The ungrouped test entity can be updated.');

    $this->setCurrentUser($this->createUser([], ['administer entity_test_with_owner content']));
    $this->assertTrue($this->accessControlHandler->access($entity_1, 'update'), 'Non-members can update any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'update'), 'Non-members can update any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'update'), 'The ungrouped test entity can be updated.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'update'), 'Members cannot update grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'update'), 'Members cannot update grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'update'), 'The ungrouped test entity can be updated.');
  }

  /**
   * Tests the updating of own grouped entities for members.
   */
  public function testMemberUpdateOwnAccess() {
    $account = $this->createUser([], ['administer entity_test_with_owner content']);
    $entity_1 = $this->createTestEntity();
    $entity_2 = $this->createTestEntity(['uid' => $account->id()]);
    $entity_3 = $this->createTestEntity();

    $role_config = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['update own entity_test_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($entity_1, 'entity_test_relation');
    $group_a->addMember($this->getCurrentUser());
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($entity_2, 'entity_test_relation');
    $group_b->addMember($this->getCurrentUser());
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($entity_1, 'update'), 'Members can update their own grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'update'), 'Members cannot update grouped test entities they do not own.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'update'), 'The ungrouped test entity can be updated.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'update'), 'Members cannot update grouped test entities they do not own.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'update'), 'Members can update their own grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'update'), 'The ungrouped test entity can be updated.');

    $this->setCurrentUser($this->createUser([], ['administer entity_test_with_owner content']));
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'update'), 'Members cannot update grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'update'), 'Members cannot update grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'update'), 'The ungrouped test entity can be updated.');
  }

  /**
   * Tests the updating of own grouped entities for non-members.
   */
  public function testNonMemberUpdateOwnAccess() {
    $account = $this->createUser([], ['administer entity_test_with_owner content']);
    $entity_1 = $this->createTestEntity();
    $entity_2 = $this->createTestEntity(['uid' => $account->id()]);
    $entity_3 = $this->createTestEntity();

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['update own entity_test_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($entity_1, 'entity_test_relation');
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($entity_2, 'entity_test_relation');
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($entity_1, 'update'), 'Non-members can update their own grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'update'), 'Non-members cannot update grouped test entities they do not own.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'update'), 'The ungrouped test entity can be updated.');

    $this->setCurrentUser($this->createUser([], ['administer entity_test_with_owner content']));
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'update'), 'Non-members cannot update grouped test entities they do not own.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'update'), 'Non-members cannot update grouped test entities they do not own.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'update'), 'The ungrouped test entity can be updated.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'update'), 'Members cannot update grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'update'), 'Members cannot update grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'update'), 'The ungrouped test entity can be updated.');
  }

  /**
   * Tests the deleting of any grouped entities for members.
   */
  public function testMemberDeleteAnyAccess() {
    $account = $this->createUser([], ['administer entity_test_with_owner content']);
    $entity_1 = $this->createTestEntity();
    $entity_2 = $this->createTestEntity();
    $entity_3 = $this->createTestEntity();

    $role_config = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['delete any entity_test_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($entity_1, 'entity_test_relation');
    $group_a->addMember($this->getCurrentUser());
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($entity_2, 'entity_test_relation');
    $group_b->addMember($this->getCurrentUser());
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($entity_1, 'delete'), 'Members can delete any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'delete'), 'Members can delete any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'delete'), 'The ungrouped test entity can be deleted.');

    $this->setCurrentUser($account);
    $this->assertTrue($this->accessControlHandler->access($entity_1, 'delete'), 'Members can delete any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'delete'), 'Members can delete any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'delete'), 'The ungrouped test entity can be deleted.');

    $this->setCurrentUser($this->createUser([], ['administer entity_test_with_owner content']));
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'delete'), 'Non-members cannot delete grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'delete'), 'Non-members cannot delete grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'delete'), 'The ungrouped test entity can be deleted.');
  }

  /**
   * Tests the deleting of any grouped entities for non-members.
   */
  public function testNonMemberDeleteAnyAccess() {
    $account = $this->createUser([], ['administer entity_test_with_owner content']);
    $entity_1 = $this->createTestEntity();
    $entity_2 = $this->createTestEntity();
    $entity_3 = $this->createTestEntity();

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['delete any entity_test_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($entity_1, 'entity_test_relation');
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($entity_2, 'entity_test_relation');
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($entity_1, 'delete'), 'Non-members can delete any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'delete'), 'Non-members can delete any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'delete'), 'The ungrouped test entity can be deleted.');

    $this->setCurrentUser($this->createUser([], ['administer entity_test_with_owner content']));
    $this->assertTrue($this->accessControlHandler->access($entity_1, 'delete'), 'Non-members can delete any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'delete'), 'Non-members can delete any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'delete'), 'The ungrouped test entity can be deleted.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'delete'), 'Members cannot delete grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'delete'), 'Members cannot delete grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'delete'), 'The ungrouped test entity can be deleted.');
  }

  /**
   * Tests the deleting of own grouped entities for members.
   */
  public function testMemberDeleteOwnAccess() {
    $account = $this->createUser([], ['administer entity_test_with_owner content']);
    $entity_1 = $this->createTestEntity();
    $entity_2 = $this->createTestEntity(['uid' => $account->id()]);
    $entity_3 = $this->createTestEntity();

    $role_config = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['delete own entity_test_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($entity_1, 'entity_test_relation');
    $group_a->addMember($this->getCurrentUser());
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($entity_2, 'entity_test_relation');
    $group_b->addMember($this->getCurrentUser());
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($entity_1, 'delete'), 'Members can delete their own grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'delete'), 'Members cannot delete grouped test entities they do not own.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'delete'), 'The ungrouped test entity can be deleted.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'delete'), 'Members cannot delete grouped test entities they do not own.');
    $this->assertTrue($this->accessControlHandler->access($entity_2, 'delete'), 'Members can delete their own grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'delete'), 'The ungrouped test entity can be deleted.');

    $this->setCurrentUser($this->createUser([], ['administer entity_test_with_owner content']));
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'delete'), 'Members cannot delete grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'delete'), 'Members cannot delete grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'delete'), 'The ungrouped test entity can be deleted.');
  }

  /**
   * Tests the deleting of own grouped entities for non-members.
   */
  public function testNonMemberDeleteOwnAccess() {
    $account = $this->createUser([], ['administer entity_test_with_owner content']);
    $entity_1 = $this->createTestEntity();
    $entity_2 = $this->createTestEntity(['uid' => $account->id()]);
    $entity_3 = $this->createTestEntity();

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['delete own entity_test_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($entity_1, 'entity_test_relation');
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($entity_2, 'entity_test_relation');
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($entity_1, 'delete'), 'Non-members can delete their own grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'delete'), 'Non-members cannot delete grouped test entities they do not own.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'delete'), 'The ungrouped test entity can be deleted.');

    $this->setCurrentUser($this->createUser([], ['administer entity_test_with_owner content']));
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'delete'), 'Non-members cannot delete grouped test entities they do not own.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'delete'), 'Non-members cannot delete grouped test entities they do not own.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'delete'), 'The ungrouped test entity can be deleted.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($entity_1, 'delete'), 'Members cannot delete grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($entity_2, 'delete'), 'Members cannot delete grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($entity_3, 'delete'), 'The ungrouped test entity can be deleted.');
  }

  /**
   * Creates a test entity.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\entity_test\Entity\EntityTest
   *   The created test entity entity.
   */
  protected function createTestEntity(array $values = []) {
    $test_entity = $this->storage->create($values + [
      'name' => $this->randomString(),
    ]);
    $test_entity->enforceIsNew();
    $this->storage->save($test_entity);
    return $test_entity;
  }

}
