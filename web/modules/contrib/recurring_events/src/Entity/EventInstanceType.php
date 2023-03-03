<?php

namespace Drupal\recurring_events\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Event instance type entity.
 *
 * @ConfigEntityType(
 *   id = "eventinstance_type",
 *   label = @Translation("Event instance type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\recurring_events\EventInstanceTypeListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\recurring_events\Form\EventInstanceTypeForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\recurring_events\EventInstanceTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "eventinstance_type",
 *   bundle_of = "eventinstance",
 *   admin_permission = "administer eventinstance entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/events/instance/types/eventinstance_type/{eventinstance_type}",
 *     "edit-form" = "/admin/structure/events/instance/types/eventinstance_type/{eventinstance_type}/edit",
 *     "collection" = "/admin/structure/events/instance/types/eventinstance_type"
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "description",
 *   }
 * )
 */
class EventInstanceType extends ConfigEntityBundleBase implements EventInstanceTypeInterface {

  /**
   * The Event instance type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Event instance type label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this eventinstance type.
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
