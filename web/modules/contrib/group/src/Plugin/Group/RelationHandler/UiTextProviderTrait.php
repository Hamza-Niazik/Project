<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;

/**
 * Trait for group relation UI text providers.
 */
trait UiTextProviderTrait {

  use RelationHandlerTrait {
    init as traitInit;
  }
  use StringTranslationTrait;

  /**
   * The entity type the plugin handler is for.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * {@inheritdoc}
   */
  public function init($plugin_id, GroupRelationTypeInterface $group_relation_type) {
    $this->traitInit($plugin_id, $group_relation_type);
    $this->entityType = $this->entityTypeManager()->getDefinition($group_relation_type->getEntityTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationshipLabel(GroupRelationshipInterface $group_relationship) {
    if (!isset($this->parent)) {
      throw new \LogicException('Using UiTextProviderTrait without assigning a parent or overwriting the methods.');
    }
    return $this->parent->getRelationshipLabel($group_relationship);
  }

  /**
   * {@inheritdoc}
   */
  public function getAddPageLabel($create_mode) {
    if (!isset($this->parent)) {
      throw new \LogicException('Using UiTextProviderTrait without assigning a parent or overwriting the methods.');
    }
    return $this->parent->getAddPageLabel($create_mode);
  }

  /**
   * {@inheritdoc}
   */
  public function getAddPageDescription($create_mode) {
    if (!isset($this->parent)) {
      throw new \LogicException('Using UiTextProviderTrait without assigning a parent or overwriting the methods.');
    }
    return $this->parent->getAddPageDescription($create_mode);
  }

  /**
   * {@inheritdoc}
   */
  public function getAddFormTitle($create_mode) {
    if (!isset($this->parent)) {
      throw new \LogicException('Using UiTextProviderTrait without assigning a parent or overwriting the methods.');
    }
    return $this->parent->getAddFormTitle($create_mode);
  }

}
