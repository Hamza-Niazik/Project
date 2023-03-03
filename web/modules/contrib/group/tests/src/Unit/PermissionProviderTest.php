<?php

namespace Drupal\Tests\group\Unit;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationType;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\group\Plugin\Group\RelationHandlerDefault\PermissionProvider;
use Drupal\group_test_plugin\Plugin\Group\RelationHandler\FullEntityPermissionProvider;
use Drupal\Tests\UnitTestCase;
use Drupal\user\EntityOwnerInterface;

/**
 * Tests the default group relation permission_provider handler.
 *
 * @coversDefaultClass \Drupal\group\Plugin\Group\RelationHandlerDefault\PermissionProvider
 * @group group
 */
class PermissionProviderTest extends UnitTestCase {

  /**
   * Tests the admin permission name.
   *
   * @param mixed $expected
   *   The expected return value.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $implements_owner
   *   Whether the plugin's entity type deals with ownership.
   * @param bool $implements_published
   *   Whether the plugin's entity type deals with publishing of entities.
   *
   * @covers ::getAdminPermission
   * @dataProvider adminPermissionProvider
   */
  public function testGetAdminPermission($expected, $plugin_id, GroupRelationTypeInterface $definition, $implements_owner, $implements_published) {
    $permission_provider = $this->createPermissionProvider($plugin_id, $definition, $implements_owner, $implements_published);
    $this->assertEquals($expected, $permission_provider->getAdminPermission());
  }

  /**
   * Data provider for testGetAdminPermission().
   *
   * @return array
   *   A list of testGetAdminPermission method arguments.
   */
  public function adminPermissionProvider() {
    $cases = [];
    foreach ($this->getPermissionProviderScenarios() as $scenario) {
      $case = $scenario;
      $case['expected'] = $case['definition']->getAdminPermission();
      $cases[] = $case;
    }
    return $cases;
  }

  /**
   * Tests the relation view permission name.
   *
   * @param mixed $expected
   *   The expected return value.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $implements_owner
   *   Whether the plugin's entity type deals with ownership.
   * @param bool $implements_published
   *   Whether the plugin's entity type deals with publishing of entities.
   * @param string $scope
   *   The $scope parameter for the tested method.
   *
   * @covers ::getPermission
   * @dataProvider relationViewPermissionProvider
   */
  public function testGetRelationViewPermission($expected, $plugin_id, GroupRelationTypeInterface $definition, $implements_owner, $implements_published, $scope) {
    $permission_provider = $this->createPermissionProvider($plugin_id, $definition, $implements_owner, $implements_published);
    $this->assertEquals($expected, $permission_provider->getPermission('view', 'relationship', $scope));
  }

  /**
   * Data provider for testGetRelationViewPermission().
   *
   * @return array
   *   A list of testGetRelationViewPermission method arguments.
   */
  public function relationViewPermissionProvider() {
    $cases = [];
    foreach ($this->getPermissionProviderScenarios() as $scenario) {
      foreach (['any', 'own'] as $scope) {
        $case = $scenario;
        $case['scope'] = $scope;

        // View own relation is not present in version 1.x.
        $case['expected'] = $scope === 'any'
          ? "view {$scenario['plugin_id']} relationship"
          : FALSE;

        $cases[] = $case;
      }
    }
    return $cases;
  }

  /**
   * Tests the relation update permission name.
   *
   * @param mixed $expected
   *   The expected return value.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $implements_owner
   *   Whether the plugin's entity type deals with ownership.
   * @param bool $implements_published
   *   Whether the plugin's entity type deals with publishing of entities.
   * @param string $scope
   *   The $scope parameter for the tested method.
   *
   * @covers ::getPermission
   * @dataProvider relationUpdatePermissionProvider
   */
  public function testGetRelationUpdatePermission($expected, $plugin_id, GroupRelationTypeInterface $definition, $implements_owner, $implements_published, $scope) {
    $permission_provider = $this->createPermissionProvider($plugin_id, $definition, $implements_owner, $implements_published);
    $this->assertEquals($expected, $permission_provider->getPermission('update', 'relationship', $scope));
  }

