<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\flexible_permissions\CalculatedPermissionsInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;

/**
 * Tests the calculation of synchronized group permissions.
 *
 * @coversDefaultClass \Drupal\group\Access\SynchronizedGroupPermissionCalculator
 * @group group
 */
class SynchronizedGroupPermissionCalculatorTest extends GroupKernelTestBase {

  /**
   * The chain permission calculator.
   *
   * @var \Drupal\flexible_permissions\ChainPermissionCalculatorInterface
   */
  protected $permissionCalculator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->permissionCalculator = $this->container->get('flexible_permissions.chain_calculator');
  }

  /**
   * Tests the calculation of the synchronized permissions.
   *
   * @param string $scope
   *   The scope for the synchronized permissions. Either outsider or insider.
   *
   * @covers ::calculatePermissions
   * @dataProvider calculatePermissionsProvider
   */
  public function testCalculatePermissions($scope) {
    $this->createRole([], RoleInterface::AUTHENTICATED_ID);
    $group_type_a = $this->createGroupType();
    $group_type_b = $this->createGroupType();
    $role_config = [
      'scope' => $scope,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => [],
    ];
    $group_role_a = $this->createGroupRole(['group_type' => $group_type_a->id()] + $role_config);
    $group_role_b = $this->createGroupRole(['group_type' => $group_type_b->id()] + $role_config);

    $permissions[$scope] = [
      $group_type_a->id() => [],
      $group_type_b->id() => [],
    ];

    $cache_tags = [
      'config:group.role.' . $group_role_a->id(),
      'config:group.role.' . $group_role_b->id(),
      'config:group_role_list',
      'flexible_permissions',
    ];
    sort($cache_tags);

    $calculated_permissions = $this->permissionCalculator->calculatePermissions($this->getCurrentUser(), $scope);
    $converted = $this->convertCalculatedPermissionsToArray($calculated_permissions);
    $this->assertEqualsCanonicalizing($permissions, $converted, 'Permissions are returned per group type.');
    $this->assertSame([], $calculated_permissions->getCacheContexts(), 'Permissions have the right cache contexts.');
    $this->assertSame(-1, $calculated_permissions->getCacheMaxAge(), 'Permissions have the right max cache age.');
    $this->assertEqualsCanonicalizing($cache_tags, $calculated_permissions->getCacheTags(), 'Permissions have the right cache tags.');

    $group_role_a->grantPermission('view group')->save();
    $permissions[$scope][$group_type_a->id()][] = 'view group';

    $calculated_permissions = $this->permissionCalculator->calculatePermissions($this->getCurrentUser(), $scope);
    $converted = $this->convertCalculatedPermissionsToArray($calculated_permissions);
    $this->assertEqualsCanonicalizing($permissions, $converted, 'Updated permissions are returned per group type.');
    $this->assertSame([], $calculated_permissions->getCacheContexts(), 'Updated permissions have the right cache contexts.');
    $this->assertSame(-1, $calculated_permissions->getCacheMaxAge(), 'Updated permissions have the right max cache age.');
    $this->assertEqualsCanonicalizing($cache_tags, $calculated_permissions->getCacheTags(), 'Updated permissions have the right cache tags.');

    $group_type_c = $this->createGroupType();
    $group_role_c = $this->createGroupRole(['group_type' => $group_type_c->id()] + $role_config);
    $permissions[$scope][$group_type_c->id()] = [];
    $cache_tags[] = 'config:group.role.' . $group_role_c->id();
    sort($cache_tags);

    $calculated_permissions = $this->permissionCalculator->calculatePermissions($this->getCurrentUser(), $scope);
    $converted = $this->convertCalculatedPermissionsToArray($calculated_permissions);
    $this->assertEqualsCanonicalizing($permissions, $converted, 'Permissions are updated after introducing a new group type.');
    $this->assertSame([], $calculated_permissions->getCacheContexts(), 'Permissions have the right cache contexts after introducing a new group type.');
    $this->assertSame(-1, $calculated_permissions->getCacheMaxAge(), 'Permissions have the right max cache age after introducing a new group type.');
    $this->assertEqualsCanonicalizing($cache_tags, $calculated_permissions->getCacheTags(), 'Permissions have the right cache tags after introducing a new group type.');
  }

  /**
   * Data provider for testCalculatePermissions().
   *
   * @return array
   *   A list of testCalculatePermissions method arguments.
   */
  public function calculatePermissionsProvider() {
    return [
      'outsider-scope' => [PermissionScopeInterface::OUTSIDER_ID],
      'insider-scope' => [PermissionScopeInterface::INSIDER_ID],
    ];
  }

  /**
   * Converts a calculated permissions object into an array.
   *
   * This is done to make comparison assertions easier. Make sure you use the
   * canonicalize option of assertEquals.
   *
   * @param \Drupal\flexible_permissions\CalculatedPermissionsInterface $calculated_permissions
   *   The calculated permissions object to convert.
   *
   * @return string[]
   *   The permissions, keyed by scope identifier.
   */
  protected function convertCalculatedPermissionsToArray(CalculatedPermissionsInterface $calculated_permissions) {
    $permissions = [];
    foreach ($calculated_permissions->getItems() as $item) {
      $permissions[$item->getScope()][$item->getIdentifier()] = $item->getPermissions();
    }
    return $permissions;
  }

}
