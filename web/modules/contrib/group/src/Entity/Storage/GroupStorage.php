<?php

namespace Drupal\group\Entity\Storage;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\group\Entity\GroupInterface;

/**
 * Defines the storage handler class for group entities.
 */
class GroupStorage extends SqlContentEntityStorage {

  /**
   * {@inheritdoc}
   */
  public function restore(EntityInterface $entity) {
    assert($entity instanceof GroupInterface);
    // It seems that SqlFieldableEntityTypeListenerTrait::copyData() does not
    // care about the revision creation time or user at all, leading to broken
    // UIs after the update that enabled revisions. To fix this, we implement
    // the necessary logic here, but ideally this should be fixed in core.
    if (!$entity->getRevisionCreationTime()) {
      $entity->setRevisionCreationTime($entity->getCreatedTime());
    }
    if (!$entity->getRevisionUser()) {
      $entity->setRevisionUserId($entity->getOwnerId());
    }
    if (!$entity->getRevisionLogMessage()) {
      $entity->setRevisionLogMessage('');
    }
    parent::restore($entity);
  }

}
