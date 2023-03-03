<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\flexible_permissions\CalculatedPermissionsInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;

/**
 * Tests the calculation of individual group permissions.
 *
 * @coversDefaultClass \Drupal\group\Access\IndividualGroupPermissionCalculator
 * @group group
 */
class IndividualGroupPermissionCalculatorTest extends GroupKernelTestBase {

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
   * @covers ::calculatePermissions
   */
  public function testCalculatePermissions() {
    $scope = PermissionScopeInterface::INDIVIDUAL_ID;

    $group_type = $this->createGroupType();
    $group_role = $this->createGroupRole([
      'group_type' => $group_type->id(),
      'scope' => $scope,
      'permissions' => [
        'view group',
        'leave group',
      ],
    ]);

    // Also create a member role to see if it's ignored.
    $this->createGroupRole([
      'group_type' => $group_type->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['delete group'],
    ]);

    $account = $this->createUser();
    $group = $this->createGroup(['type' => $group_type->id()]);

    $permissions = [];
    $cache_tags = [
      'flexible_permissions',
      'group_relationship_list:plugin:group_membership:entity:' . $account->id(),
    ];
    sort($cache_tags);

    $calculated_permissions = $this->permissionCalculator->calculatePermissions($account, $scope);
    $converted = $this->convertCalculatedPermissionsToArray($calculated_permissions);
    $this->assertEqualsCanonicalizing($permissions, $converted, 'Permissions are returned per group ID.');
    $this->assertSame(-1, $calculated_permissions->getCacheMaxAge(), 'Permissions have the right max cache age.');
    $this->assertEqualsCanonicalizing($cache_tags, $calculated_permissions->getCacheTags(), 'Permissions have the right cache tags.');

    // @todo This displays a desperate need for addRole() and removeRole().
    $group->addMember($account);
    $member = $group->getMember($account);
    $group_relationship = $member->getGroupRelationship();
    $group_relationship->group_roles[] = $group_role->id();
    $group_relationship->save();

    $permissions[$scope][$group->id()][] = 'view group';
    $permissions[$scope][$group->id()][] = 'leave group';

    $cache_tags[] = 'config:group.role.' . $group_role->id();
    $cache_tags = array_unique(array_merge($member->getCacheTags(), $cache_tags));
    sort($cache_tags);

    $calculated_permissions = $this->permissionCalculator->calculatePermissions($account, $scope);
    $converted = $this->convertCalculatedPermissionsToArray($calculated_permissions);
    $this->assertEqualsCanonicalizing($permissions, $converted, 'Permissions are returned per group ID after joining a group.');
    $this->assertSame(-1, $calculated_permissions->getCacheMaxAge(), 'Permissions have the right max cache age after joining a group.');
    $this->assertEqualsCanonicalizing($cache_tags, $calculated_permissions->getCacheTags(), 'Permissions have the right cache tags after joining a group.');

    $group_role->grantPermission('edit group');
    $group_role->save();
    $permissions[$scope][$group->id()][] = 'edit group';

    $calculated_permissions = $this->permissionCalculator->calculatePermissions($account, $scope);
    $converted = $this->convertCalculatedPermissionsToArray($calculated_permissions);
    $this->assertEqualsCanonicalizing($permissions, $converted, 'Updated permissions are returned per group ID.');
    $this->assertSame(-1, $calculated_permissions->getCacheMaxAge(), 'Updated permissions have the right max cache age.');
    $this->assertEqualsCanonicalizing($cache_tags, $calculated_permissions->getCacheTags(), 'Updated permissions have the right cache tags.');
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
