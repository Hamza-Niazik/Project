<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

/**
 * Provides a default UI text provider.
 *
 * In case a plugin does not define a handler, the empty class is used so that
 * others can still decorate the plugin-specific service.
 */
class EmptyUiTextProvider implements UiTextProviderInterface {

  use UiTextProviderTrait;

  /**
   * Constructs a new EmptyUiTextProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\UiTextProviderInterface $parent
   *   The parent UI text provider.
   */
  public function __construct(UiTextProviderInterface $parent) {
    $this->parent = $parent;
  }

}