  /**
   * Data provider for testGetRelationUpdatePermission().
   *
   * @return array
   *   A list of testGetRelationUpdatePermission method arguments.
   */
  public function relationUpdatePermissionProvider() {
    $cases = [];
    foreach ($this->getPermissionProviderScenarios() as $scenario) {
      foreach (['any', 'own'] as $scope) {
        $case = $scenario;
        $case['scope'] = $scope;
        $case['expected'] = "update $scope {$scenario['plugin_id']} relationship";
        $cases[] = $case;
      }
    }
    return $cases;
  }

  /**
   * Tests the relation delete permission name.
   *
   * @param mixed $expected
   *   The expected return value.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $implements_owner
   *   Whether the plugin's entity type deals with ownership.
   * @param bool $implements_published
   *   Whether the plugin's entity type deals with publishing of entities.
   * @param string $scope
   *   The $scope parameter for the tested method.
   *
   * @covers ::getPermission
   * @dataProvider relationDeletePermissionProvider
   */
  public function testGetRelationDeletePermission($expected, $plugin_id, GroupRelationTypeInterface $definition, $implements_owner, $implements_published, $scope) {
    $permission_provider = $this->createPermissionProvider($plugin_id, $definition, $implements_owner, $implements_published);
    $this->assertEquals($expected, $permission_provider->getPermission('delete', 'relationship', $scope));
  }

  /**
   * Data provider for testGetRelationDeletePermission().
   *
   * @return array
   *   A list of testGetRelationDeletePermission method arguments.
   */
  public function relationDeletePermissionProvider() {
    $cases = [];
    foreach ($this->getPermissionProviderScenarios() as $scenario) {
      foreach (['any', 'own'] as $scope) {
        $case = $scenario;
        $case['scope'] = $scope;
        $case['expected'] = "delete $scope {$scenario['plugin_id']} relationship";
        $cases[] = $case;
      }
    }
    return $cases;
  }

  /**
   * Tests the relation create permission name.
   *
   * @param mixed $expected
   *   The expected return value.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $implements_owner
   *   Whether the plugin's entity type deals with ownership.
   * @param bool $implements_published
   *   Whether the plugin's entity type deals with publishing of entities.
   *
   * @covers ::getPermission
   * @dataProvider relationCreatePermissionProvider
   */
  public function testGetRelationCreatePermission($expected, $plugin_id, GroupRelationTypeInterface $definition, $implements_owner, $implements_published) {
    $permission_provider = $this->createPermissionProvider($plugin_id, $definition, $implements_owner, $implements_published);
    $this->assertEquals($expected, $permission_provider->getPermission('create', 'relationship'));
  }

  /**
   * Data provider for testGetRelationCreatePermission().
   *
   * @return array
   *   A list of testGetRelationCreatePermission method arguments.
   */
  public function relationCreatePermissionProvider() {
    $cases = [];
    foreach ($this->getPermissionProviderScenarios() as $scenario) {
      $case = $scenario;
      $case['expected'] = "create {$scenario['plugin_id']} relationship";
      $cases[] = $case;
    }
    return $cases;
  }

  /**
   * Tests the entity view permission name.
   *
   * @param mixed $expected
   *   The expected return value.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $implements_owner
   *   Whether the plugin's entity type deals with ownership.
   * @param bool $implements_published
   *   Whether the plugin's entity type deals with publishing of entities.
   * @param string $scope
   *   The $scope parameter for the tested method.
   *
   * @covers ::getPermission
   * @dataProvider entityViewPermissionProvider
   */
  public function testGetEntityViewPermission($expected, $plugin_id, GroupRelationTypeInterface $definition, $implements_owner, $implements_published, $scope) {
    $permission_provider = $this->createPermissionProvider($plugin_id, $definition, $implements_owner, $implements_published);
    $this->assertEquals($expected, $permission_provider->getPermission('view', 'entity', $scope));
  }

