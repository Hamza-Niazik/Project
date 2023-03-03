<?php

namespace Drupal\recurring_events\Plugin\views\filter;

/**
 * Filter handler for taxonomy terms with depth.
 *
 * This handler is actually part of the node table and has some restrictions,
 * because it uses a subquery to find nodes with.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("taxonomy_index_tid_eventinstance_depth")
 */
class TaxonomyIndexTidEventInstanceDepth extends TaxonomyIndexTidEventSeriesDepth {

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
