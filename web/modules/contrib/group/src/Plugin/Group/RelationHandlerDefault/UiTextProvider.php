<?php

namespace Drupal\group\Plugin\Group\RelationHandlerDefault;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Plugin\Group\RelationHandler\UiTextProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\UiTextProviderTrait;

/**
 * Provides UI text for group relations.
 */
class UiTextProvider implements UiTextProviderInterface {

  use UiTextProviderTrait;

  /**
   * Constructs a new UiTextProvider.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationshipLabel(GroupRelationshipInterface $group_relationship) {
    return $group_relationship->getEntity()->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getAddPageLabel($create_mode) {
    return $this->groupRelationType->getLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function getAddPageDescription($create_mode) {
    $t_args = ['%entity_type' => $this->entityType->getSingularLabel()];

    if ($bundle = $this->groupRelationType->getEntityBundle()) {
      $storage = $this->entityTypeManager()->getStorage($this->entityType->getBundleEntityType());
      $t_args['%bundle'] = $storage->load($bundle)->label();
      return $create_mode
        ? $this->t('Add new %entity_type of type %bundle to the group.', $t_args)
        : $this->t('Add existing %entity_type of type %bundle to the group.', $t_args);
    }

    return $create_mode
      ? $this->t('Add new %entity_type to the group.', $t_args)
      : $this->t('Add existing %entity_type to the group.', $t_args);
  }

  /**
   * {@inheritdoc}
   */
  public function getAddFormTitle($create_mode) {
    return $this->t('Add @name', ['@name' => $this->groupRelationType->getLabel()]);
  }

}
