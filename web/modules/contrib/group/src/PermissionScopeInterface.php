<?php

namespace Drupal\group;

/**
 * Defines the group permission scopes.
 */
interface PermissionScopeInterface {

  /**
   * Scope ID for people who do not belong to a group.
   */
  const OUTSIDER_ID = 'outsider';

  /**
   * Scope ID for people who do belong to a group.
   */
  const INSIDER_ID = 'insider';

  /**
   * Scope ID for individual people within a group.
   */
  const INDIVIDUAL_ID = 'individual';

}
