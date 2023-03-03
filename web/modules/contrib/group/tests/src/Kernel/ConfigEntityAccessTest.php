<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\group\Traits\NodeTypeCreationTrait;
use Drupal\user\RoleInterface;

/**
 * Tests that Group properly checks access for grouped entities.
 *
 * @group group
 */
class ConfigEntityAccessTest extends GroupKernelTestBase {

  use NodeTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test_plugin', 'node'];

  /**
   * The test entity storage to use in testing.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
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
   * The permissions required to deal with ungrouped node types.
   * @var array
   */
  protected $nodeTypePermissions = [
    'access content',
    'administer content types',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('node_type');

    // Create the authenticated role.
    $this->createRole([], RoleInterface::AUTHENTICATED_ID);

    $this->storage = $this->entityTypeManager->getStorage('node_type');
    $this->accessControlHandler = $this->entityTypeManager->getAccessControlHandler('node_type');

    $this->groupTypeA = $this->createGroupType(['id' => 'foo', 'creator_membership' => FALSE]);
    $this->groupTypeB = $this->createGroupType(['id' => 'bar', 'creator_membership' => FALSE]);

    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->save($storage->createFromPlugin($this->groupTypeA, 'node_type_relation'));
    $storage->save($storage->createFromPlugin($this->groupTypeB, 'node_type_relation'));

    $this->setCurrentUser($this->createUser([], $this->nodeTypePermissions));
  }

  /**
   * Tests that regular access checks work.
   */
  public function testRegularAccess() {
    $node_type = $this->createNodeType();
    $this->assertTrue($this->accessControlHandler->access($node_type, 'view'), 'Regular view access works.');
  }

  /**
   * Tests that unsupported operations are not checked.
   */
  public function testUnsupportedOperation() {
    $node_type = $this->createNodeType();
    $group = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group->addRelationship($node_type, 'node_type_relation');

    $result = $this->accessControlHandler->access($node_type, 'take me to the moon', $this->createUser(), TRUE);
    $this->assertTrue($result->isNeutral(), 'Unsupported operations are not checked.');

    $result = $this->accessControlHandler->access($node_type, 'view', $this->createUser(), TRUE);
    $this->assertTrue($result->isForbidden(), 'Supported operations are checked.');
  }

  /**
   * Tests that grouped test entities are properly hidden.
   */
  public function testMemberGroupAccessWithoutPermission() {
    $node_type_1 = $this->createNodeType();
    $node_type_2 = $this->createNodeType();

    $group = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group->addRelationship($node_type_1, 'node_type_relation');
    $group->addMember($this->getCurrentUser());

    $this->assertFalse($this->accessControlHandler->access($node_type_1, 'view'), 'Cannot view the grouped test entity.');
    $this->assertTrue($this->accessControlHandler->access($node_type_2, 'view'), 'Only the ungrouped test entity shows up.');
  }

  /**
   * Tests that grouped test entities are properly hidden.
   */
  public function testNonMemberGroupAccessWithoutPermission() {
    $node_type_1 = $this->createNodeType();
    $node_type_2 = $this->createNodeType();

    $group = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group->addRelationship($node_type_1, 'node_type_relation');

    $this->assertFalse($this->accessControlHandler->access($node_type_1, 'view'), 'Cannot view the grouped test entity.');
    $this->assertTrue($this->accessControlHandler->access($node_type_2, 'view'), 'Only the ungrouped test entity shows up.');
  }

