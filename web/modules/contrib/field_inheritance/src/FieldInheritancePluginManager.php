<?php

namespace Drupal\field_inheritance;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a FieldInheritance plugin manager.
 *
 * @see \Drupal\field_inheritance\Annotation\FieldInheritance
 * @see \Drupal\field_inheritance\FieldInheritancePluginInterface
 * @see plugin_api
 */
class FieldInheritancePluginManager extends DefaultPluginManager {

  /**
   * Constructs a FieldInheritancePluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/FieldInheritance',
      $namespaces,
      $module_handler,
      'Drupal\field_inheritance\FieldInheritancePluginInterface',
      'Drupal\field_inheritance\Annotation\FieldInheritance'
    );
    $this->alterInfo('field_inheritance_info');
    $this->setCacheBackend($cache_backend, 'field_inheritance_info_plugins');
  }

}
