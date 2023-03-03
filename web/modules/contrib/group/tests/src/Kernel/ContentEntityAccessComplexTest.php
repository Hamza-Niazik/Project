<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\group\Traits\NodeTypeCreationTrait;
use Drupal\user\RoleInterface;

/**
 * Tests that Group properly checks access for "complex" grouped entities.
 *
 * By complex entities we mean entities that can be published or unpublished and
 * have a way of determining who owns the entity.
 *
 * @group group
 */
class ContentEntityAccessComplexTest extends GroupKernelTestBase {

  use NodeTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test_plugin', 'node'];

  /**
   * The node storage to use in testing.
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
   * The required permissions for our node checks.
   *
   * @var string[]
   */
  protected $permissions = [
    'access content',
    'view own unpublished content',
    'edit own page content',
    'edit any page content',
    'delete any page content',
    'delete any page content',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['user', 'group_test_plugin']);
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('node');

    $this->storage = $this->entityTypeManager->getStorage('node');
    $this->accessControlHandler = $this->entityTypeManager->getAccessControlHandler('node');
    $this->createNodeType(['type' => 'page']);
    $this->createNodeType(['type' => 'article']);

    $this->groupTypeA = $this->createGroupType(['id' => 'foo', 'creator_membership' => FALSE]);
    $this->groupTypeB = $this->createGroupType(['id' => 'bar', 'creator_membership' => FALSE]);

    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->save($storage->createFromPlugin($this->groupTypeA, 'node_relation:page'));
    $storage->save($storage->createFromPlugin($this->groupTypeA, 'node_relation:article'));
    $storage->save($storage->createFromPlugin($this->groupTypeB, 'node_relation:page'));
    $storage->save($storage->createFromPlugin($this->groupTypeB, 'node_relation:article'));

