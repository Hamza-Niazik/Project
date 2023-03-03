<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;

/**
 * Trait for group relation handlers.
 *
 * This trait contains a few service getters for services that are often needed
 * in plugin handlers. When using one of these getters, please make sure you
 * inject the dependency into the corresponding property from within your
 * service's constructor.
 */
trait RelationHandlerTrait {

  /**
   * The parent relation handler in the decorator chain.
   *
   * You MUST set this when you are decorating an existing handler.
   *
   * @var \Drupal\group\Plugin\Group\RelationHandler\RelationHandlerInterface|null
   */
  protected $parent = NULL;

  /**
   * The plugin ID as read from the definition.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The group relation type.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface
   */
  protected $groupRelationType;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $groupRelationTypeManager;

  /**
   * {@inheritdoc}
   */
  public function init($plugin_id, GroupRelationTypeInterface $group_relation_type) {
    if (isset($this->parent)) {
      $this->parent->init($plugin_id, $group_relation_type);
    }
    $this->pluginId = $plugin_id;
    $this->groupRelationType = $group_relation_type;
  }

  /**
   * Gets the current user.
   *
   * @return \Drupal\Core\Session\AccountProxyInterface
   *   The current user.
   */
  protected function currentUser() {
    if (!$this->currentUser) {
      $this->currentUser = \Drupal::currentUser();
    }
    return $this->currentUser;
  }

  /**
   * Gets the entity type manager service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager service.
   */
  protected function entityTypeManager() {
    if (!$this->entityTypeManager) {
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }
    return $this->entityTypeManager;
  }

  /**
   * Gets the group relation type manager service.
   *
   * @return \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   *   The group relation type manager service.
   */
  protected function groupRelationTypeManager() {
    if (!$this->groupRelationTypeManager) {
      $this->groupRelationTypeManager = \Drupal::service('group_relation_type.manager');
    }
    return $this->groupRelationTypeManager;
  }

}
