<?php

namespace Drupal\recurring_events\Plugin\views\argument;

/**
 * Argument handler for event instances with taxonomy terms with depth.
 *
 * Normally taxonomy terms with depth contextual filter can be used
 * only for content. This handler can be used for Recurring Events instances.
 *
 * Handler expects reference field name, gets reference table and column and
 * builds sub query on that table. That is why handler does not need special
 * relation table like taxonomy_index.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("taxonomy_index_tid_eventinstance_depth")
 */
class IndexTidEventInstanceDepth extends IndexTidEventSeriesDepth {

  /**
   * The entity type.
   *
   * @var \string
   */
  protected $entityType = 'eventinstance';

  /**
   * The entity type label.
   *
   * @var \string
   */
  protected $entityTypeLabel = 'Event Instance';

}
