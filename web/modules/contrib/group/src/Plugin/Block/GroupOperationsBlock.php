<?php

namespace Drupal\group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with operations the user can perform on a group.
 *
 * @Block(
 *   id = "group_operations",
 *   admin_label = @Translation("Group operations"),
 *   context_definitions = {
 *     "group" = @ContextDefinition("entity:group", required = FALSE)
 *   }
 * )
 */
class GroupOperationsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $pluginManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Creates a GroupOperationsBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $plugin_manager
   *   The group relation type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GroupRelationTypeManagerInterface $plugin_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginManager = $plugin_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('group_relation_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // The operations available in this block vary per the current user's group
    // permissions. It obviously also varies per group, but we cannot know for
    // sure how we got that group as it is up to the context provider to
    // implement that. This block will then inherit the appropriate cacheable
    // metadata from the context, as set by the context provider.
    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->setCacheContexts(['user.group_permissions']);

    if (($group = $this->getContextValue('group')) && $group->id()) {
      assert($group instanceof GroupInterface);
      $links = [];

      // Retrieve the operations and cacheable metadata from the plugins.
      foreach ($group->getGroupType()->getInstalledPlugins() as $plugin) {
        assert($plugin instanceof GroupRelationInterface);
        $operation_provider = $this->pluginManager->getOperationProvider($plugin->getRelationTypeId());
        $operations = $operation_provider->getGroupOperations($group);
        $cacheable_metadata = $cacheable_metadata->merge(CacheableMetadata::createFromRenderArray($operations));
        unset($operations['#cache']);
        $links += $operations;
      }

      if ($links) {
        // Allow modules to alter the collection of gathered links.
        $this->moduleHandler->alter('group_operations', $links, $group);

        // Sort the operations by weight.
        uasort($links, '\Drupal\Component\Utility\SortArray::sortByWeightElement');

        // Create an operations element with all of the links.
        $build['#type'] = 'operations';
        $build['#links'] = $links;
      }
    }

    // Set the cacheable metadata on the build.
    $cacheable_metadata->applyTo($build);

    return $build;
  }

}
