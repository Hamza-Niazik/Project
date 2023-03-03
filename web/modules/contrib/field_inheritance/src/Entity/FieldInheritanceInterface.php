<?php

namespace Drupal\field_inheritance\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Field inheritance entities.
 */
interface FieldInheritanceInterface extends ConfigEntityInterface {

  /**
   * Get the inheritance type.
   *
   * @return string
   *   The inheritance type.
   */
  public function type();

  /**
   * Get the inheritance ID without the type and bundle.
   *
   * @return string
   *   The inheritance ID without the type and bundle.
   */
  public function idWithoutTypeAndBundle();

  /**
   * Get the inheritance source entity type.
   *
   * @return string
   *   The inheritance source entity type.
   */
  public function sourceEntityType();

  /**
   * Get the inheritance destination entity type.
   *
   * @return string
   *   The inheritance destination entity type.
   */
  public function destinationEntityType();

  /**
   * Get the inheritance source entity bundle.
   *
   * @return string
   *   The inheritance source entity bundle.
   */
  public function sourceEntityBundle();

  /**
   * Get the inheritance destination entity bundle.
   *
   * @return string
   *   The inheritance destination entity bundle.
   */
  public function destinationEntityBundle();

  /**
   * Get the inheritance source field.
   *
   * @return string
   *   The inheritance source field.
   */
  public function sourceField();

  /**
   * Get the inheritance destination field.
   *
   * @return string
   *   The inheritance destination field.
   */
  public function destinationField();

  /**
   * Get the inheritance plugin.
   *
   * @return string
   *   The inheritance plugin.
   */
  public function plugin();

  /**
   * Set the inheritance type.
   *
   * @var string $type
   *   The inheritance type.
   *
   * @return $this
   */
  public function setType($type);

  /**
   * Set the inheritance source entity type.
   *
   * @var string $source_entity_type
   *   The inheritance source entity type.
   *
   * @return $this
   */
  public function setSourceEntityType($source_entity_type);

  /**
   * Set the inheritance destination entity type.
   *
   * @var string $destination_entity_type
   *   The inheritance destination entity type.
   *
   * @return $this
   */
  public function setDestinationEntityType($destination_entity_type);

  /**
   * Set the inheritance source entity bundles.
   *
   * @var string $source_entity_bundles
   *   The inheritance source entity bundles.
   *
   * @return $this
   */
  public function setSourceEntityBundle($source_entity_bundles);

  /**
   * Set the inheritance destination entity bundles.
   *
   * @var string $destination_entity_bundles
   *   The inheritance destination entity bundles.
   *
   * @return $this
   */
  public function setDestinationEntityBundle($destination_entity_bundles);

  /**
   * Set the inheritance source field.
   *
   * @var string $source_field
   *   The inheritance source field.
   *
   * @return $this
   */
  public function setSourceField($source_field);

  /**
   * Set the inheritance destination field.
   *
   * @var string $destination_field
   *   The inheritance destination field.
   *
   * @return $this
   */
  public function setDestinationField($destination_field);

  /**
   * Set the inheritance plugin.
   *
   * @var string $plugin
   *   The inheritance plugin.
   *
   * @return $this
   */
  public function setPlugin($plugin);

}
