<?php

namespace Drupal\Tests\group\Unit;

use Drupal\Core\Session\AccountInterface;
use Drupal\flexible_permissions\CalculatedPermissionsItem;
use Drupal\flexible_permissions\RefinableCalculatedPermissions;
use Drupal\group\Access\GroupPermissionCalculatorInterface;
use Drupal\group\Access\GroupPermissionChecker;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the group permission checker service.
 *
 * @coversDefaultClass \Drupal\group\Access\GroupPermissionChecker
 * @group group
 */
class GroupPermissionCheckerTest extends UnitTestCase {

  /**
   * The group permission calculator.
   *
   * @var \Drupal\group\Access\GroupPermissionCalculatorInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $permissionCalculator;

  /**
   * The group membership loader.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $membershipLoader;

  /**
   * The group permission checker.
   *
   * @var \Drupal\group\Access\GroupPermissionCheckerInterface
   */
  protected $permissionChecker;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->permissionCalculator = $this->prophesize(GroupPermissionCalculatorInterface::class);
    $this->membershipLoader = $this->prophesize(GroupMembershipLoaderInterface::class);
    $this->permissionChecker = new GroupPermissionChecker($this->permissionCalculator->reveal(), $this->membershipLoader->reveal());
  }

  /**
   * Tests checking whether a user has a permission in a group.
   *
   * @param bool $is_member
   *   Whether the user is a member.
   * @param array $outsider_permissions
   *   The permissions the user has in the outsider scope.
   * @param bool $outsider_admin
   *   Whether the user is an admin in the outsider scope.
   * @param array $insider_permissions
   *   The permissions the user has in the insider scope.
   * @param bool $insider_admin
   *   Whether the user is an admin in the insider scope.
   * @param array $individual_permissions
   *   The permissions the user has in the individual scope.
   * @param bool $individual_admin
   *   Whether the user is an admin in the individual scope.
   * @param string $permission
   *   The permission to check for.
   * @param bool $has_permission
   *   Whether the user should have the permission.
   * @param string $message
   *   The message to use in the assertion.
   *
   * @covers ::hasPermissionInGroup
   * @dataProvider provideHasPermissionInGroupScenarios
   */
  public function testHasPermissionInGroup($is_member, $outsider_permissions, $outsider_admin, $insider_permissions, $insider_admin, $individual_permissions, $individual_admin, $permission, $has_permission, $message) {
    $account = $this->prophesize(AccountInterface::class)->reveal();
    $group = $this->prophesize(GroupInterface::class);
    $group->id()->willReturn(1);
    $group->bundle()->willReturn('foo');
    $group = $group->reveal();

    $calculated_permissions = new RefinableCalculatedPermissions();
    foreach ($outsider_permissions as $identifier => $permissions) {
      $calculated_permissions->addItem(new CalculatedPermissionsItem(PermissionScopeInterface::OUTSIDER_ID, $identifier, $permissions, $outsider_admin));
    }
    foreach ($insider_permissions as $identifier => $permissions) {
      $calculated_permissions->addItem(new CalculatedPermissionsItem(PermissionScopeInterface::INSIDER_ID, $identifier, $permissions, $insider_admin));
    }
    foreach ($individual_permissions as $identifier => $permissions) {
      $calculated_permissions->addItem(new CalculatedPermissionsItem(PermissionScopeInterface::INDIVIDUAL_ID, $identifier, $permissions, $individual_admin));
    }

    $this->permissionCalculator
      ->calculateFullPermissions($account)
      ->willReturn($calculated_permissions);

    $this->membershipLoader
      ->load($group, $account)
      ->willReturn($is_member);

    $result = $this->permissionChecker->hasPermissionInGroup($permission, $account, $group);
    $this->assertSame($has_permission, $result, $message);
  }

  /**
   * Data provider for testHasPermissionInGroup().
   *
   * All scenarios assume group ID 1 and type 'foo'.
   */
  public function provideHasPermissionInGroupScenarios() {
    $scenarios['outsiderWithAdmin'] = [
      'is_member' => FALSE,
      'outsider_permissions' => ['foo' => []],
      'outsider_admin' => TRUE,
      'insider_permissions' => [],
      'insider_admin' => FALSE,
      'individual_permissions' => [],
      'individual_admin' => FALSE,
      'permission' => 'view group',
      'has_permission' => TRUE,
      'An outsider with the group admin permission can view the group.',
    ];

    $scenarios['insiderWithAdmin'] = [
      'is_member' => TRUE,
      'outsider_permissions' => [],
      'outsider_admin' => FALSE,
      'insider_permissions' => ['foo' => []],
      'insider_admin' => TRUE,
      'individual_permissions' => [],
      'individual_admin' => FALSE,
      'permission' => 'view group',
      'has_permission' => TRUE,
      'An insider with the group admin permission can view the group.',
    ];

    $scenarios['individualOutsiderWithAdmin'] = [
      'is_member' => FALSE,
      'outsider_permissions' => [],
      'outsider_admin' => FALSE,
      'insider_permissions' => [],
      'insider_admin' => FALSE,
      'individual_permissions' => [1 => []],
      'individual_admin' => TRUE,
      'permission' => 'view group',
      'has_permission' => TRUE,
      'An individual outsider with the group admin permission can view the group.',
    ];

    $scenarios['individualInsiderWithAdmin'] = [
      'is_member' => TRUE,
      'outsider_permissions' => [],
      'outsider_admin' => FALSE,
      'insider_permissions' => [],
      'insider_admin' => FALSE,
      'individual_permissions' => [1 => []],
      'individual_admin' => TRUE,
      'permission' => 'view group',
      'has_permission' => TRUE,
      'An individual insider with the group admin permission can view the group.',
    ];

    $scenarios['outsiderWithPermission'] = [
      'is_member' => FALSE,
      'outsider_permissions' => ['foo' => ['view group']],
      'outsider_admin' => FALSE,
      'insider_permissions' => [],
      'insider_admin' => FALSE,
      'individual_permissions' => [],
      'individual_admin' => FALSE,
      'permission' => 'view group',
      'has_permission' => TRUE,
      'An outsider with the right permission can view the group.',
    ];

    $scenarios['insiderWithPermission'] = [
      'is_member' => TRUE,
      'outsider_permissions' => [],
      'outsider_admin' => FALSE,
      'insider_permissions' => ['foo' => ['view group']],
      'insider_admin' => FALSE,
      'individual_permissions' => [],
      'individual_admin' => FALSE,
      'permission' => 'view group',
      'has_permission' => TRUE,
      'An insider with the right permission can view the group.',
    ];

    $scenarios['individualOutsiderWithPermission'] = [
      'is_member' => FALSE,
      'outsider_permissions' => [],
      'outsider_admin' => FALSE,
      'insider_permissions' => [],
      'insider_admin' => FALSE,
      'individual_permissions' => [1 => ['view group']],
      'individual_admin' => FALSE,
      'permission' => 'view group',
      'has_permission' => TRUE,
      'An individual outsider with the right permission can view the group.',
    ];

    $scenarios['individualInsiderWithPermission'] = [
      'is_member' => TRUE,
      'outsider_permissions' => [],
      'outsider_admin' => FALSE,
      'insider_permissions' => [],
      'insider_admin' => FALSE,
      'individual_permissions' => [1 => ['view group']],
      'individual_admin' => FALSE,
      'permission' => 'view group',
      'has_permission' => TRUE,
      'An individual insider with the right permission can view the group.',
    ];

    $scenarios['outsiderWithoutPermission'] = [
      'is_member' => FALSE,
      'outsider_permissions' => ['foo' => []],
      'outsider_admin' => FALSE,
      'insider_permissions' => [],
      'insider_admin' => FALSE,
      'individual_permissions' => [],
      'individual_admin' => FALSE,
      'permission' => 'view group',
      'has_permission' => FALSE,
      'An outsider without the right permission cannot view the group.',
    ];

    $scenarios['insiderWithoutPermission'] = [
      'is_member' => TRUE,
      'outsider_permissions' => [],
      'outsider_admin' => FALSE,
      'insider_permissions' => ['foo' => []],
      'insider_admin' => FALSE,
      'individual_permissions' => [],
      'individual_admin' => FALSE,
      'permission' => 'view group',
      'has_permission' => FALSE,
      'An insider without the right permission cannot view the group.',
    ];

    $scenarios['individualOutsiderWithoutPermission'] = [
      'is_member' => FALSE,
      'outsider_permissions' => [],
      'outsider_admin' => FALSE,
      'insider_permissions' => [],
      'insider_admin' => FALSE,
      'individual_permissions' => [1 => []],
      'individual_admin' => FALSE,
      'permission' => 'view group',
      'has_permission' => FALSE,
      'An individual outsider without the right permission can not view the group.',
    ];

    $scenarios['individualInsiderWithoutPermission'] = [
      'is_member' => TRUE,
      'outsider_permissions' => [],
      'outsider_admin' => FALSE,
      'insider_permissions' => [],
      'insider_admin' => FALSE,
      'individual_permissions' => [1 => []],
      'individual_admin' => FALSE,
      'permission' => 'view group',
      'has_permission' => FALSE,
      'An individual insider without the right permission can not view the group.',
    ];

    return $scenarios;
  }

}