  /**
   * Data provider for testGetEntityViewPermission().
   *
   * @return array
   *   A list of testGetEntityViewPermission method arguments.
   */
  public function entityViewPermissionProvider() {
    $cases = [];
    foreach ($this->getPermissionProviderScenarios() as $scenario) {
      foreach (['any', 'own'] as $scope) {
        $case = $scenario;
        $case['scope'] = $scope;
        $case['expected'] = FALSE;
        if ($case['definition']->definesEntityAccess()) {
          // View own entity is not present in version 1.x.
          if ($scope === 'any') {
            $case['expected'] = "view {$scenario['plugin_id']} entity";
          }
        }
        $cases[] = $case;
      }
    }
    return $cases;
  }

  /**
   * Tests the entity view unpublished permission name.
   *
   * @param mixed $expected
   *   The expected return value.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $implements_owner
   *   Whether the plugin's entity type deals with ownership.
   * @param bool $implements_published
   *   Whether the plugin's entity type deals with publishing of entities.
   * @param string $scope
   *   The $scope parameter for the tested method.
   *
   * @covers ::getPermission
   * @dataProvider entityViewUnpublishedPermissionProvider
   */
  public function testGetEntityViewUnpublishedPermission($expected, $plugin_id, GroupRelationTypeInterface $definition, $implements_owner, $implements_published, $scope) {
    $permission_provider = $this->createPermissionProvider($plugin_id, $definition, $implements_owner, $implements_published);
    $this->assertEquals($expected, $permission_provider->getPermission('view unpublished', 'entity', $scope));
  }

  /**
   * Data provider for testGetEntityViewUnpublishedPermission().
   *
   * @return array
   *   A list of testGetEntityViewUnpublishedPermission method arguments.
   */
  public function entityViewUnpublishedPermissionProvider() {
    $cases = [];
    foreach ($this->getPermissionProviderScenarios() as $scenario) {
      foreach (['any', 'own'] as $scope) {
        $case = $scenario;
        $case['scope'] = $scope;
        $case['expected'] = FALSE;
        if ($case['definition']->definesEntityAccess() && $case['implements_published']) {
          // View own unpublished entity is not implemented yet.
          if ($scope === 'any') {
            $case['expected'] = "view $scope unpublished {$scenario['plugin_id']} entity";
          }
        }
        $cases[] = $case;
      }
    }
    return $cases;
  }

  /**
   * Tests the entity update permission name.
   *
   * @param mixed $expected
   *   The expected return value.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $implements_owner
   *   Whether the plugin's entity type deals with ownership.
   * @param bool $implements_published
   *   Whether the plugin's entity type deals with publishing of entities.
   * @param string $scope
   *   The $scope parameter for the tested method.
   *
   * @covers ::getPermission
   * @dataProvider entityUpdatePermissionProvider
   */
  public function testGetEntityUpdatePermission($expected, $plugin_id, GroupRelationTypeInterface $definition, $implements_owner, $implements_published, $scope) {
    $permission_provider = $this->createPermissionProvider($plugin_id, $definition, $implements_owner, $implements_published);
    $this->assertEquals($expected, $permission_provider->getPermission('update', 'entity', $scope));
  }

  /**
   * Data provider for testGetEntityUpdatePermission().
   *
   * @return array
   *   A list of testGetEntityUpdatePermission method arguments.
   */
  public function entityUpdatePermissionProvider() {
    $cases = [];
    foreach ($this->getPermissionProviderScenarios() as $scenario) {
      foreach (['any', 'own'] as $scope) {
        $case = $scenario;
        $case['scope'] = $scope;
        $case['expected'] = FALSE;
        if ($case['definition']->definesEntityAccess()) {
          if ($case['implements_owner'] || $scope === 'any') {
            $case['expected'] = "update $scope {$scenario['plugin_id']} entity";
          }
        }
        $cases[] = $case;
      }
    }
    return $cases;
  }

