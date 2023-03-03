<?php

namespace Drupal\group\Plugin\Group\RelationHandlerDefault;

use Drupal\group\Plugin\Group\RelationHandler\PostInstallInterface;
use Drupal\group\Plugin\Group\RelationHandler\PostInstallTrait;

/**
 * Provides post install tasks for group relations.
 */
class PostInstall implements PostInstallInterface {

  use PostInstallTrait;

  /**
   * {@inheritdoc}
   */
  public function getInstallTasks() {
    // By default, plugins have nothing to do after installation.
    return [];
  }

}
