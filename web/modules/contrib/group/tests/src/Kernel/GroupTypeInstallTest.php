<?php

namespace Drupal\Tests\group\Kernel;

use Drupal\group\Entity\GroupTypeInterface;

/**
 * Tests the creation of group type entities during extension install.
 *
 * @coversDefaultClass \Drupal\group\Entity\GroupType
 * @group group
 */
class GroupTypeInstallTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['group_test_config'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig('group_test_config');
  }

  /**
   * Tests special behavior during group type creation.
   *
   * @covers ::postSave
   */
  public function testInstall() {
    // Check that the group type was installed properly.
    $group_type = $this->entityTypeManager
      ->getStorage('group_type')
      ->load('default');
    assert($group_type instanceof GroupTypeInterface);
    $this->assertNotNull($group_type, 'Group type was loaded successfully.');

    // Check that the enforced plugins give priority to the Yaml files.
    $plugin = $group_type->getPlugin('group_membership');
    $config = $plugin->getConfiguration();
    $this->assertEquals('99', $config['group_cardinality'], 'Enforced group_membership plugin was created from Yaml file.');
  }

}
