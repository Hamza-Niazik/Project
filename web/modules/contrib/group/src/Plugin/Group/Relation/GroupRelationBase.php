<?php

namespace Drupal\group\Plugin\Group\Relation;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\group\Entity\GroupType;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a base class for GroupRelation plugins.
 *
 * @see \Drupal\group\Annotation\GroupRelationType
 * @see \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManager
 * @see \Drupal\group\Plugin\Group\Relation\GroupRelationInterface
 * @see plugin_api
 */
abstract class GroupRelationBase extends PluginBase implements GroupRelationInterface {

  /**
   * The ID of group type this plugin was instantiated for.
   *
   * @var string
   */
  protected $groupTypeId;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Only support setting the group type ID during construction.
    if (empty($configuration['group_type_id'])) {
      throw new PluginException('Instantiating a group relation plugin requires a group type ID.');
    }
    $this->groupTypeId = $configuration['group_type_id'];

    // Include the default configuration by calling ::setConfiguration().
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationTypeId() {
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationType() {
    return $this->pluginDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupCardinality() {
    return $this->configuration['group_cardinality'];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityCardinality() {
    return $this->configuration['entity_cardinality'];
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupType() {
    return GroupType::load($this->getGroupTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupTypeId() {
    return $this->groupTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // Do not allow the changing of the group type ID after construction.
    unset($configuration['group_type_id']);

    // Merge in the default configuration.
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Warning: For every key defined here you need to have a matching config
    // schema entry following the pattern group_relation.config.MY_KEY!
    // @see group.schema.yml
    return [
      'group_cardinality' => 0,
      'entity_cardinality' => 0,
      'use_creation_wizard' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $entity_type_manager = \Drupal::entityTypeManager();

    $replace = [
      '%entity_type' => $entity_type_manager->getDefinition($this->getRelationType()->getEntityTypeId())->getLabel(),
      '%group_type' => $this->getGroupType()->label(),
      '%plugin' => $this->getRelationType()->getLabel(),
    ];

    $form['group_cardinality'] = [
      '#type' => 'number',
      '#title' => $this->t('Group cardinality'),
      '#description' => $this->t('The amount of %group_type groups a single %entity_type entity can be added to as a %plugin. Set to 0 for unlimited.', $replace),
      '#default_value' => $this->configuration['group_cardinality'],
      '#min' => 0,
      '#required' => TRUE,
    ];

    $form['entity_cardinality'] = [
      '#type' => 'number',
      '#title' => $this->t('Entity cardinality'),
      '#description' => $this->t('The amount of times a single %entity_type entity can be added to the same %group_type group as a %plugin. Set to 0 for unlimited.', $replace),
      '#default_value' => $this->configuration['entity_cardinality'],
      '#min' => 0,
      '#required' => TRUE,
    ];

    if ($this->getRelationType()->definesEntityAccess()) {
      $form['use_creation_wizard'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use 2-step wizard when creating a new %entity_type entity within a %group_type group', $replace),
        '#description' => $this->t('This will first show you the form to create the actual entity and then a form to create the relationship between the entity and the group.<br />You can choose to disable this wizard if you did not or will not add any fields to the relationship (i.e. this plugin).<br /><strong>Warning:</strong> If you do have fields on the relationship and do not use the wizard, you may end up with required fields not being filled out.'),
        '#default_value' => $this->configuration['use_creation_wizard'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   *
   * Only override this function if you need to do something specific to the
   * submitted data before it is saved as configuration on the plugin. The data
   * gets saved on the plugin in \Drupal\group\Entity\Form\GroupRelationshipTypeForm.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $entity_type = \Drupal::entityTypeManager()->getDefinition($this->getRelationType()->getEntityTypeId());
    $dependencies['module'][] = $this->getRelationType()->getProvider();
    $dependencies['module'][] = $entity_type->getProvider();
    return $dependencies;
  }

}