    $this->setCurrentUser($this->createUser([], $this->permissions));
  }

  /**
   * Tests that regular access checks work.
   */
  public function testRegularAccess() {
    $node_1 = $this->createNode(['type' => 'page']);
    $this->assertTrue($this->accessControlHandler->access($node_1, 'view'), 'Regular view access works.');
  }

  /**
   * Tests that unsupported operations are not checked.
   */
  public function testUnsupportedOperation() {
    $node = $this->createNode(['type' => 'page']);
    $group = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group->addRelationship($node, 'node_relation:page');

    $result = $this->accessControlHandler->access($node, 'take me to the moon', $this->createUser([], ['access content']), TRUE);
    $this->assertTrue($result->isNeutral(), 'Unsupported operations are not checked.');

    $result = $this->accessControlHandler->access($node, 'view', $this->createUser([], ['access content']), TRUE);
    $this->assertTrue($result->isForbidden(), 'Supported operations are checked.');
  }

  /**
   * Tests that grouped nodes are properly hidden.
   */
  public function testMemberGroupAccessWithoutPermission() {
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page']);

    $group = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group->addRelationship($node_1, 'node_relation:page');
    $group->addMember($this->getCurrentUser());

    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Cannot view the grouped node.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'view'), 'Only the ungrouped node shows up.');
  }

  /**
   * Tests that grouped nodes are properly hidden.
   */
  public function testNonMemberGroupAccessWithoutPermission() {
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page']);

    $group = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group->addRelationship($node_1, 'node_relation:page');

    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Cannot view the grouped node.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'view'), 'Only the ungrouped node shows up.');
  }

  /**
   * Tests that grouped nodes are visible to members.
   */
  public function testMemberGroupAccessWithPermission() {
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page']);

    $this->createGroupRole([
      'group_type' => $this->groupTypeA->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['administer node_relation:page'],
    ]);

    $group = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group->addRelationship($node_1, 'node_relation:page');
    $group->addMember($this->getCurrentUser());

    $this->assertTrue($this->accessControlHandler->access($node_1, 'view'), 'Members can see grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'view'), 'The ungrouped node can be viewed.');
  }

  /**
   * Tests that grouped nodes are visible to non-members.
   */
  public function testNonMemberGroupAccessWithPermission() {
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page']);

    $this->createGroupRole([
      'group_type' => $this->groupTypeA->id(),
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['administer node_relation:page'],
    ]);

    $group = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group->addRelationship($node_1, 'node_relation:page');
    $this->createGroup(['type' => $this->groupTypeA->id()]);

    $this->assertTrue($this->accessControlHandler->access($node_1, 'view'), 'Outsiders can see grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'view'), 'The node can be viewed.');
  }

  /**
   * Tests the viewing of any published grouped entities for members.
   */
  public function testMemberViewAnyPublishedAccess() {
    $account = $this->createUser([], $this->permissions);
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page']);
    $node_3 = $this->createNode(['type' => 'page']);

    // Sanity check: Verify that we don't touch unpublished nodes.
    $node_4 = $this->createNode(['type' => 'page', 'status' => 0]);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addRelationship($node_4, 'node_relation:page');
    $group_a->addMember($this->getCurrentUser());
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_2, 'node_relation:page');
    $group_b->addMember($this->getCurrentUser());
    $group_b->addMember($account);

    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Members cannot see any published grouped nodes without permission.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Members cannot see any published grouped nodes without permission.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The published node can be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');

    $role_config = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view any node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);
    $this->accessControlHandler->resetCache();

    $this->assertTrue($this->accessControlHandler->access($node_1, 'view'), 'Members can see any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'view'), 'Members can see any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The published node can be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');

    $this->setCurrentUser($account);
    $this->assertTrue($this->accessControlHandler->access($node_1, 'view'), 'Members can see any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'view'), 'Members can see any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The published node can be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');

    $this->setCurrentUser($this->createUser([], $this->permissions));
    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Non-members cannot see published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Non-members cannot see published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The published node can be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');
  }

  /**
   * Tests the viewing of any published grouped entities for non-members.
   */
  public function testNonMemberViewAnyPublishedAccess() {
    $account = $this->createUser([], $this->permissions);
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page']);
    $node_3 = $this->createNode(['type' => 'page']);

    // Sanity check: Verify that we don't touch unpublished nodes.
    $node_4 = $this->createNode(['type' => 'page', 'status' => 0]);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addRelationship($node_4, 'node_relation:page');
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_2, 'node_relation:page');
    $group_b->addMember($account);

    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Non-members cannot see any published grouped nodes without permission.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Non-members cannot see any published grouped nodes without permission.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The published node can be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view any node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);
    $this->accessControlHandler->resetCache();

    $this->assertTrue($this->accessControlHandler->access($node_1, 'view'), 'Non-members can see any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'view'), 'Non-members can see any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The published node can be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');

    $this->setCurrentUser($this->createUser([], $this->permissions));
    $this->assertTrue($this->accessControlHandler->access($node_1, 'view'), 'Non-members can see any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'view'), 'Non-members can see any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The published node can be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Members cannot see published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Members cannot see published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The published node can be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');
  }

  /**
   * Tests the viewing of any published grouped entities for anonymous.
   */
  public function testAnonymousViewAnyPublishedAccess() {
    $this->entityTypeManager->getStorage('user_role')->load(RoleInterface::ANONYMOUS_ID)->grantPermission('access content')->save();

    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page']);
    $node_3 = $this->createNode(['type' => 'page']);

    // Sanity check: Verify that we don't touch unpublished nodes.
    $node_4 = $this->createNode(['type' => 'page', 'status' => 0]);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addRelationship($node_4, 'node_relation:page');

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_2, 'node_relation:page');

    $this->setCurrentUser(new AnonymousUserSession());
    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Anonymous cannot see published grouped nodes without permission.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Anonymous cannot see published grouped nodes without permission.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'Anonymous can see published ungrouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::ANONYMOUS_ID,
      'permissions' => ['view any node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);
    $this->accessControlHandler->resetCache();

    $this->assertTrue($this->accessControlHandler->access($node_1, 'view'), 'Anonymous can see any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'view'), 'Anonymous can see any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'Anonymous can see published ungrouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');
  }

  /**
   * Tests the viewing of own published grouped entities for members.
   */
  public function testMemberViewOwnPublishedAccess() {
    $account = $this->createUser([], $this->permissions);
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page', 'uid' => $account->id()]);
    $node_3 = $this->createNode(['type' => 'page']);

    // Sanity check: Verify that we don't touch unpublished nodes.
    $node_4 = $this->createNode(['type' => 'page', 'status' => 0]);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addRelationship($node_4, 'node_relation:page');
    $group_a->addMember($this->getCurrentUser());
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_2, 'node_relation:page');
    $group_b->addMember($this->getCurrentUser());
    $group_b->addMember($account);

    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Members cannot see their own published grouped nodes without permission.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Members cannot see published grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The published node can be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');

    $role_config = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view own node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);
    $this->accessControlHandler->resetCache();

    $this->assertTrue($this->accessControlHandler->access($node_1, 'view'), 'Members can see their own published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Members cannot see published grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The published node can be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Members cannot see published grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'view'), 'Members can see their own published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The published node can be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');

    $this->setCurrentUser($this->createUser([], $this->permissions));
    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Members cannot see published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Members cannot see published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The published node can be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');
  }

  /**
   * Tests the viewing of own published grouped entities for non-members.
   */
  public function testNonMemberViewOwnPublishedAccess() {
    $account = $this->createUser([], $this->permissions);
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page', 'uid' => $account->id()]);
    $node_3 = $this->createNode(['type' => 'page']);

    // Sanity check: Verify that we don't touch unpublished nodes.
    $node_4 = $this->createNode(['type' => 'page', 'status' => 0]);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addRelationship($node_4, 'node_relation:page');
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_2, 'node_relation:page');
    $group_b->addMember($account);

    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Non-members cannot see their own published grouped nodes without permission.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Non-members cannot see published grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The published node can be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view own node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);
    $this->accessControlHandler->resetCache();

    $this->assertTrue($this->accessControlHandler->access($node_1, 'view'), 'Non-members can see their own published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Non-members cannot see published grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The published node can be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');

    $this->setCurrentUser($this->createUser([], $this->permissions));
    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Non-members cannot see published grouped nodes they do not own.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Non-members cannot see published grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The published node can be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Members cannot see published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Members cannot see published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The published node can be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The unpublished grouped node cannot be viewed.');
  }

  /**
   * Tests the viewing of any unpublished grouped entities for members.
   */
  public function testMemberViewAnyUnpublishedAccess() {
    $account = $this->createUser([], $this->permissions);
    $node_1 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_2 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_3 = $this->createNode(['type' => 'page', 'status' => 0]);

    // Sanity check: Verify that we don't touch published nodes.
    $node_4 = $this->createNode(['type' => 'page']);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addRelationship($node_4, 'node_relation:page');
    $group_a->addMember($this->getCurrentUser());
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_2, 'node_relation:page');
    $group_b->addMember($this->getCurrentUser());
    $group_b->addMember($account);

    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Members cannot see any unpublished grouped nodes without permission.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Members cannot see any unpublished grouped nodes without permission.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The unpublished node can be viewed by the owner.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');

    $role_config = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view any unpublished node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);
    $this->accessControlHandler->resetCache();

    $this->assertTrue($this->accessControlHandler->access($node_1, 'view'), 'Members can see any unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'view'), 'Members can see any unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The unpublished node can be viewed by the owner.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');

    $this->setCurrentUser($account);
    $this->assertTrue($this->accessControlHandler->access($node_1, 'view'), 'Members can see any unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'view'), 'Members can see any unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'view'), 'The unpublished node cannot be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');

    $this->setCurrentUser($this->createUser([], $this->permissions));
    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Non-members cannot see unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Non-members cannot see unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'view'), 'The unpublished node cannot be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');
  }

  /**
   * Tests the viewing of any unpublished grouped entities for non-members.
   */
  public function testNonMemberViewAnyUnpublishedAccess() {
    $account = $this->createUser([], $this->permissions);
    $node_1 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_2 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_3 = $this->createNode(['type' => 'page', 'status' => 0]);

    // Sanity check: Verify that we don't touch published nodes.
    $node_4 = $this->createNode(['type' => 'page']);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addRelationship($node_4, 'node_relation:page');
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_2, 'node_relation:page');
    $group_b->addMember($account);

    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Non-members cannot see any unpublished grouped nodes without permission.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Non-members cannot see any unpublished grouped nodes without permission.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The unpublished node can be viewed by the owner.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view any unpublished node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);
    $this->accessControlHandler->resetCache();

    $this->assertTrue($this->accessControlHandler->access($node_1, 'view'), 'Non-members can see any unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'view'), 'Non-members can see any unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The unpublished node can be viewed by the owner.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');

    $this->setCurrentUser($this->createUser([], $this->permissions));
    $this->assertTrue($this->accessControlHandler->access($node_1, 'view'), 'Non-members can see any unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'view'), 'Non-members can see any unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'view'), 'The unpublished node cannot be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Members cannot see unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Members cannot see unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'view'), 'The unpublished node cannot be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');
  }

  /**
   * Tests the viewing of any unpublished grouped entities for anonymous.
   */
  public function testAnonymousViewAnyUnpublishedAccess() {
    $this->entityTypeManager->getStorage('user_role')->load(RoleInterface::ANONYMOUS_ID)->grantPermission('access content')->save();

    $node_1 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_2 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_3 = $this->createNode(['type' => 'page', 'status' => 0]);

    // Sanity check: Verify that we don't touch published nodes.
    $node_4 = $this->createNode(['type' => 'page']);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addRelationship($node_4, 'node_relation:page');

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_2, 'node_relation:page');

    $this->setCurrentUser(new AnonymousUserSession());
    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Anonymous cannot see unpublished grouped nodes without permission.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Anonymous cannot see unpublished grouped nodes without permission.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'view'), 'Anonymous cannot see unpublished ungrouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::ANONYMOUS_ID,
      'permissions' => ['view any unpublished node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);
    $this->accessControlHandler->resetCache();

    $this->assertTrue($this->accessControlHandler->access($node_1, 'view'), 'Anonymous can see any unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'view'), 'Anonymous can see any unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'view'), 'Anonymous cannot see unpublished ungrouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');
  }

  /**
   * Tests the viewing of own unpublished grouped entities for members.
   */
  public function testMemberViewOwnUnpublishedAccess() {
    $account = $this->createUser([], $this->permissions);
    $node_1 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_2 = $this->createNode(['type' => 'page', 'status' => 0, 'uid' => $account->id()]);
    $node_3 = $this->createNode(['type' => 'page', 'status' => 0]);

    // Sanity check: Verify that we don't touch published nodes.
    $node_4 = $this->createNode(['type' => 'page']);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addRelationship($node_4, 'node_relation:page');
    $group_a->addMember($this->getCurrentUser());
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_2, 'node_relation:page');
    $group_b->addMember($this->getCurrentUser());
    $group_b->addMember($account);

    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Members cannot see their own unpublished grouped nodes without permission.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Members cannot see unpublished grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The unpublished node can be viewed by the owner.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');

    $role_config = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view own unpublished node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);
    $this->accessControlHandler->resetCache();

    $this->assertTrue($this->accessControlHandler->access($node_1, 'view'), 'Members can see their own unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Members cannot see unpublished grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The unpublished node can be viewed by the owner.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Members cannot see unpublished grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'view'), 'Members can see their own unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'view'), 'The unpublished node cannot be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');

    $this->setCurrentUser($this->createUser([], $this->permissions));
    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Members cannot see unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Members cannot see unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'view'), 'The unpublished node cannot be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');
  }

  /**
   * Tests the viewing of own unpublished grouped entities for non-members.
   */
  public function testNonMemberViewOwnUnpublishedAccess() {
    $account = $this->createUser([], $this->permissions);
    $node_1 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_2 = $this->createNode(['type' => 'page', 'status' => 0, 'uid' => $account->id()]);
    $node_3 = $this->createNode(['type' => 'page', 'status' => 0]);

    // Sanity check: Verify that we don't touch published nodes.
    $node_4 = $this->createNode(['type' => 'page']);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addRelationship($node_4, 'node_relation:page');
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_2, 'node_relation:page');
    $group_b->addMember($account);

    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Non-members cannot see their own unpublished grouped nodes without permission.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Non-members cannot see unpublished grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The unpublished node can be viewed by the owner.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view own unpublished node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);
    $this->accessControlHandler->resetCache();

    $this->assertTrue($this->accessControlHandler->access($node_1, 'view'), 'Non-members can see their own unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Non-members cannot see unpublished grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'view'), 'The unpublished node can be viewed by the owner.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');

    $this->setCurrentUser($this->createUser([], $this->permissions));
    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Non-members cannot see unpublished grouped nodes they do not own.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Non-members cannot see unpublished grouped nodes they do not own.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'view'), 'The unpublished node cannot be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($node_1, 'view'), 'Members cannot see unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'view'), 'Members cannot see unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'view'), 'The unpublished node cannot be viewed.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'view'), 'The published grouped node cannot be viewed.');
  }

  /**
   * Tests the updating of any grouped entities for members.
   */
  public function testMemberUpdateAnyAccess() {
    $account = $this->createUser([], $this->permissions);
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_3 = $this->createNode(['type' => 'page']);

    $role_config = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['update any node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addMember($this->getCurrentUser());
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_2, 'node_relation:page');
    $group_b->addMember($this->getCurrentUser());
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($node_1, 'update'), 'Members can update any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'update'), 'Members can update any unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'update'), 'The ungrouped node can be updated.');

    $this->setCurrentUser($account);
    $this->assertTrue($this->accessControlHandler->access($node_1, 'update'), 'Members can update any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'update'), 'Members can update any unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'update'), 'The ungrouped node can be updated.');

    $this->setCurrentUser($this->createUser([], $this->permissions));
    $this->assertFalse($this->accessControlHandler->access($node_1, 'update'), 'Non-members cannot update published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'update'), 'Non-members cannot update unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'update'), 'The ungrouped node can be updated.');
  }

  /**
   * Tests the updating of any grouped entities for non-members.
   */
  public function testNonMemberUpdateAnyAccess() {
    $account = $this->createUser([], $this->permissions);
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_3 = $this->createNode(['type' => 'page']);

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['update any node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_2, 'node_relation:page');
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($node_1, 'update'), 'Non-members can update any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'update'), 'Non-members can update any unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'update'), 'The ungrouped node can be updated.');

    $this->setCurrentUser($this->createUser([], $this->permissions));
    $this->assertTrue($this->accessControlHandler->access($node_1, 'update'), 'Non-members can update any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'update'), 'Non-members can update any unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'update'), 'The ungrouped node can be updated.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($node_1, 'update'), 'Members cannot update published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'update'), 'Members cannot update unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'update'), 'The ungrouped node can be updated.');
  }

  /**
   * Tests the updating of own grouped entities for members.
   */
  public function testMemberUpdateOwnAccess() {
    $account = $this->createUser([], $this->permissions);
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_3 = $this->createNode(['type' => 'page', 'uid' => $account->id()]);
    $node_4 = $this->createNode(['type' => 'page', 'uid' => $account->id(), 'status' => 0]);
    $node_5 = $this->createNode(['type' => 'page']);

    $role_config = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['update own node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addRelationship($node_2, 'node_relation:page');
    $group_a->addMember($this->getCurrentUser());
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_3, 'node_relation:page');
    $group_b->addRelationship($node_4, 'node_relation:page');
    $group_b->addMember($this->getCurrentUser());
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($node_1, 'update'), 'Members can update their own published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'update'), 'Members can update their own unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'update'), 'Members cannot update published grouped nodes they do not own.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'update'), 'Members cannot update unpublished grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_5, 'update'), 'The ungrouped node can be updated.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($node_1, 'update'), 'Members cannot update published grouped nodes they do not own.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'update'), 'Members cannot update unpublished grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'update'), 'Members can update their own published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_4, 'update'), 'Members can update their own unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_5, 'update'), 'The ungrouped node can be updated.');

    $this->setCurrentUser($this->createUser([], $this->permissions));
    $this->assertFalse($this->accessControlHandler->access($node_1, 'update'), 'Members cannot update published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'update'), 'Members cannot update unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'update'), 'Members cannot update published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'update'), 'Members cannot update unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_5, 'update'), 'The ungrouped node can be updated.');
  }

  /**
   * Tests the updating of own grouped entities for non-members.
   */
  public function testNonMemberUpdateOwnAccess() {
    $account = $this->createUser([], $this->permissions);
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_3 = $this->createNode(['type' => 'page', 'uid' => $account->id()]);
    $node_4 = $this->createNode(['type' => 'page', 'uid' => $account->id(), 'status' => 0]);
    $node_5 = $this->createNode(['type' => 'page']);

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['update own node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addRelationship($node_2, 'node_relation:page');
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_3, 'node_relation:page');
    $group_b->addRelationship($node_4, 'node_relation:page');
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($node_1, 'update'), 'Non-members can update their own published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'update'), 'Non-members can update their own unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'update'), 'Non-members cannot update published grouped nodes they do not own.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'update'), 'Non-members cannot update unpublished grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_5, 'update'), 'The ungrouped node can be updated.');

    $this->setCurrentUser($this->createUser([], $this->permissions));
    $this->assertFalse($this->accessControlHandler->access($node_1, 'update'), 'Non-members cannot update published grouped nodes they do not own.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'update'), 'Non-members cannot update unpublished grouped nodes they do not own.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'update'), 'Non-members cannot update published grouped nodes they do not own.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'update'), 'Non-members cannot update unpublished grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_5, 'update'), 'The ungrouped node can be updated.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($node_1, 'update'), 'Members cannot update published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'update'), 'Members cannot update unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'update'), 'Members cannot update published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'update'), 'Members cannot update unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_5, 'update'), 'The ungrouped node can be updated.');
  }

  /**
   * Tests the deleting of any grouped entities for members.
   */
  public function testMemberDeleteAnyAccess() {
    $account = $this->createUser([], $this->permissions);
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page', 'status' => 1]);
    $node_3 = $this->createNode(['type' => 'page']);

    $role_config = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['delete any node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addMember($this->getCurrentUser());
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_2, 'node_relation:page');
    $group_b->addMember($this->getCurrentUser());
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($node_1, 'delete'), 'Members can delete any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'delete'), 'Members can delete any unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'delete'), 'The ungrouped node can be deleted.');

    $this->setCurrentUser($account);
    $this->assertTrue($this->accessControlHandler->access($node_1, 'delete'), 'Members can delete any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'delete'), 'Members can delete any unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'delete'), 'The ungrouped node can be deleted.');

    $this->setCurrentUser($this->createUser([], $this->permissions));
    $this->assertFalse($this->accessControlHandler->access($node_1, 'delete'), 'Non-members cannot delete published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'delete'), 'Non-members cannot delete unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'delete'), 'The ungrouped node can be deleted.');
  }

  /**
   * Tests the deleting of any grouped entities for non-members.
   */
  public function testNonMemberDeleteAnyAccess() {
    $account = $this->createUser([], $this->permissions);
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_3 = $this->createNode(['type' => 'page']);

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['delete any node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_2, 'node_relation:page');
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($node_1, 'delete'), 'Non-members can delete any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'delete'), 'Non-members can delete any unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'delete'), 'The ungrouped node can be deleted.');

    $this->setCurrentUser($this->createUser([], $this->permissions));
    $this->assertTrue($this->accessControlHandler->access($node_1, 'delete'), 'Non-members can delete any published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'delete'), 'Non-members can delete any unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'delete'), 'The ungrouped node can be deleted.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($node_1, 'delete'), 'Members cannot delete published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'delete'), 'Members cannot delete unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'delete'), 'The ungrouped node can be deleted.');
  }

  /**
   * Tests the deleting of own grouped entities for members.
   */
  public function testMemberDeleteOwnAccess() {
    $account = $this->createUser([], $this->permissions);
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_3 = $this->createNode(['type' => 'page', 'uid' => $account->id()]);
    $node_4 = $this->createNode(['type' => 'page', 'uid' => $account->id(), 'status' => 0]);
    $node_5 = $this->createNode(['type' => 'page']);

    $role_config = [
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['delete own node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addRelationship($node_2, 'node_relation:page');
    $group_a->addMember($this->getCurrentUser());
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_3, 'node_relation:page');
    $group_b->addRelationship($node_4, 'node_relation:page');
    $group_b->addMember($this->getCurrentUser());
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($node_1, 'delete'), 'Members can delete their own published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'delete'), 'Members can delete their own unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'delete'), 'Members cannot delete published grouped nodes they do not own.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'delete'), 'Members cannot delete unpublished grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_5, 'delete'), 'The ungrouped node can be deleted.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($node_1, 'delete'), 'Members cannot delete published grouped nodes they do not own.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'delete'), 'Members cannot delete unpublished grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_3, 'delete'), 'Members can delete their own published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_4, 'delete'), 'Members can delete their own unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_5, 'delete'), 'The ungrouped node can be deleted.');

    $this->setCurrentUser($this->createUser([], $this->permissions));
    $this->assertFalse($this->accessControlHandler->access($node_1, 'delete'), 'Members cannot delete published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'delete'), 'Members cannot delete unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'delete'), 'Members cannot delete published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'delete'), 'Members cannot delete unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_5, 'delete'), 'The ungrouped node can be deleted.');
  }

  /**
   * Tests the deleting of own grouped entities for non-members.
   */
  public function testNonMemberDeleteOwnAccess() {
    $account = $this->createUser([], $this->permissions);
    $node_1 = $this->createNode(['type' => 'page']);
    $node_2 = $this->createNode(['type' => 'page', 'status' => 0]);
    $node_3 = $this->createNode(['type' => 'page', 'uid' => $account->id()]);
    $node_4 = $this->createNode(['type' => 'page', 'uid' => $account->id(), 'status' => 0]);
    $node_5 = $this->createNode(['type' => 'page']);

    $role_config = [
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['delete own node_relation:page entity'],
    ];
    $this->createGroupRole(['group_type' => $this->groupTypeA->id()] + $role_config);
    $this->createGroupRole(['group_type' => $this->groupTypeB->id()] + $role_config);

    $group_a = $this->createGroup(['type' => $this->groupTypeA->id()]);
    $group_a->addRelationship($node_1, 'node_relation:page');
    $group_a->addRelationship($node_2, 'node_relation:page');
    $group_a->addMember($account);

    $group_b = $this->createGroup(['type' => $this->groupTypeB->id()]);
    $group_b->addRelationship($node_3, 'node_relation:page');
    $group_b->addRelationship($node_4, 'node_relation:page');
    $group_b->addMember($account);

    $this->assertTrue($this->accessControlHandler->access($node_1, 'delete'), 'Non-members can delete their own published grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_2, 'delete'), 'Non-members can delete their own unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'delete'), 'Non-members cannot delete published grouped nodes they do not own.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'delete'), 'Non-members cannot delete unpublished grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_5, 'delete'), 'The ungrouped node can be deleted.');

    $this->setCurrentUser($this->createUser([], $this->permissions));
    $this->assertFalse($this->accessControlHandler->access($node_1, 'delete'), 'Non-members cannot delete published grouped nodes they do not own.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'delete'), 'Non-members cannot delete unpublished grouped nodes they do not own.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'delete'), 'Non-members cannot delete published grouped nodes they do not own.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'delete'), 'Non-members cannot delete unpublished grouped nodes they do not own.');
    $this->assertTrue($this->accessControlHandler->access($node_5, 'delete'), 'The ungrouped node can be deleted.');

    $this->setCurrentUser($account);
    $this->assertFalse($this->accessControlHandler->access($node_1, 'delete'), 'Members cannot delete published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_2, 'delete'), 'Members cannot delete unpublished grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_3, 'delete'), 'Members cannot delete published grouped nodes.');
    $this->assertFalse($this->accessControlHandler->access($node_4, 'delete'), 'Members cannot delete unpublished grouped nodes.');
    $this->assertTrue($this->accessControlHandler->access($node_5, 'delete'), 'The ungrouped node can be deleted.');
  }

  /**
   * Creates a node.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\node\Entity\Node
   *   The created node entity.
   */
  protected function createNode(array $values = []) {
    $node = $this->storage->create($values + [
      'title' => $this->randomString(),
    ]);
    $node->enforceIsNew();
    $this->storage->save($node);
    return $node;
  }

}
