<?php

namespace Drupal\group\Plugin\Group\Relation;

use Drupal\Component\Plugin\Definition\PluginDefinition;

/**
 * Provides an implementation of a group relation type and its metadata.
 */
class GroupRelationType extends PluginDefinition implements GroupRelationTypeInterface {

  /**
   * Any additional properties and values.
   *
   * @var array
   */
  protected $additional = [];

  /**
   * The name of the deriver of this plugin, if any.
   *
   * @var string|null
   */
  protected $deriver;

  /**
   * The administrative label for the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  protected $label;

  /**
   * The administrative description for the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  protected $description;

  /**
   * (optional) The label for the entity reference field.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  protected $reference_label;

  /**
   * (optional) The description for the entity reference field.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  protected $reference_description;

  /**
   * The entity type ID the plugin supports.
   *
   * @var string
   */
  protected $entity_type_id;

  /**
   * (optional) The bundle of the entity type the plugin supports.
   *
   * Do not specify if your plugin manages all bundles.
   *
   * @var string|false
   */
  protected $entity_bundle = FALSE;

  /**
   * (optional) Whether the supported entity type is config.
   *
   * This will be determined by the plugin manager, no need to set it.
   *
   * @var bool
   */
  protected $config_entity_type = FALSE;

  /**
   * (optional) Whether the plugin defines entity access.
   *
   * This controls whether you can create entities within the group (TRUE) or
   * only add existing ones (FALSE), or if you can update or delete entities
   * that have been added to the group. It also generates the necessary group
   * permissions when enabled.
   *
   * @var bool
   */
  protected $entity_access = FALSE;

  /**
   * (optional) Whether this plugin is always on.
   *
   * @var bool
   */
  protected $enforced = FALSE;

  /**
   * (optional) Whether this plugin can only be (un)installed through code.
   *
   * This is useful for plugins that should not be enabled by choice, but rather
   * when certain conditions are met throughout the site. When that happens, you
   * should install the plugin on a group type through code, at which point it
   * will show up in the plugin overview as enabled.
   *
   * @var bool
   */
  protected $code_only = FALSE;

  /**
   * (optional) The key to use in automatically generated paths.
   *
   * This is exposed through tokens so modules like Pathauto may use it. Only
   * use this if your plugin has something meaningful to show on the actual
   * relationship entity. Otherwise leave blank so it defaults to 'content'.
   *
   * @var string
   */
  protected $pretty_path_key = 'content';

  /**
   * (optional) The admin permission for this plugin.
   *
   * @var string|false
   */
  protected $admin_permission = FALSE;

  /**
   * Constructs a new GroupRelationType.
   *
   * @param array $definition
   *   An array of values from the annotation.
   */
  public function __construct($definition) {
    foreach ($definition as $property => $value) {
      $this->set($property, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($property) {
    if (property_exists($this, $property)) {
      $value = isset($this->{$property}) ? $this->{$property} : NULL;
    }
    else {
      $value = isset($this->additional[$property]) ? $this->additional[$property] : NULL;
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function set($property, $value) {
    if (property_exists($this, $property)) {
      $this->{$property} = $value;
    }
    else {
      $this->additional[$property] = $value;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeriver() {
    return $this->deriver;
  }

  /**
   * {@inheritdoc}
   */
  public function setDeriver($deriver) {
    $this->deriver = $deriver;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityReferenceLabel() {
    return $this->reference_label;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityReferenceDescription() {
    return $this->reference_description;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->entity_type_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityBundle() {
    return $this->entity_bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function handlesConfigEntityType() {
    return $this->config_entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public function definesEntityAccess() {
    return $this->entity_access;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnforced() {
    return $this->enforced;
  }

  /**
   * {@inheritdoc}
   */
  public function isCodeOnly() {
    return $this->code_only;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrettyPathKey() {
    return $this->pretty_path_key;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminPermission() {
    return $this->admin_permission;
  }

}
