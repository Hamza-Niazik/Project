<?php

namespace Drupal\group;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Alters existing services for the Group module.
 */
class GroupServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Ensures you can update to enable VariationCache without the container
    // choking on the variation_cache_factory service no longer being there.
    if (!$container->hasDefinition('variation_cache_factory')) {
      $definition = new Definition('\Drupal\group\VariationCacheFactoryUpdateFix');
      $definition->setPublic(TRUE);
      $container->setDefinition('variation_cache_factory', $definition);
    }

    // Automatically create missing handler services for group relations.
    $modules = $container->getParameter('container.modules');
    $discovery = new AnnotatedClassDiscovery(
      'Plugin/Group/Relation',
      $container->get('container.namespaces'),
      'Drupal\group\Annotation\GroupRelationType',
      []
    );

    $handlers = [
      'access_control' => 'Drupal\group\Plugin\Group\RelationHandler\EmptyAccessControl',
      'entity_reference' => 'Drupal\group\Plugin\Group\RelationHandler\EmptyEntityReference',
      'operation_provider' => 'Drupal\group\Plugin\Group\RelationHandler\EmptyOperationProvider',
      'permission_provider' => 'Drupal\group\Plugin\Group\RelationHandler\EmptyPermissionProvider',
      'post_install' => 'Drupal\group\Plugin\Group\RelationHandler\EmptyPostInstall',
      'ui_text_provider' => 'Drupal\group\Plugin\Group\RelationHandler\EmptyUiTextProvider',
    ];

    foreach ($discovery->getDefinitions() as $group_relation_type_id => $group_relation_type) {
      assert($group_relation_type instanceof GroupRelationTypeInterface);
      // Skip plugins that whose provider is not installed.
      if (!isset($modules[$group_relation_type->getProvider()])) {
        continue;
      }

      foreach ($handlers as $handler => $handler_class) {
        $service_name = "group.relation_handler.$handler.$group_relation_type_id";
        if (!$container->has($service_name)) {
          // Define the service and pass it the default one to decorate.
          $definition = new Definition($handler_class, [new Reference("group.relation_handler.$handler")]);
          $definition->setPublic(TRUE);
          $definition->setShared(FALSE);
          $container->setDefinition($service_name, $definition);
        }
      }
    }
  }

}
