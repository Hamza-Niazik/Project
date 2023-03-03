<?php

namespace Drupal\group\Plugin\Field\FieldType;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\group\Entity\Storage\ConfigWrapperStorageInterface;

/**
 * Defines the 'group_relationship_target' entity field type.
 *
 * Extends EntityReferenceItem to dynamically support config entities.
 *
 * @FieldType(
 *   id = "group_relationship_target",
 *   label = @Translation("Group relationship target"),
 *   description = @Translation("A reference to either a content or wrapped config entity."),
 *   category = @Translation("Reference"),
 *   default_widget = "entity_reference_autocomplete",
 *   default_formatter = "entity_reference_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 *   no_ui = TRUE,
 * )
 */
class RelationshipEntityReferenceItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Massage incoming config entities into config wrappers.
    if ($this->getSetting('target_type') === 'group_config_wrapper') {
      $storage = \Drupal::entityTypeManager()->getStorage('group_config_wrapper');
      assert($storage instanceof ConfigWrapperStorageInterface);

      $entity_type_id = reset($this->getSetting('handler_settings')['target_bundles']);
      if ($values instanceof ConfigEntityInterface) {
        $values = $storage->wrapEntity($values);
      }
      elseif (is_string($values) && !is_numeric($values)) {
        $values = $storage->wrapEntityId($entity_type_id, $values)->id();
      }
      elseif (is_array($values)) {
        if (isset($values['entity']) && $values['entity'] instanceof ConfigEntityInterface) {
          $values['entity'] = $storage->wrapEntityId($entity_type_id, $values);
        }
        if (isset($values['target_id']) && is_string($values['target_id']) && !is_numeric($values['target_id'])) {
          $values['target_id'] = $storage->wrapEntityId($entity_type_id, $values)->id();
        }
      }
    }

    parent::setValue($values, $notify);
  }

}
