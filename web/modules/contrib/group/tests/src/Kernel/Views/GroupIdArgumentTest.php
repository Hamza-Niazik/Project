<?php

namespace Drupal\Tests\group\Kernel\Views;

use Drupal\views\Views;

/**
 * Tests the group_id argument handler.
 *
 * @see \Drupal\group\Plugin\views\argument\GroupId
 *
 * @group group
 */
class GroupIdArgumentTest extends GroupViewsKernelTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_group_id_argument'];

  /**
   * Tests the group_id argument.
   */
  public function testGroupIdArgument() {
    $view = Views::getView('test_group_id_argument');
    $view->setDisplay();

    $this->createGroup();
    $group2 = $this->createGroup();

    $view->preview();
    $this->assertEquals(2, count($view->result), 'Found the expected number of results.');

    // Set the second group id as an argument.
    $view->destroy();
    $view->preview('default', [$group2->id()]);

    // Verify that the title is overridden.
    $this->assertEquals($group2->label(), $view->getTitle());

    // Verify that the argument filtering works.
    $this->assertEquals(1, count($view->result), 'Found the expected number of results.');
    $this->assertEquals((string) $view->style_plugin->getField(0, 'id'), $group2->id(), 'Found the correct group id.');

    // Verify that setting a non-existing id as argument results in no groups
    // being shown.
    $view->destroy();
    $view->preview('default', [22]);
    $this->assertEquals(0, count($view->result), 'Found the expected number of results.');
  }

}
