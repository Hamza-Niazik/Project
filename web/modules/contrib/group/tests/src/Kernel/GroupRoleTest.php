<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;

/**
 * Tests the general behavior of group role entities.
 *
 * @coversDefaultClass \Drupal\group\Entity\GroupRole
 * @group group
 */
class GroupRoleTest extends GroupKernelTestBase {

  /**
   * The group role entity to run tests on.
   *
   * @var \Drupal\group\Entity\GroupRoleInterface
   */
  protected $groupRole;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createGroupType(['id' => 'foo']);

    $storage = $this->entityTypeManager->getStorage('group_role');
    $this->groupRole = $storage->create([
      'id' => 'test-role',
      'label' => 'test label',
      'weight' => 1986,
      'admin' => FALSE,
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
      'group_type' => 'foo',
      'permissions' => [],
    ]);
    $storage->save($this->groupRole);
  }

  /**
   * Tests the weight getter.
   *
   * @covers ::getWeight
   */
  public function testGetWeight() {
    $this->assertEquals(1986, $this->groupRole->getWeight());
  }

  /**
   * Tests the weight setter.
   *
   * @covers ::setWeight
   * @depends testGetWeight
   */
  public function testSetWeight() {
    $this->groupRole->setWeight(1991);
    $this->assertEquals(1991, $this->groupRole->getWeight());
  }

  /**
   * Tests the admin check.
   *
   * @covers ::isAdmin
   */
  public function testIsAdmin() {
    $this->assertEquals(FALSE, $this->groupRole->isAdmin());
    $this->groupRole->set('admin', TRUE);
    $this->assertEquals(TRUE, $this->groupRole->isAdmin());
  }

  /**
   * Tests the anonymous check.
   *
   * @covers ::isAnonymous
   */
  public function testIsAnonymous() {
    $this->assertEquals(FALSE, $this->groupRole->isAnonymous());

    $this->groupRole->set('scope', PermissionScopeInterface::OUTSIDER_ID);
    $this->groupRole->set('global_role', RoleInterface::ANONYMOUS_ID);
    $this->assertEquals(TRUE, $this->groupRole->isAnonymous());

    $this->groupRole->set('global_role', 'a-random-id');
    $this->assertEquals(FALSE, $this->groupRole->isAnonymous());
  }

  /**
   * Tests the outsider check.
   *
   * @covers ::isOutsider
   */
  public function testIsOutsider() {
    $this->assertEquals(FALSE, $this->groupRole->isOutsider());

    $this->groupRole->set('scope', PermissionScopeInterface::OUTSIDER_ID);
    $this->groupRole->set('global_role', RoleInterface::AUTHENTICATED_ID);
    $this->assertEquals(TRUE, $this->groupRole->isOutsider());

    $this->groupRole->set('global_role', 'a-random-id');
    $this->assertEquals(TRUE, $this->groupRole->isOutsider());
  }

  /**
   * Tests the outsider check.
   *
   * @covers ::isMember
   */
  public function testIsMember() {
    $this->assertEquals(TRUE, $this->groupRole->isMember());

    $this->groupRole->set('scope', PermissionScopeInterface::INSIDER_ID);
    $this->assertEquals(TRUE, $this->groupRole->isMember());

    $this->groupRole->set('scope', PermissionScopeInterface::OUTSIDER_ID);
    $this->assertEquals(FALSE, $this->groupRole->isMember());
  }

  /**
   * Tests the scope getter.
   *
   * @covers ::getScope
   */
  public function testGetScope() {
    $this->assertEquals(PermissionScopeInterface::INDIVIDUAL_ID, $this->groupRole->getScope());
  }

  /**
   * Tests the global role getter.
   *
   * @covers ::getGlobalRole
   */
  public function testGetGlobalRole() {
    $this->assertEquals(NULL, $this->groupRole->getGlobalRole());

    $role_id = $this->createRole([], RoleInterface::AUTHENTICATED_ID);
    $this->groupRole->set('global_role', $role_id);
    $role = $this->entityTypeManager->getStorage('user_role')->load($role_id);
    $this->assertEquals($role, $this->groupRole->getGlobalRole());
  }

  /**
   * Tests the global role ID getter.
   *
   * @covers ::getGlobalRoleId
   */
  public function testGetGlobalRoleId() {
    $this->assertEquals(NULL, $this->groupRole->getGlobalRoleId());

    $role_id = $this->createRole([], RoleInterface::AUTHENTICATED_ID);
    $this->groupRole->set('global_role', $role_id);
    $this->assertEquals($role_id, $this->groupRole->getGlobalRoleId());
  }

  /**
   * Tests the group type getter.
   *
   * @covers ::getGroupType
   */
  public function testGetGroupType() {
    $group_type = $this->entityTypeManager->getStorage('group_type')->load('foo');
    $this->assertEquals($group_type, $this->groupRole->getGroupType());
  }

  /**
   * Tests the group type ID getter.
   *
   * @covers ::getGroupTypeId
   */
  public function testGetGroupTypeId() {
    $this->assertEquals('foo', $this->groupRole->getGroupTypeId());
  }

  /**
   * Tests the weight getter.
   *
   * @covers ::getPermissions
   */
  public function testGetPermissions() {
    $this->assertEquals([], $this->groupRole->getPermissions());
  }

  /**
   * Tests the permission checker.
   *
   * @covers ::hasPermission
   */
  public function testHasPermission() {
    $this->assertFalse($this->groupRole->hasPermission('view group'));
    $this->groupRole->set('permissions', ['view group']);
    $this->assertTrue($this->groupRole->hasPermission('view group'));
    $this->groupRole->set('admin', TRUE);
    $this->assertTrue($this->groupRole->hasPermission('edit group'));
  }

  /**
   * Tests the permission granting.
   *
   * @covers ::grantPermission
   * @depends testHasPermission
   */
  public function testGrantPermission() {
    $this->assertFalse($this->groupRole->hasPermission('view group'));
    $this->groupRole->grantPermission('view group');
    $this->assertTrue($this->groupRole->hasPermission('view group'));
  }

  /**
   * Tests the multiple permission granting.
   *
   * @covers ::grantPermissions
   * @depends testHasPermission
   */
  public function testGrantPermissions() {
    $this->assertFalse($this->groupRole->hasPermission('view group'));
    $this->assertFalse($this->groupRole->hasPermission('edit group'));
    $this->groupRole->grantPermissions(['view group', 'edit group']);
    $this->assertTrue($this->groupRole->hasPermission('view group'));
    $this->assertTrue($this->groupRole->hasPermission('edit group'));
  }

  /**
   * Tests the permission revoking.
   *
   * @covers ::revokePermission
   * @depends testHasPermission
   * @depends testGrantPermission
   */
  public function testRevokePermission() {
    $this->groupRole->grantPermission('view group');
    $this->groupRole->revokePermission('view group');
    $this->assertFalse($this->groupRole->hasPermission('view group'));
  }

  /**
   * Tests the multiple permission revoking.
   *
   * @covers ::revokePermissions
   * @depends testHasPermission
   * @depends testGrantPermissions
   */
  public function testRevokePermissions() {
    $this->groupRole->grantPermissions(['view group', 'edit group']);
    $this->groupRole->revokePermissions(['view group', 'edit group']);
    $this->assertFalse($this->groupRole->hasPermission('view group'));
    $this->assertFalse($this->groupRole->hasPermission('edit group'));
  }

  /**
   * Tests the multiple permission changing.
   *
   * @covers ::changePermissions
   * @depends testHasPermission
   * @depends testGrantPermissions
   */
  public function testChangePermissions() {
    $this->groupRole->grantPermissions(['view group', 'edit group']);
    $this->groupRole->changePermissions([
      'view group' => 1,
      'edit group' => 0,
      'delete group' => 1,
    ]);
    $this->assertTrue($this->groupRole->hasPermission('view group'));
    $this->assertFalse($this->groupRole->hasPermission('edit group'));
    $this->assertTrue($this->groupRole->hasPermission('delete group'));
  }

}
