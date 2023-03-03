<?php

namespace Drupal\Tests\group\Functional;

use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;

/**
 * Tests that entity operations (do not) show up on the group overview.
 *
 * @see \Drupal\group\Entity\Controller\GroupListBuilder::getDefaultOperations()
 *
 * @group group
 */
class EntityOperationsTest extends GroupBrowserTestBase {

  /**
   * Checks for entity operations under given circumstances.
   *
   * @parem array $visible
   *   A list of visible link labels, keyed by path.
   * @parem array $invisible
   *   A list of invisible link labels, keyed by path.
   * @param string[] $permissions
   *   A list of group permissions to assign to the user.
   * @param string[] $modules
   *   A list of modules to enable.
   *
   * @dataProvider provideEntityOperationScenarios
   */
  public function testEntityOperations($visible, $invisible, $permissions = [], $modules = []) {
    $group = $this->createGroup(['type' => $this->createGroupType()->id()]);

    if (!empty($permissions)) {
      $this->createGroupRole([
        'group_type' => $group->bundle(),
        'scope' => PermissionScopeInterface::INSIDER_ID,
        'global_role' => RoleInterface::AUTHENTICATED_ID,
        'permissions' => $permissions,
      ]);
    }

    if (!empty($modules)) {
      $this->container->get('module_installer')->install($modules, TRUE);
    }

    $this->drupalGet('admin/group');

    foreach ($visible as $path => $label) {
      $this->assertSession()->linkExists($label);
      $this->assertSession()->linkByHrefExists($path);
    }

    foreach ($invisible as $path => $label) {
      $this->assertSession()->linkNotExists($label);
      $this->assertSession()->linkByHrefNotExists($path);
    }
  }

  /**
   * Data provider for testEntityOperations().
   */
  public function provideEntityOperationScenarios() {
    $scenarios['withoutAccess'] = [
      [],
      [
        'group/1/edit' => 'Edit',
        'group/1/members' => 'Members',
        'group/1/delete' => 'Delete',
        'group/1/revisions' => 'Revisions',
      ],
    ];

    $scenarios['withAccess'] = [
      [
        'group/1/edit' => 'Edit',
        'group/1/delete' => 'Delete',
        'group/1/revisions' => 'Revisions',
      ],
      [
        'group/1/members' => 'Members',
      ],
      [
        'view group',
        'edit group',
        'delete group',
        'administer members',
        'view group revisions',
      ],
    ];

    $scenarios['withAccessAndViews'] = [
      [
        'group/1/edit' => 'Edit',
        'group/1/members' => 'Members',
        'group/1/delete' => 'Delete',
        'group/1/revisions' => 'Revisions',
      ],
      [],
      [
        'view group',
        'edit group',
        'delete group',
        'administer members',
        'view group revisions',
      ],
      ['views'],
    ];

    return $scenarios;
  }

}
