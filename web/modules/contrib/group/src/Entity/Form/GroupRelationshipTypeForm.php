<?php

namespace Drupal\group\Entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Entity\GroupRelationshipTypeInterface;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for relationship type forms.
 */
class GroupRelationshipTypeForm extends EntityForm {

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a new GroupRelationshipTypeForm.
   *
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $plugin_manager
   *   The group relation type manager.
   */
  public function __construct(GroupRelationTypeManagerInterface $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('group_relation_type.manager')
    );
  }

  /**
   * Returns the configurable plugin for the relationship type.
   *
   * @return \Drupal\group\Plugin\Group\Relation\GroupRelationInterface
   *   The configurable group relation.
   */
  protected function getPlugin() {
    $relationship_type = $this->getEntity();
    assert($relationship_type instanceof GroupRelationshipTypeInterface);
    $group_type = $relationship_type->getGroupType();

    // Initialize an empty plugin so we can show a default configuration form.
    if ($this->operation == 'add') {
      $plugin_id = $relationship_type->getPluginId();
      $configuration['group_type_id'] = $group_type->id();
      return $this->pluginManager->createInstance($plugin_id, $configuration);
    }
    // Return the already configured plugin for existing relationship types.
    else {
      return $relationship_type->getPlugin();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $relationship_type = $this->getEntity();
    assert($relationship_type instanceof GroupRelationshipTypeInterface);
    $group_type = $relationship_type->getGroupType();
    $group_relation = $this->getPlugin();
    $group_relation_type = $this->getPlugin()->getRelationType();

    // @todo These messages may need some love.
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Install content plugin');
      $message = 'By installing the %plugin plugin, you will allow %entity_type entities to be added to groups of type %group_type';
    }
    else {
      $form['#title'] = $this->t('Configure content plugin');
      $message = 'This form allows you to configure the %plugin plugin for the %group_type group type.';
    }

    // Add in the replacements for the $message variable set above.
    $replace = [
      '%plugin' => $group_relation_type->getLabel(),
      '%entity_type' => $this->entityTypeManager->getDefinition($group_relation_type->getEntityTypeId())->getLabel(),
      '%group_type' => $group_type->label(),
    ];

    // Display a description to explain the purpose of the form.
    $form['description'] = [
      '#markup' => $this->t($message, $replace),
    ];

    // Add in the plugin configuration form.
    $form += $group_relation->buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->operation == 'add' ? $this->t('Install plugin') : $this->t('Save configuration'),
      '#submit' => ['::submitForm'],
    ];

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $plugin = $this->getPlugin();
    $plugin->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $relationship_type = $this->getEntity();
    assert($relationship_type instanceof GroupRelationshipTypeInterface);
    $group_type = $relationship_type->getGroupType();
    $plugin = $this->getPlugin();
    $plugin->submitConfigurationForm($form, $form_state);

    // Remove button and internal Form API values from submitted values.
    $form_state->cleanValues();

    // Extract the values as configuration that should be saved.
    $config = $form_state->getValues();

    // If we are on an 'add' form, we create the relationship type using the
    // plugin configuration submitted using this form.
    if ($this->operation == 'add') {
      $storage = $this->entityTypeManager->getStorage('group_relationship_type');
      assert($storage instanceof GroupRelationshipTypeStorageInterface);
      $storage->createFromPlugin($group_type, $plugin->getRelationTypeId(), $config)->save();
      $this->messenger()->addStatus($this->t('The content plugin was installed on the group type.'));
    }
    // Otherwise, we update the existing relationship type's configuration.
    else {
      $relationship_type->updatePlugin($config);
      $this->messenger()->addStatus($this->t('The content plugin configuration was saved.'));
    }

    $form_state->setRedirect('entity.group_type.content_plugins', ['group_type' => $group_type->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    if ($route_match->getRawParameter($entity_type_id) !== NULL) {
      return $route_match->getParameter($entity_type_id);
    }

    // If we are on the create form, we can't extract an entity from the route,
    // so we need to create one based on the route parameters.
    $values = [];
    if ($route_match->getRawParameter('group_type') !== NULL && $route_match->getRawParameter('plugin_id') !== NULL) {
      $values = [
        'group_type' => $route_match->getRawParameter('group_type'),
        'content_plugin' => $route_match->getRawParameter('plugin_id'),
      ];
    }
    return $this->entityTypeManager->getStorage($entity_type_id)->create($values);
  }

}
