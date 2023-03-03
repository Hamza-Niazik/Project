<?php

namespace Drupal\recurring_events\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Event series type entity.
 *
 * @ConfigEntityType(
 *   id = "eventseries_type",
 *   label = @Translation("Event series type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\recurring_events\EventSeriesTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\recurring_events\Form\EventSeriesTypeForm",
 *       "edit" = "Drupal\recurring_events\Form\EventSeriesTypeForm",
 *       "delete" = "Drupal\recurring_events\Form\EventSeriesTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\recurring_events\EventSeriesTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "eventseries_type",
 *   bundle_of = "eventseries",
 *   admin_permission = "administer eventseries entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "delete-form" = "/admin/structure/events/series/types/eventseries_type/{eventseries_type}/delete",
 *     "edit-form" = "/admin/structure/events/series/types/eventseries_type/{eventseries_type}/edit",
 *     "canonical" = "/admin/structure/events/series/types/eventseries_type/{eventseries_type}",
 *     "add-form" = "/admin/structure/events/series/types/eventseries_type/add",
 *     "collection" = "/admin/structure/events/series/types/eventseries_type"
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "description",
 *   }
 * )
 */
class EventSeriesType extends ConfigEntityBundleBase implements EventSeriesTypeInterface {

  /**
   * The Event series type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Event series type label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this eventseries type.
   *
   * @var string
   */
  protected $description;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

}
