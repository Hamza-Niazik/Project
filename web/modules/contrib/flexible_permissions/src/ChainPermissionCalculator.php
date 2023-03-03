<?php

namespace Drupal\flexible_permissions;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\variationcache\Cache\VariationCacheInterface;

/**
 * Collects permissions for an account.
 */
class ChainPermissionCalculator implements ChainPermissionCalculatorInterface {

  /**
   * The calculators.
   *
   * @var \Drupal\flexible_permissions\PermissionCalculatorInterface[]
   */
  protected $calculators = [];

  /**
   * The variation cache backend to use as a persistent cache.
   *
   * @var \Drupal\variationcache\Cache\VariationCacheInterface
   */
  protected $cache;

  /**
   * The variation cache backend to use as a static cache.
   *
   * @var \Drupal\variationcache\Cache\VariationCacheInterface
   */
  protected $static;

  /**
   * The regular cache backend to use as a static cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $regularStatic;

  /**
   * The account switcher service.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * Constructs a ChainPermissionCalculator object.
   *
   * @param \Drupal\variationcache\Cache\VariationCacheInterface $cache
   *   The variation cache to use as a persistent cache.
   * @param \Drupal\variationcache\Cache\VariationCacheInterface $static
   *   The variation cache to use as a static cache.
   * @param \Drupal\Core\Cache\CacheBackendInterface $regular_static
   *   The regular cache backend to use as a static cache.
   * @param \Drupal\Core\Session\AccountSwitcherInterface $account_switcher
   *   The account switcher service.
   */
  public function __construct(VariationCacheInterface $cache, VariationCacheInterface $static, CacheBackendInterface $regular_static, AccountSwitcherInterface $account_switcher) {
    $this->cache = $cache;
    $this->static = $static;
    $this->regularStatic = $regular_static;
    $this->accountSwitcher = $account_switcher;
  }

  /**
   * {@inheritdoc}
   */
  public function addCalculator(PermissionCalculatorInterface $calculator) {
    $this->calculators[] = $calculator;
  }

  /**
   * {@inheritdoc}
   */
  public function getCalculators() {
    return $this->calculators;
  }

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, $scope) {
    $persistent_cache_contexts = $this->getPersistentCacheContexts($scope);
    $initial_cacheability = (new CacheableMetadata())->addCacheContexts($persistent_cache_contexts);
    $cache_keys = ['flexible_permissions', $scope];

    // Whether to switch the user account during cache storage and retrieval.
    //
    // This is necessary because permissions may be stored varying by the user
    // cache context or one of its child contexts. Because we may be calculating
    // permissions for an account other than the current user, we need to ensure
    // that the cache ID for said entry is set according to the passed in
    // account's data.
    //
    // Drupal core does not help us here because there is no way to reuse the
    // cache context logic outside of the caching layer. This means that in
    // order to generate a cache ID based on, let's say, one's permissions, we'd
    // have to copy all of the permission hash generation logic. Same goes for
    // the optimizing/folding of cache contexts.
    //
    // Instead of doing so, we simply set the current user to the passed in
    // account, calculate the cache ID and then immediately switch back. It's
    // the cleanest solution we could come up with that doesn't involve copying
    // half of core's caching layer and that still allows us to use the
    // VariationCache for accounts other than the current user.
    $switch_account = FALSE;
    foreach ($persistent_cache_contexts as $cache_context) {
      [$cache_context_root] = explode('.', $cache_context, 2);
      if ($cache_context_root === 'user') {
        $switch_account = TRUE;
        $this->accountSwitcher->switchTo($account);
        break;
      }
    }

    // Retrieve the permissions from the static cache if available.
    $static_cache_hit = FALSE;
    $persistent_cache_hit = FALSE;
    if ($static_cache = $this->static->get($cache_keys, $initial_cacheability)) {
      $static_cache_hit = TRUE;
      $calculated_permissions = $static_cache->data;
    }
    // Retrieve the permissions from the persistent cache if available.
    elseif ($cache = $this->cache->get($cache_keys, $initial_cacheability)) {
      $persistent_cache_hit = TRUE;
      $calculated_permissions = $cache->data;
    }
    // Otherwise build the permissions and store them in the persistent cache.
    else {
      $calculated_permissions = new RefinableCalculatedPermissions();
      foreach ($this->getCalculators() as $calculator) {
        $calculated_permissions = $calculated_permissions->merge($calculator->calculatePermissions($account, $scope));
      }

      // Apply a cache tag to easily flush the calculated permissions.
      $calculated_permissions->addCacheTags(['flexible_permissions']);
    }

    if (!$static_cache_hit) {
      $cacheability = CacheableMetadata::createFromObject($calculated_permissions);

      // First store the actual calculated permissions in the persistent cache,
      // along with the final cache contexts after all calculations have run.
      if (!$persistent_cache_hit) {
        $this->cache->set($cache_keys, $calculated_permissions, $cacheability, $initial_cacheability);
      }

      // Then convert the calculated permissions to an immutable value object
      // and store it in the static cache so that we don't have to do the same
      // conversion every time we call for the calculated permissions from a
      // warm static cache.
      $calculated_permissions = new CalculatedPermissions($calculated_permissions);
      $this->static->set($cache_keys, $calculated_permissions, $cacheability, $initial_cacheability);
    }

    if ($switch_account) {
      $this->accountSwitcher->switchBack();
    }

    // Return the permissions as an immutable value object.
    return $calculated_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts($scope) {
    $cid = 'flexible_permissions:chain_calculator:contexts:' . $scope;

    // Retrieve the contexts from the regular static cache if available.
    if ($static_cache = $this->regularStatic->get($cid)) {
      $contexts = $static_cache->data;
    }
    else {
      $contexts = [];
      foreach ($this->getCalculators() as $calculator) {
        $contexts = array_merge($contexts, $calculator->getPersistentCacheContexts($scope));
      }

      // Store the contexts in the regular static cache.
      $this->regularStatic->set($cid, $contexts);
    }

    return $contexts;
  }

}
