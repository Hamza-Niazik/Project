<?php

namespace Drupal\group\Entity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for GroupRelationship routes.
 */
class GroupRelationshipController extends ControllerBase {

  /**
   * The private store factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $privateTempStoreFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $groupRelationTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new GroupRelationshipController.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The private store factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *   The entity form builder.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $groupRelationTypeManager
   *   The group relation type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $entity_type_manager, EntityFormBuilderInterface $entity_form_builder, GroupRelationTypeManagerInterface $groupRelationTypeManager, RendererInterface $renderer) {
    $this->privateTempStoreFactory = $temp_store_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->groupRelationTypeManager = $groupRelationTypeManager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
      $container->get('group_relation_type.manager'),
      $container->get('renderer'),
    );
  }

  /**
   * Provides the relationship creation overview page.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the relationship to.
   * @param bool $create_mode
   *   (optional) Whether the target entity still needs to be created. Defaults
   *   to FALSE, meaning the target entity is assumed to exist already.
   * @param string|null $base_plugin_id
   *   (optional) A base plugin ID to filter the bundles on. This can be useful
   *   when you want to show the add page for just a single plugin that has
   *   derivatives for the target entity type's bundles.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The relationship creation overview page or a redirect to the form for
   *   adding relationships if there is only one relationship type.
   */
  public function addPage(GroupInterface $group, $create_mode = FALSE, $base_plugin_id = NULL) {
    $build = ['#theme' => 'entity_add_list', '#bundles' => []];
    $form_route = $this->addPageFormRoute($group, $create_mode);
    $relationship_types = $this->addPageBundles($group, $create_mode, $base_plugin_id);

    // Set the add bundle message if available.
    $add_bundle_message = $this->addPageBundleMessage($group, $create_mode);
    if ($add_bundle_message !== FALSE) {
      $build['#add_bundle_message'] = $add_bundle_message;
    }

    // Filter out the bundles the user doesn't have access to.
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('group_relationship');
    foreach ($relationship_types as $relationship_type_id => $relationship_type) {
      $access = $access_control_handler->createAccess($relationship_type_id, NULL, ['group' => $group], TRUE);
      if (!$access->isAllowed()) {
        unset($relationship_types[$relationship_type_id]);
      }
      $this->renderer->addCacheableDependency($build, $access);
    }

    // Redirect if there's only one bundle available.
    if (count($relationship_types) == 1) {
      $route_params = [
        'group' => $group->id(),
        'plugin_id' => reset($relationship_types)->getPluginId(),
      ];
      $url = Url::fromRoute($form_route, $route_params, ['absolute' => TRUE]);
      return new RedirectResponse($url->toString());
    }

    // Set the info for all of the remaining bundles.
    foreach ($relationship_types as $relationship_type_id => $relationship_type) {
      $ui_text_provider = $this->groupRelationTypeManager->getUiTextProvider($relationship_type->getPluginId());

      $label = $ui_text_provider->getAddPageLabel($create_mode);
      $build['#bundles'][$relationship_type_id] = [
        'label' => $label,
        'description' => $ui_text_provider->getAddPageDescription($create_mode),
        'add_link' => Link::createFromRoute($label, $form_route, [
          'group' => $group->id(),
          'plugin_id' => $relationship_type->getPluginId(),
        ]),
      ];
    }

    // Add the list cache tags for the GroupRelationshipType entity type.
    $bundle_entity_type = $this->entityTypeManager->getDefinition('group_relationship_type');
    $build['#cache']['tags'] = $bundle_entity_type->getListCacheTags();

    return $build;
  }

  /**
   * Retrieves a list of available relationship types for the add page.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the relationship to.
   * @param bool $create_mode
   *   Whether the target entity still needs to be created.
   * @param string|null $base_plugin_id
   *   (optional) A base plugin ID to filter the bundles on. This can be useful
   *   when you want to show the add page for just a single plugin that has
   *   derivatives for the target entity type's bundles.
   *
   * @return \Drupal\group\Entity\GroupRelationshipTypeInterface[]
   *   An array of relationship types, keyed by their ID.
   *
   * @see ::addPage()
   */
  protected function addPageBundles(GroupInterface $group, $create_mode, $base_plugin_id) {
    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);

    $relationship_types = $storage->loadByGroupType($group->getGroupType());
    foreach ($relationship_types as $relationship_type_id => $relationship_type) {
      $relation = $relationship_type->getPlugin();

      // Check the base plugin ID if a plugin filter was specified.
      if ($base_plugin_id && $relation->getBaseId() === $base_plugin_id) {
        unset($relationship_types[$relationship_type_id]);
      }
      // Skip the bundle if we are listing bundles that allow you to create an
      // entity in the group and the bundle's plugin does not support that.
      elseif ($create_mode && !$relation->getRelationType()->definesEntityAccess()) {
        unset($relationship_types[$relationship_type_id]);
      }
    }

