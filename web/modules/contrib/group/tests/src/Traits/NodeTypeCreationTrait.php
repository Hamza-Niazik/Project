<?php

namespace Drupal\Tests\group\Traits;

/**
 * Provides methods to create node types based on default settings.
 */
trait NodeTypeCreationTrait {

  /**
   * Creates a node type.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   *
   * @return \Drupal\node\NodeTypeInterface
   *   The created node type entity.
   */
  protected function createNodeType(array $values = []) {
    $storage = $this->entityTypeManager->getStorage('node_type');
    $node_type = $storage->create($values + [
      'type' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ]);
    $storage->save($node_type);
    return $node_type;
  }

}