  /**
   * Tests that grouped test entities are visible to members.
   */
  public function testMemberGroupAccessWithPermission() {
    $node_type_1 = $this->createNodeType();
    $node_type_2 = $this->createNodeType();

    $this->createGroupRole([
      'group_type' => $this->groupTypeA->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['administer node_type_relation'],
    ]);

    $group = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group->addRelationship($node_type_1, 'node_type_relation');
    $group->addMember($this->getCurrentUser());

    $this->assertTrue($this->accessControlHandler->access($node_type_1, 'view'), 'Members can see grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_2, 'view'), 'The ungrouped test entity can be viewed.');
  }

  /**
   * Tests that grouped test entities are visible to non-members.
   */
  public function testNonMemberGroupAccessWithPermission() {
    $node_type_1 = $this->createNodeType();
    $node_type_2 = $this->createNodeType();

    $this->createGroupRole([
      'group_type' => $this->groupTypeA->id(),
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['administer node_type_relation'],
    ]);

    $group = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group->addRelationship($node_type_1, 'node_type_relation');
    $this->createGroup(['type' => $this->groupTypeA->id()]);

    $this->assertTrue($this->accessControlHandler->access($node_type_1, 'view'), 'Outsiders can see grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_2, 'view'), 'The ungrouped test entity can be viewed.');
  }

  /**
   * Tests the viewing of any grouped entities for members.
   */
  public function testMemberViewAnyAccess() {
    $account = $this->createUser([], $this->nodeTypePermissions);
    $node_type_1 = $this->createNodeType();
    $node_type_2 = $this->createNodeType();
    $node_type_3 = $this->createNodeType();

    $role_config = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view node_type_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_type_1, 'node_type_relation');
    $group_a->addMember($this->getCurrentUser());
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_type_2, 'node_type_relation');
    $group_b->addMember($this->getCurrentUser());
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($node_type_1, 'view'), 'Members can see any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_2, 'view'), 'Members can see any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'view'), 'The ungrouped test entity can be viewed.');

    $this->setCurrentUser($account);
    $this->assertTrue($this->accessControlHandler->access($node_type_1, 'view'), 'Members can see any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_2, 'view'), 'Members can see any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'view'), 'The ungrouped test entity can be viewed.');

    $this->setCurrentUser($this->createUser([], $this->nodeTypePermissions));
    $this->assertFalse($this->accessControlHandler->access($node_type_1, 'view'), 'Non-members cannot see grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($node_type_2, 'view'), 'Non-members cannot see grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'view'), 'The ungrouped test entity can be viewed.');
  }

  /**
   * Tests the viewing of any grouped entities for non-members.
   */
  public function testNonMemberViewAnyAccess() {
    $account = $this->createUser([], $this->nodeTypePermissions);
    $node_type_1 = $this->createNodeType();
    $node_type_2 = $this->createNodeType();
    $node_type_3 = $this->createNodeType();

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view node_type_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_type_1, 'node_type_relation');
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_type_2, 'node_type_relation');
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($node_type_1, 'view'), 'Non-members can see any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_2, 'view'), 'Non-members can see any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'view'), 'The ungrouped test entity can be viewed.');

    $this->setCurrentUser($this->createUser([], $this->nodeTypePermissions));
    $this->assertTrue($this->accessControlHandler->access($node_type_1, 'view'), 'Non-members can see any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_2, 'view'), 'Non-members can see any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'view'), 'The ungrouped test entity can be viewed.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($node_type_1, 'view'), 'Members cannot see grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($node_type_2, 'view'), 'Members cannot see grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'view'), 'The ungrouped test entity can be viewed.');
  }

  /**
   * Tests the updating of any grouped entities for members.
   */
  public function testMemberUpdateAnyAccess() {
    $account = $this->createUser([], $this->nodeTypePermissions);
    $node_type_1 = $this->createNodeType();
    $node_type_2 = $this->createNodeType();
    $node_type_3 = $this->createNodeType();

    $role_config = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['update any node_type_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_type_1, 'node_type_relation');
    $group_a->addMember($this->getCurrentUser());
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_type_2, 'node_type_relation');
    $group_b->addMember($this->getCurrentUser());
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($node_type_1, 'update'), 'Members can update any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_2, 'update'), 'Members can update any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'update'), 'The ungrouped test entity can be updated.');

    $this->setCurrentUser($account);
    $this->assertTrue($this->accessControlHandler->access($node_type_1, 'update'), 'Members can update any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_2, 'update'), 'Members can update any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'update'), 'The ungrouped test entity can be updated.');

    $this->setCurrentUser($this->createUser([], $this->nodeTypePermissions));
    $this->assertFalse($this->accessControlHandler->access($node_type_1, 'update'), 'Non-members cannot update grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($node_type_2, 'update'), 'Non-members cannot update grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'update'), 'The ungrouped test entity can be updated.');
  }

  /**
   * Tests the updating of any grouped entities for non-members.
   */
  public function testNonMemberUpdateAnyAccess() {
    $account = $this->createUser([], $this->nodeTypePermissions);
    $node_type_1 = $this->createNodeType();
    $node_type_2 = $this->createNodeType();
    $node_type_3 = $this->createNodeType();

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['update any node_type_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_type_1, 'node_type_relation');
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_type_2, 'node_type_relation');
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($node_type_1, 'update'), 'Non-members can update any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_2, 'update'), 'Non-members can update any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'update'), 'The ungrouped test entity can be updated.');

    $this->setCurrentUser($this->createUser([], $this->nodeTypePermissions));
    $this->assertTrue($this->accessControlHandler->access($node_type_1, 'update'), 'Non-members can update any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_2, 'update'), 'Non-members can update any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'update'), 'The ungrouped test entity can be updated.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($node_type_1, 'update'), 'Members cannot update grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($node_type_2, 'update'), 'Members cannot update grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'update'), 'The ungrouped test entity can be updated.');
  }

  /**
   * Tests the deleting of any grouped entities for members.
   */
  public function testMemberDeleteAnyAccess() {
    $account = $this->createUser([], $this->nodeTypePermissions);
    $node_type_1 = $this->createNodeType();
    $node_type_2 = $this->createNodeType();
    $node_type_3 = $this->createNodeType();

    $role_config = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['delete any node_type_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_type_1, 'node_type_relation');
    $group_a->addMember($this->getCurrentUser());
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_type_2, 'node_type_relation');
    $group_b->addMember($this->getCurrentUser());
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($node_type_1, 'delete'), 'Members can delete any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_2, 'delete'), 'Members can delete any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'delete'), 'The ungrouped test entity can be deleted.');

    $this->setCurrentUser($account);
    $this->assertTrue($this->accessControlHandler->access($node_type_1, 'delete'), 'Members can delete any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_2, 'delete'), 'Members can delete any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'delete'), 'The ungrouped test entity can be deleted.');

    $this->setCurrentUser($this->createUser([], $this->nodeTypePermissions));
    $this->assertFalse($this->accessControlHandler->access($node_type_1, 'delete'), 'Non-members cannot delete grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($node_type_2, 'delete'), 'Non-members cannot delete grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'delete'), 'The ungrouped test entity can be deleted.');
  }

  /**
   * Tests the deleting of any grouped entities for non-members.
   */
  public function testNonMemberDeleteAnyAccess() {
    $account = $this->createUser([], $this->nodeTypePermissions);
    $node_type_1 = $this->createNodeType();
    $node_type_2 = $this->createNodeType();
    $node_type_3 = $this->createNodeType();

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['delete any node_type_relation entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_type_1, 'node_type_relation');
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_type_2, 'node_type_relation');
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($node_type_1, 'delete'), 'Non-members can delete any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_2, 'delete'), 'Non-members can delete any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'delete'), 'The ungrouped test entity can be deleted.');

    $this->setCurrentUser($this->createUser([], $this->nodeTypePermissions));
    $this->assertTrue($this->accessControlHandler->access($node_type_1, 'delete'), 'Non-members can delete any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_2, 'delete'), 'Non-members can delete any grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'delete'), 'The ungrouped test entity can be deleted.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($node_type_1, 'delete'), 'Members cannot delete grouped test entities.');
    $this->assertFalse($this->accessControlHandler->access($node_type_2, 'delete'), 'Members cannot delete grouped test entities.');
    $this->assertTrue($this->accessControlHandler->access($node_type_3, 'delete'), 'The ungrouped test entity can be deleted.');
  }

}