    return $relationship_types;
  }

  /**
   * Returns the 'add_bundle_message' string for the add page.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the relationship to.
   * @param bool $create_mode
   *   Whether the target entity still needs to be created.
   *
   * @return string|false
   *   The translated string or FALSE if no string should be set.
   *
   * @see ::addPage()
   */
  protected function addPageBundleMessage(GroupInterface $group, $create_mode) {
    // We do not set the 'add_bundle_message' variable because we deny access to
    // the page if no bundle is available. This method exists so that modules
    // that extend this controller may specify a message should they decide to
    // allow access to their page even if it has no bundles.
    return FALSE;
  }

  /**
   * Returns the route name of the form the add page should link to.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the relationship to.
   * @param bool $create_mode
   *   Whether the target entity still needs to be created.
   *
   * @return string
   *   The route name.
   *
   * @see ::addPage()
   */
  protected function addPageFormRoute(GroupInterface $group, $create_mode) {
    return $create_mode
      ? 'entity.group_relationship.create_form'
      : 'entity.group_relationship.add_form';
  }

  /**
   * Provides the relationship submission form.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the relationship to.
   * @param string $plugin_id
   *   The group relation to add content with.
   *
   * @return array
   *   A group submission form.
   */
  public function addForm(GroupInterface $group, $plugin_id) {
    $storage = $this->entityTypeManager()->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);

    $values = [
      'type' => $storage->getRelationshipTypeId($group->bundle(), $plugin_id),
      'gid' => $group->id(),
    ];
    $group_relationship = $this->entityTypeManager()->getStorage('group_relationship')->create($values);

    return $this->entityFormBuilder->getForm($group_relationship, 'add');
  }

  /**
   * The _title_callback for the entity.group_relationship.add_form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the relationship to.
   * @param string $plugin_id
   *   The group relation to add content with.
   *
   * @return string
   *   The page title.
   */
  public function addFormTitle(GroupInterface $group, $plugin_id) {
    return $this->groupRelationTypeManager->getUiTextProvider($plugin_id)->getAddFormTitle(FALSE);
  }

  /**
   * The _title_callback for the entity.group_relationship.edit_form route.
   *
   * Overrides the Drupal\Core\Entity\Controller\EntityController::editTitle().
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityInterface $_entity
   *   (optional) An entity, passed in directly from the request attributes.
   *
   * @return string|null
   *   The title for the entity edit page, if an entity was found.
   */
  public function editFormTitle(RouteMatchInterface $route_match, EntityInterface $_entity = NULL) {
    if ($entity = $route_match->getParameter('group_relationship')) {
      return $this->t('Edit %label', ['%label' => $entity->label()]);
    }
  }

  /**
   * The _title_callback for the entity.group_relationship.collection route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the relationship to.
   *
   * @return string
   *   The page title.
   */
  public function collectionTitle(GroupInterface $group) {
    return $this->t('All entity relations for @group', ['@group' => $group->label()]);
  }

  /**
   * Provides the relationship creation form.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to add the relationship to.
   * @param string $plugin_id
   *   The group relation to add content with.
   *
   * @return array
   *   A relationship creation form.
   */
  public function createForm(GroupInterface $group, $plugin_id) {
    $group_relation = $group->getGroupType()->getPlugin($plugin_id);
    $group_relation_type = $group_relation->getRelationType();

    $wizard_id = 'group_entity';
    $store = $this->privateTempStoreFactory->get($wizard_id);
    $store_id = $plugin_id . ':' . $group->id();

    // See if the plugin uses a wizard for creating new entities. Also pass this
    // info to the form state.
    $config = $group_relation->getConfiguration();
    $extra['group_wizard'] = $config['use_creation_wizard'];
    $extra['group_wizard_id'] = $wizard_id;

    // Pass the group, plugin ID and store ID to the form state as well.
    $extra['group'] = $group;
    $extra['group_relation'] = $plugin_id;
    $extra['store_id'] = $store_id;

    // See if we are on the second step of the form.
    $step2 = $extra['group_wizard'] && $store->get("$store_id:step") === 2;

    // Grouped entity form, potentially as wizard step 1.
    if (!$step2) {
      // Figure out what entity type the plugin is serving.
      $entity_type_id = $group_relation_type->getEntityTypeId();
      $entity_type = $this->entityTypeManager()->getDefinition($entity_type_id);
      $storage = $this->entityTypeManager()->getStorage($entity_type_id);

      // Only create a new entity if we have nothing stored.
      if (!$entity = $store->get("$store_id:entity")) {
        $values = [];
        if (($key = $entity_type->getKey('bundle')) && ($bundle = $group_relation_type->getEntityBundle())) {
          $values[$key] = $bundle;
        }
        $entity = $storage->create($values);
      }

      // Use the add form handler if available.
      $operation = 'default';
      if ($entity_type->getFormClass('add')) {
        $operation = 'add';
      }
    }
    // Wizard step 2: Group relationship form.
    else {
      $relationship_type_storage = $this->entityTypeManager()->getStorage('group_relationship_type');
      assert($relationship_type_storage instanceof GroupRelationshipTypeStorageInterface);

      // Create an empty relationship entity.
      $values = [
        'type' => $relationship_type_storage->getRelationshipTypeId($group->bundle(), $plugin_id),
        'gid' => $group->id(),
      ];
      $entity = $this->entityTypeManager()->getStorage('group_relationship')->create($values);

      // Group relationship entities have an add form handler.
      $operation = 'add';
    }

    // Return the entity form with the configuration gathered above.
    return $this->entityFormBuilder()->getForm($entity, $operation, $extra);
  }

  /**
   * The _title_callback for the entity.group_relationship.create_form route.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to create the relationship for.
   * @param string $plugin_id
   *   The group relation to create the relationship with.
   *
   * @return string
   *   The page title.
   */
  public function createFormTitle(GroupInterface $group, $plugin_id) {
    return $this->groupRelationTypeManager->getUiTextProvider($plugin_id)->getAddFormTitle(TRUE);
  }

}
