<?php

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity\Controller\RevisionOverviewController;
use Drupal\group\Entity\GroupInterface;

/**
 * Returns responses for Group revision UI routes.
 */
class GroupRevisionOverviewController extends RevisionOverviewController {

  /**
   * {@inheritdoc}
   */
  protected function hasRevertRevisionAccess(EntityInterface $group) {
    assert($group instanceof GroupInterface);
    return $group->hasPermission('revert group revisions', $this->currentUser());
  }

  /**
   * {@inheritdoc}
   */
  protected function hasDeleteRevisionAccess(EntityInterface $group) {
    assert($group instanceof GroupInterface);
    return $group->hasPermission('delete group revisions', $this->currentUser());
  }

}
