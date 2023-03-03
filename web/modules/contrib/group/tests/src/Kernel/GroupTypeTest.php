<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\Entity\Exception\ConfigEntityIdLengthException;
use Drupal\group\Entity\GroupTypeInterface;

/**
 * Tests the general behavior of group type entities.
 *
 * @coversDefaultClass \Drupal\group\Entity\GroupType
 * @group group
 */
class GroupTypeTest extends GroupKernelTestBase {

  /**
   * Tests the maximum ID length of a group type.
   *
   * @covers ::preSave
   */
  public function testMaximumIdLength() {
    $this->expectException(ConfigEntityIdLengthException::class);
    $this->expectExceptionMessageMatches('/Attempt to create a group type with an ID longer than \d+ characters: \w+\./');
    $this->entityTypeManager
      ->getStorage('group_type')
      ->create([
        'id' => $this->randomMachineName(GroupTypeInterface::ID_MAX_LENGTH + 1),
        'label' => 'Invalid ID length group type',
        'description' => '',
      ])
      ->save();
  }

  /**
   * Tests the retrieval of the collection of installed plugins.
   *
   * @covers ::getInstalledPlugins
   */
  public function testGetInstalledPlugins() {
    $plugins = $this->createGroupType()->getInstalledPlugins();
    $this->assertInstanceOf('\Drupal\group\Plugin\Group\Relation\GroupRelationCollection', $plugins, 'Loaded the installed plugin collection.');
    $this->assertCount(1, $plugins, 'Plugin collection has one plugin instance.');
  }

  /**
   * Tests whether a group type can tell if it has a plugin installed.
   *
   * @covers ::hasPlugin
   */
  public function testHasPlugin() {
    $group_type = $this->createGroupType();
    $this->assertTrue($group_type->hasPlugin('group_membership'), 'Found the group_membership plugin.');
    $this->assertFalse($group_type->hasPlugin('fake_plugin_id'), 'Could not find the fake_plugin_id plugin.');
  }

  /**
   * Tests the retrieval of an installed plugin.
   *
   * @covers ::getPlugin
   */
  public function testGetInstalledPlugin() {
    $plugin = $this->createGroupType()->getPlugin('group_membership');
    $this->assertInstanceOf('\Drupal\group\Plugin\Group\Relation\GroupRelationInterface', $plugin, 'Loaded the group_membership plugin.');
  }

  /**
   * Tests the retrieval of a non-existent plugin.
   *
   * @covers ::getPlugin
   */
  public function testGetNonExistentPlugin() {
    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage("Plugin ID 'fake_plugin_id' was not found.");
    $this->createGroupType()->getPlugin('fake_plugin_id');
  }

}
