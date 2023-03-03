<?php

namespace Drupal\flexible_permissions\Cache;

use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCache;

/**
 * Placeholder for when/if core commits this properly.
 *
 * @internal
 */
class MemoryCacheFactory implements CacheFactoryInterface {

  /**
   * Instantiated memory cache bins.
   *
   * @var \Drupal\Core\Cache\MemoryCache\MemoryCache[]
   */
  protected $bins = [];

  /**
   * {@inheritdoc}
   */
  public function get($bin) {
    if (!isset($this->bins[$bin])) {
      $this->bins[$bin] = new MemoryCache();
    }
    return $this->bins[$bin];
  }

}
