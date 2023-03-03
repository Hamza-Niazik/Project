<?php

namespace Drupal\field_inheritance;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Field inheritance entities.
 */
class FieldInheritanceListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Field inheritance');
    $header['id'] = $this->t('Machine name');
    $header['type'] = $this->t('Type');
    $header['source_entity'] = $this->t('Source Entity/Bundle');
    $header['source_field'] = $this->t('Source Field');
    $header['destination_entity'] = $this->t('Destination Entity/Bundle');
    $header['destination_field'] = $this->t('Destination Field');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['type'] = $entity->type();
    $row['source_entity'] = $entity->sourceEntityType() . ':' . $entity->sourceEntityBundle();
    $row['source_field'] = $entity->sourceField();
    $row['destination_entity'] = $entity->destinationEntityType() . ':' . $entity->destinationEntityBundle();
    $row['destination_field'] = $entity->destinationField() ?: $this->t('N/A');
    return $row + parent::buildRow($entity);
  }

}