  /**
   * Tests the entity delete permission name.
   *
   * @param mixed $expected
   *   The expected return value.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $implements_owner
   *   Whether the plugin's entity type deals with ownership.
   * @param bool $implements_published
   *   Whether the plugin's entity type deals with publishing of entities.
   * @param string $scope
   *   The $scope parameter for the tested method.
   *
   * @covers ::getPermission
   * @dataProvider entityDeletePermissionProvider
   */
  public function testGetEntityDeletePermission($expected, $plugin_id, GroupRelationTypeInterface $definition, $implements_owner, $implements_published, $scope) {
    $permission_provider = $this->createPermissionProvider($plugin_id, $definition, $implements_owner, $implements_published);
    $this->assertEquals($expected, $permission_provider->getPermission('delete', 'entity', $scope));
  }

  /**
   * Data provider for testGetEntityDeletePermission().
   *
   * @return array
   *   A list of testGetEntityDeletePermission method arguments.
   */
  public function entityDeletePermissionProvider() {
    $cases = [];
    foreach ($this->getPermissionProviderScenarios() as $scenario) {
      foreach (['any', 'own'] as $scope) {
        $case = $scenario;
        $case['scope'] = $scope;
        $case['expected'] = FALSE;
        if ($case['definition']->definesEntityAccess()) {
          if ($case['implements_owner'] || $scope === 'any') {
            $case['expected'] = "delete $scope {$scenario['plugin_id']} entity";
          }
        }
        $cases[] = $case;
      }
    }
    return $cases;
  }

  /**
   * Tests the entity create permission name.
   *
   * @param mixed $expected
   *   The expected return value.
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $implements_owner
   *   Whether the plugin's entity type deals with ownership.
   * @param bool $implements_published
   *   Whether the plugin's entity type deals with publishing of entities.
   *
   * @covers ::getPermission
   * @dataProvider entityCreatePermissionProvider
   */
  public function testGetEntityCreatePermission($expected, $plugin_id, GroupRelationTypeInterface $definition, $implements_owner, $implements_published) {
    $permission_provider = $this->createPermissionProvider($plugin_id, $definition, $implements_owner, $implements_published);
    $this->assertEquals($expected, $permission_provider->getPermission('create', 'entity'));
  }

  /**
   * Data provider for testGetEntityCreatePermission().
   *
   * @return array
   *   A list of testGetEntityCreatePermission method arguments.
   */
  public function entityCreatePermissionProvider() {
    $cases = [];
    foreach ($this->getPermissionProviderScenarios() as $scenario) {
      $case = $scenario;
      $case['expected'] = FALSE;
      if ($case['definition']->definesEntityAccess()) {
        $case['expected'] = "create {$scenario['plugin_id']} entity";
      }
      $cases[] = $case;
    }
    return $cases;
  }

  /**
   * Tests the permission builder.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
   *   The plugin definition.
   * @param bool $implements_owner
   *   Whether the plugin's entity type deals with ownership.
   * @param bool $implements_published
   *   Whether the plugin's entity type deals with publishing of entities.
   *
   * @covers ::buildPermissions
   * @dataProvider buildPermissionsProvider
   */
  public function testBuildPermissions($plugin_id, GroupRelationTypeInterface $definition, $implements_owner, $implements_published) {
    $permission_provider = $this->createPermissionProvider($plugin_id, $definition, $implements_owner, $implements_published, TRUE);
    $permissions = $permission_provider->buildPermissions();

    // Test the admin permission being restricted.
    if (!empty($definition->getAdminPermission())) {
      $admin_permission = $permission_provider->getAdminPermission();
      $this->assertArrayHasKey($admin_permission, $permissions);
      $this->assertArrayHasKey('restrict access', $permissions[$admin_permission]);
      $this->assertTrue($permissions[$admin_permission]['restrict access']);
    }

    // We do not test all permissions here as they are thoroughly covered in
    // their dedicated getter test. Simply test that the labels of common
    // permissions are prefixed properly.
    if ($permission = $permission_provider->getPermission('update', 'relationship')) {
      $this->assertArrayHasKey($permission, $permissions);
      $this->assertStringStartsWith('Relationship: ', $permissions[$permission]['title']);
    }
    if ($permission = $permission_provider->getPermission('update', 'entity')) {
      $this->assertArrayHasKey($permission, $permissions);
      $this->assertStringStartsWith('Entity: ', $permissions[$permission]['title']);
    }

    // Test that we call the full chain for permission names.
    if ($implements_owner && $definition->definesEntityAccess()) {
      $this->assertFalse($permission_provider->getPermission('view', 'entity', 'own'), 'The handler does not support view own entity');
      $this->assertArrayHasKey("view own $plugin_id entity", $permissions, 'The full chain does support view own entity and therefore the permission is built');
    }
  }

