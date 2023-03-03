<?php

namespace Drupal\recurring_events;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides an EventInstanceCreator plugin manager.
 *
 * @see \Drupal\recurring_events\Annotation\EventInstanceCreator
 * @see \Drupal\recurring_events\EventInstanceCreatorInterface
 * @see plugin_api
 */
class EventInstanceCreatorPluginManager extends DefaultPluginManager {

  /**
   * Constructs a EventInstanceCreatorPluginManager object.
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
      'Plugin/EventInstanceCreator',
      $namespaces,
      $module_handler,
      'Drupal\recurring_events\EventInstanceCreatorInterface',
      'Drupal\recurring_events\Annotation\EventInstanceCreator'
    );
    $this->alterInfo('eventinstance_creator_info');
    $this->setCacheBackend($cache_backend, 'eventinstance_creator_info_plugins');
  }

}
