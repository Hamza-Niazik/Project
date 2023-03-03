<?php

namespace Drupal\group\Plugin\Group\RelationHandlerDefault;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\group\Plugin\Group\RelationHandler\EntityReferenceInterface;
use Drupal\group\Plugin\Group\RelationHandler\EntityReferenceTrait;

/**
 * Provides post install tasks for group relations.
 */
class EntityReference implements EntityReferenceInterface {

  use EntityReferenceTrait;

  /**
   * {@inheritdoc}
   */
  public function configureField(BaseFieldDefinition $entity_reference) {
    $entity_type_id = $this->groupRelationType->getEntityTypeId();
    $bundle = $this->groupRelationType->getEntityBundle();

    // If we're dealing with config entities, the reference field actually uses
    // the config wrapper entities, so make sure that's reflected on the field.
    if ($this->groupRelationType->handlesConfigEntityType()) {
      $bundle = $entity_type_id;
      $entity_type_id = 'group_config_wrapper';
      $entity_reference->setSetting('handler', 'group_config_wrapper:target_entity');
    }

    $entity_reference->setSetting('target_type', $entity_type_id);
    if ($bundle) {
      $handler_settings = $entity_reference->getSetting('handler_settings');
      $handler_settings['target_bundles'] = [$bundle];
      $entity_reference->setSetting('handler_settings', $handler_settings);
    }

    if ($label = $this->groupRelationType->getEntityReferenceLabel()) {
      $entity_reference->setLabel($label);
    }
    if ($description = $this->groupRelationType->getEntityReferenceDescription()) {
      $entity_reference->setDescription($description);
    }
  }

}
