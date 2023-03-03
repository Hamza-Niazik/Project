<?php

namespace Drupal\Tests\group\Kernel\Views;

/**
 * Tests the group_relationship_to_entity_reverse relationship handler.
 *
 * @see \Drupal\group\Plugin\views\relationship\GroupRelationshipToEntityReverse
 *
 * @group group
 */
class GroupRelationshipToEntityReverseRelationshipTest extends GroupRelationshipToEntityRelationshipTest {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_group_relationship_to_entity_reverse_relationship'];

}
