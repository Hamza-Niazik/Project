<?php

namespace Drupal\Tests\group\Unit {

  use Drupal\Core\Entity\EntityTypeInterface;
  use Drupal\Core\Entity\EntityTypeManagerInterface;
  use Drupal\Core\Extension\ModuleHandlerInterface;
  use Drupal\Core\Session\AccountProxyInterface;
  use Drupal\Core\StringTranslation\TranslationInterface;
  use Drupal\group\Entity\GroupRelationshipTypeInterface;
  use Drupal\group\Entity\GroupInterface;
  use Drupal\group\Entity\GroupTypeInterface;
  use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
  use Drupal\group\Plugin\Group\Relation\GroupRelationType;
  use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
  use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
  use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;
  use Drupal\group\Plugin\Group\RelationHandlerDefault\OperationProvider;
  use Drupal\Tests\UnitTestCase;
  use Prophecy\Argument;

  /**
   * Tests the default group relation operation_provider handler.
   *
   * @coversDefaultClass \Drupal\group\Plugin\Group\RelationHandlerDefault\OperationProvider
   * @group group
   */
  class OperationProviderTest extends UnitTestCase {

    /**
     * Tests the retrieval of operations.
     *
     * @param mixed $expected
     *   The expected operation keys.
     * @param string $plugin_id
     *   The plugin ID.
     * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
     *   The plugin definition.
     * @param bool $installed
     *   Whether the plugin is installed.
     * @param bool $field_ui
     *   Whether Field UI is enabled.
     *
     * @covers ::getOperations
     * @dataProvider getOperationsProvider
     */
    public function testGetOperations($expected, $plugin_id, GroupRelationTypeInterface $definition, $installed, $field_ui) {
      $group_type = $this->prophesize(GroupTypeInterface::class);
      $group_type->id()->willReturn('some_type');
      $group_type->hasPlugin($plugin_id)->willReturn($installed);

      $module_handler = $this->prophesize(ModuleHandlerInterface::class);
      $module_handler->moduleExists('field_ui')->willReturn($field_ui);

      $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
      $entity_type = $this->prophesize(EntityTypeInterface::class);
      $entity_type_manager->getDefinition($definition->getEntityTypeId())->willReturn($entity_type->reveal());

      $entity = $this->prophesize(GroupRelationshipTypeInterface::class);
      $storage = $this->prophesize(GroupRelationshipTypeStorageInterface::class);
      $storage->getRelationshipTypeId(Argument::cetera())->willReturn('foobar');
      $storage->load('foobar')->willReturn($entity->reveal());
      $entity_type_manager->getStorage('group_relationship_type')->willReturn($storage->reveal());

      $operation_provider = $this->createOperationProvider($plugin_id, $definition, $module_handler->reveal(), NULL, $entity_type_manager->reveal());
      $this->assertEquals($expected, array_keys($operation_provider->getOperations($group_type->reveal())));
    }

    /**
     * Data provider for testGetOperations().
     *
     * @return array
     *   A list of testGetOperations method arguments.
     */
    public function getOperationsProvider() {
      $cases = [];

      foreach ($this->getOperationProviderScenarios() as $key => $scenario) {
        $keys[0] = $key;

        foreach ([TRUE, FALSE] as $installed) {
          $keys[1] = $installed ? 'installed' : 'not_installed';

          foreach ([TRUE, FALSE] as $field_ui) {
            $keys[2] = $field_ui ? 'field_ui' : 'no_field_ui';

            $operation_keys = [];

            $case = $scenario;
            $ui_allowed = !$case['definition']->isEnforced() && !$case['definition']->isCodeOnly();

            if ($installed) {
              $operation_keys[] = 'configure';
              if ($ui_allowed) {
                $operation_keys[] = 'uninstall';
              }
              if ($field_ui) {
                $operation_keys[] = 'bar';
              }
            }
            elseif ($ui_allowed) {
              $operation_keys[] = 'install';
            }

            $case['expected'] = $operation_keys;
            $case['installed'] = $installed;
            $case['field_ui'] = $field_ui;
            $cases[implode('-', $keys)] = $case;
          }
        }
      }

      return $cases;
    }

