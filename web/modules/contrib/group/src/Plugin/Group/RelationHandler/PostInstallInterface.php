<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

/**
 * Provides a common interface for group relation post install handlers.
 */
interface PostInstallInterface extends RelationHandlerInterface {

  /**
   * Retrieves the tasks to run after plugin installation.
   *
   * It's really important that you only return tasks to run rather than run
   * tasks directly. The former approach allows other modules to disable or
   * change your task whereas the latter approach makes the decorator approach
   * we're using for handlers fall short.
   *
   * @return array
   *   A list of callables as accepted by call_user_func_array(), preferably
   *   keyed by a human-readable name so that other modules can easily find
   *   your callback if they wish to change it.
   *
   *   The callbacks will receive 2 arguments:
   *   - The GroupRelationshipTypeInterface created by installing the plugin.
   *   - A boolean indicating whether the config is syncing. If TRUE, do not add
   *     any one-off config because they will already be added by the sync.
   */
  public function getInstallTasks();

}
