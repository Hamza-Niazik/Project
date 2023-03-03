<?php

namespace Drupal\group\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the ConfigWrapper entity.
 *
 * @ingroup group
 *
 * @ContentEntityType(
 *   id = "group_config_wrapper",
 *   label = @Translation("Config wrapper"),
 *   label_singular = @Translation("config wrapper"),
 *   label_plural = @Translation("config wrappers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count config wrapper",
 *     plural = "@count config wrappers"
 *   ),
 *   handlers = {
 *     "access" = "Drupal\group\Entity\Access\ConfigWrapperAccessControlHandler",
 *     "storage" = "Drupal\group\Entity\Storage\ConfigWrapperStorage",
 *     "storage_schema" = "Drupal\group\Entity\Storage\ConfigWrapperStorageSchema",
 *   },
 *   base_table = "group_config_wrapper",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "bundle",
 *   },
 * )
 */
class ConfigWrapper extends ContentEntityBase implements ConfigWrapperInterface {

  /**
   * {@inheritdoc}
   */
  public function getConfigEntity() {
    return $this->get('entity_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigEntityId() {
    return $this->get('entity_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Config'))
      ->setDescription(t('The config entity to wrap.'))
      // Required to force a string data type.
      ->setSetting('target_type', 'group_type')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $fields['entity_id'] = clone $base_field_definitions['entity_id'];
    $fields['entity_id']->setSetting('target_type', $bundle);
    return $fields;
  }

}
