<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Component\Discovery\YamlDiscovery;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;

/**
 * Tests the gathering and processing of group permissions.
 *
 * @coversDefaultClass \Drupal\group\Access\GroupPermissionHandler
 * @group group
 */
class GroupPermissionHandlerTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test_plugin', 'node'];

  /**
   * The group permission handler service.
   *
   * @var \Drupal\group\Access\GroupPermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['user', 'group_test_plugin']);
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('node');
    $this->permissionHandler = $this->container->get('group.permissions');
    $this->pluginManager = $this->container->get('group_relation_type.manager');
  }

  /**
   * Tests getting all of the permissions.
   *
   * @covers ::getPermissions
   */
  public function testGetPermissions() {
    $permissions = $this->permissionHandler->getPermissions();
    $expected = count($this->getGeneralGroupPermissionNames());
    $this->assertCount($expected, $permissions, 'Permission count matches what is in Yaml file.');

    $permissions = $this->permissionHandler->getPermissions(TRUE);
    $expected += count($this->pluginManager->getPermissionProvider('group_membership')->buildPermissions());
    $expected += count($this->pluginManager->getPermissionProvider('entity_test_relation')->buildPermissions());
    $expected += count($this->pluginManager->getPermissionProvider('group_relation')->buildPermissions());
    $expected += count($this->pluginManager->getPermissionProvider('user_relation')->buildPermissions());
    $expected += count($this->pluginManager->getPermissionProvider('node_relation:article')->buildPermissions());
    $expected += count($this->pluginManager->getPermissionProvider('node_relation:page')->buildPermissions());
    $expected += count($this->pluginManager->getPermissionProvider('node_type_relation')->buildPermissions());
    $this->assertCount($expected, $permissions, 'Permission count matches what is in Yaml file and defined by plugins.');
  }

  /**
   * Tests getting the permissions for a particular group type.
   *
   * @covers ::getPermissionsByGroupType
   */
  public function testGetPermissionsByGroupType() {
    $group_type_a = $this->createGroupType();
    $group_type_b = $this->createGroupType();
    $group_type_c = $this->createGroupType();

    $expected = count($this->getGeneralGroupPermissionNames());
    $expected += count($this->pluginManager->getPermissionProvider('group_membership')->buildPermissions());
    $this->assertCount($expected, $this->permissionHandler->getPermissionsByGroupType($group_type_a), 'Permission count matches what is in Yaml file and membership plugin.');
    $this->assertCount($expected, $this->permissionHandler->getPermissionsByGroupType($group_type_b), 'Permission count matches what is in Yaml file and membership plugin.');
    $this->assertCount($expected, $this->permissionHandler->getPermissionsByGroupType($group_type_c), 'Permission count matches what is in Yaml file and membership plugin.');

    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);
    $storage->save($storage->createFromPlugin($group_type_a, 'group_relation'));
    $storage->save($storage->createFromPlugin($group_type_b, 'user_relation'));

    $expected_a = $expected_b = $expected;
    $expected_a += count($this->pluginManager->getPermissionProvider('group_relation')->buildPermissions());
    $expected_b += count($this->pluginManager->getPermissionProvider('user_relation')->buildPermissions());
    $this->assertCount($expected_a, $this->permissionHandler->getPermissionsByGroupType($group_type_a), 'Permission count matches what is in Yaml file and installed plugins.');
    $this->assertCount($expected_b, $this->permissionHandler->getPermissionsByGroupType($group_type_b), 'Permission count matches what is in Yaml file and installed plugins.');
    $this->assertCount($expected, $this->permissionHandler->getPermissionsByGroupType($group_type_c), 'Permission count matches what is in Yaml file and membership plugin.');
  }

  /**
   * Retrieves the general group permission names from Yaml.
   *
   * Unlike the processing in the actual handler, this only grabs the group
   * permissions from group.group.permissions.yml so that we can compare machine
   * names and counts. Callbacks and other modules are not followed or fetched.
   *
   * @return string[]
   *   A list of permission machine names.
   */
  protected function getGeneralGroupPermissionNames() {
    $yaml_discovery = (new YamlDiscovery(
      'group.permissions',
      $this->container->get('module_handler')->getModuleDirectories()
    ))->findAll();

    return isset($yaml_discovery['group'])
      ? array_keys($yaml_discovery['group'])
      : [];
  }

}