  /**
   * Data provider for testBuildPermissions().
   *
   * @return array
   *   A list of testBuildPermissions method arguments.
   */
  public function buildPermissionsProvider() {
    $cases = $this->getPermissionProviderScenarios();
    foreach ($cases as &$case) {
      unset($case['expected']);
    }
    return $cases;
  }

  /**
   * All possible scenarios for a permission provider.
   *
   * @return array
   *   A set of test cases to be used in data providers.
   */
  protected function getPermissionProviderScenarios() {
    $scenarios = [];

    foreach ([TRUE, FALSE] as $implements_owner) {
      $keys[0] = $implements_owner ? 'owner' : 'no_owner';

      foreach ([TRUE, FALSE] as $implements_published) {
        $keys[1] = $implements_published ? 'pub' : 'no_pub';

        foreach ([TRUE, FALSE] as $entity_access) {
          $keys[2] = $entity_access ? 'access' : 'no_access';

          foreach (['administer foo', FALSE] as $admin_permission) {
            $keys[3] = $admin_permission ? 'admin' : 'no_admin';

            $scenarios[implode('-', $keys)] = [
              'expected' => NULL,
              // We use a derivative ID to prove these work.
              'plugin_id' => 'foo:baz',
              'definition' => new GroupRelationType([
                'id' => 'foo',
                'label' => 'Foo',
                'entity_type_id' => 'bar',
                'entity_access' => $entity_access,
                'admin_permission' => $admin_permission,
              ]),
              'implements_owner' => $implements_owner,
              'implements_published' => $implements_published,
            ];
          }
        }
      }
    }

    return $scenarios;
  }

  /**
   * Instantiates a default permission provider handler.
   *
   * @return \Drupal\group\Plugin\Group\RelationHandlerDefault\PermissionProvider
   *   The default permission provider handler.
   */
  protected function createPermissionProvider($plugin_id, $definition, $implements_owner, $implements_published, $set_up_chain = FALSE) {
    $this->assertNotEmpty($definition->getEntityTypeId());

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->entityClassImplements(EntityOwnerInterface::class)->willReturn($implements_owner);
    $entity_type->entityClassImplements(EntityPublishedInterface::class)->willReturn($implements_published);
    $entity_type->getSingularLabel()->willReturn('Bar');

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getDefinition($definition->getEntityTypeId())->willReturn($entity_type->reveal());

    $relation_type_manager = $this->prophesize(GroupRelationTypeManagerInterface::class);
    $permission_provider = new PermissionProvider($entity_type_manager->reveal(), $relation_type_manager->reveal());
    $permission_provider->init($plugin_id, $definition);

    $chained = $permission_provider;
    if ($set_up_chain) {
      $chained = new FullEntityPermissionProvider($permission_provider, $entity_type_manager->reveal());
      $chained->init($plugin_id, $definition);
    }
    $relation_type_manager->getPermissionProvider($plugin_id)->willReturn($chained);

    return $permission_provider;
  }

}
