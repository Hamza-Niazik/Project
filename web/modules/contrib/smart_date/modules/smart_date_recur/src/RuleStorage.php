<?php

namespace Drupal\smart_date_recur;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Controller class for Smart Date Recur's rules.
 *
 * This extends the Drupal\Core\Entity\Sql\SqlContentEntityStorage class, adding
 * required special handling for rule entities.
 */
class RuleStorage extends SqlContentEntityStorage implements RuleStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function getRuleIdsToCheck() {
    return $this->database->query('SELECT rid FROM {' . $this->getBaseTable() . '} WHERE `limit` IS NULL')
      ->fetchCol();
  }

}
