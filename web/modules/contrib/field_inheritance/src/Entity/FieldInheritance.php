<?php

namespace Drupal\field_inheritance\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Field inheritance entity.
 *
 * @ConfigEntityType(
 *   id = "field_inheritance",
 *   label = @Translation("Field inheritance"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\field_inheritance\FieldInheritanceListBuilder",
 *     "form" = {
 *       "add" = "Drupal\field_inheritance\Form\FieldInheritanceForm",
 *       "edit" = "Drupal\field_inheritance\Form\FieldInheritanceForm",
 *       "ajax" = "Drupal\field_inheritance\Form\FieldInheritanceAjaxForm",
 *       "delete" = "Drupal\field_inheritance\Form\FieldInheritanceDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\field_inheritance\FieldInheritanceHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "field_inheritance",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/field_inheritance/{field_inheritance}",
 *     "add-form" = "/admin/structure/field_inheritance/add",
 *     "edit-form" = "/admin/structure/field_inheritance/{field_inheritance}/edit",
 *     "delete-form" = "/admin/structure/field_inheritance/{field_inheritance}/delete",
 *     "collection" = "/admin/structure/field_inheritance"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "type",
 *     "sourceEntityType",
 *     "destinationEntityType",
 *     "sourceEntityBundle",
 *     "destinationEntityBundle",
 *     "sourceField",
 *     "destinationField",
 *     "plugin"
 *   }
 * )
 */
class FieldInheritance extends ConfigEntityBase implements FieldInheritanceInterface {

  /**
   * The field inheritance ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The field inheritance label.
   *
   * @var string
   */
  protected $label;

  /**
   * The field inheritance type.
   *
   * @var string
   */
  protected $type;

  /**
   * The field inheritance source entity type.
   *
   * @var string
   */
  protected $sourceEntityType;

  /**
   * The field inheritance destination entity type.
   *
   * @var string
   */
  protected $destinationEntityType;

  /**
   * The field inheritance source entity bundle.
   *
   * @var string
   */
  protected $sourceEntityBundle;

  /**
   * The field inheritance destination entity bundle.
   *
   * @var string
   */
  protected $destinationEntityBundle;

  /**
   * The field inheritance source field.
   *
   * @var string
   */
  protected $sourceField;

  /**
   * The field inheritance destination field.
   *
   * @var string
   */
  protected $destinationField;

  /**
   * The field inheritance plugin.
   *
   * @var string
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function type() {
    return isset($this->type) ? $this->type : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function idWithoutTypeAndBundle() {
    $prefix = $this->destinationEntityType() . '_' . $this->destinationEntityBundle() . '_';
    return str_replace($prefix, '', $this->id());
  }

  /**
   * {@inheritdoc}
   */
  public function sourceEntityType() {
    return isset($this->sourceEntityType) ? $this->sourceEntityType : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function destinationEntityType() {
    return isset($this->destinationEntityType) ? $this->destinationEntityType : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function sourceEntityBundle() {
    return isset($this->sourceEntityBundle) ? $this->sourceEntityBundle : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function destinationEntityBundle() {
    return isset($this->destinationEntityBundle) ? $this->destinationEntityBundle : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function sourceField() {
    return isset($this->sourceField) ? $this->sourceField : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function destinationField() {
    return isset($this->destinationField) ? $this->destinationField : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function plugin() {
    return isset($this->plugin) ? $this->plugin : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->type = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceEntityType($source_entity_type) {
    $this->sourceEntityType = $source_entity_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDestinationEntityType($destination_entity_type) {
    $this->destinationEntityType = $destination_entity_type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceEntityBundle($source_entity_bundle) {
    $this->sourceEntityBundle = $source_entity_bundle;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDestinationEntityBundle($destination_entity_bundle) {
    $this->destinationEntityBundle = $destination_entity_bundle;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceField($source_field) {
    $this->sourceField = $source_field;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDestinationField($destination_field) {
    $this->destinationField = $destination_field;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin($plugin) {
    $this->plugin = $plugin;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    if (strpos($this->id(), $this->destinationEntityType() . '_' . $this->destinationEntityBundle() . '_') === FALSE) {
      $this->id = $this->destinationEntityType() . '_' . $this->destinationEntityBundle() . '_' . $this->id();
    }
    parent::save();
  }

}