    /**
     * Tests the retrieval of group operations.
     *
     * @param mixed $expected
     *   The expected operation keys.
     * @param string $plugin_id
     *   The plugin ID.
     * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $definition
     *   The plugin definition.
     * @param bool $has_create_permission
     *   Whether the user can create new grouped entities.
     *
     * @covers ::getGroupOperations
     * @dataProvider getGroupOperationsProvider
     */
    public function testGetGroupOperations($expected, $plugin_id, GroupRelationTypeInterface $definition, $has_create_permission) {
      $create_permission = $this->randomMachineName();
      $permission_provider = $this->prophesize(PermissionProviderInterface::class);
      $permission_provider->getPermission('create', 'entity')->willReturn($create_permission);
      $relation_type_manager = $this->prophesize(GroupRelationTypeManagerInterface::class);
      $relation_type_manager->getPermissionProvider($plugin_id)->willReturn($permission_provider->reveal());
      $current_user = $this->prophesize(AccountProxyInterface::class)->reveal();
      $operation_provider = $this->createOperationProvider($plugin_id, $definition, NULL, $current_user, NULL, $relation_type_manager->reveal());

      $group = $this->prophesize(GroupInterface::class);
      $group->hasPermission($create_permission, $current_user)->willReturn($has_create_permission);
      if ($has_create_permission) {
        $group->id()->willReturn('does-not-matter-only-used-in-route');
      }
      $this->assertEquals($expected, array_keys($operation_provider->getGroupOperations($group->reveal())));
    }

    /**
     * Data provider for testGetGroupOperations().
     *
     * @return array
     *   A list of testGetGroupOperations method arguments.
     */
    public function getGroupOperationsProvider() {
      $cases = [];

      foreach ($this->getOperationProviderScenarios() as $key => $scenario) {
        $keys[0] = $key;

        foreach ([TRUE, FALSE] as $has_create_permission) {
          $keys[1] = $has_create_permission ? 'has_create_perm' : 'not_has_create_perm';

          foreach ([TRUE, FALSE] as $has_bundles) {
            $keys[2] = $has_bundles ? 'has_bundles' : 'not_has_bundles';
            $case = $scenario;
            $case['definition'] = clone $scenario['definition'];

            $operation_keys = [];
            if ($has_create_permission) {
              $operation_key = $case['definition']->id() . '-create';
              if ($has_bundles) {
                $case['definition']->set('entity_bundle', 'baz');
                $operation_key .= '-' . $case['definition']->getEntityBundle();
              }
              $operation_keys[] = $operation_key;
            }

            $case['expected'] = $operation_keys;
            $case['has_create_permission'] = $has_create_permission;
            $cases[implode('-', $keys)] = $case;
          }
        }
      }

      return $cases;
    }

    /**
     * All possible scenarios for an operation provider.
     *
     * @return array
     *   A set of test cases to be used in data providers.
     */
    protected function getOperationProviderScenarios() {
      $scenarios = [];

      foreach ([TRUE, FALSE] as $is_enforced) {
        $keys[0] = $is_enforced ? 'enforced' : 'not_enforced';

        foreach ([TRUE, FALSE] as $is_code_only) {
          $keys[1] = $is_code_only ? 'code_only' : 'not_code_only';

          $scenarios[implode('-', $keys)] = [
            'expected' => NULL,
            // We use a derivative ID to prove these work.
            'plugin_id' => 'foo:baz',
            'definition' => new GroupRelationType([
              'id' => 'foo',
              'label' => 'Foo',
              'entity_type_id' => 'bar',
              'enforced' => $is_enforced,
              'code_only' => $is_code_only,
            ]),
          ];
        }
      }

      return $scenarios;
    }

    /**
     * Instantiates a default operation provider handler.
     *
     * @return \Drupal\group\Plugin\Group\RelationHandlerDefault\OperationProvider
     *   The default permission provider handler.
     */
    protected function createOperationProvider(
      $plugin_id,
      $definition,
      ModuleHandlerInterface $module_handler = NULL,
      AccountProxyInterface $current_user = NULL,
      EntityTypeManagerInterface $entity_type_manager = NULL,
      GroupRelationTypeManagerInterface $relation_type_manager = NULL
    ) {
      if (!isset($module_handler)) {
        $module_handler = $this->prophesize(ModuleHandlerInterface::class)->reveal();
      }
      if (!isset($current_user)) {
        $current_user = $this->prophesize(AccountProxyInterface::class)->reveal();
      }
      if (!isset($entity_type_manager)) {
        $entity_type = $this->prophesize(EntityTypeInterface::class);
        $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
        $entity_type_manager->getDefinition($definition->getEntityTypeId())->willReturn($entity_type->reveal());
        $entity_type_manager = $entity_type_manager->reveal();
      }
      if (!isset($relation_type_manager)) {
        $relation_type_manager = $this->prophesize(GroupRelationTypeManagerInterface::class)->reveal();
      }

      $operation_provider = new OperationProvider(
        $module_handler,
        $current_user,
        $entity_type_manager,
        $relation_type_manager,
        $this->prophesize(TranslationInterface::class)->reveal()
      );
      $operation_provider->init($plugin_id, $definition);
      return $operation_provider;
    }

  }
}

namespace {

  /**
   * Dummy replacement function for Field UI's actual one.
   *
   * @param mixed $foo
   *   Can accept anything.
   *
   * @return array
   *   A dummy operation.
   */
  function field_ui_entity_operation($foo) {
    return ['bar' => []];
  }

}
