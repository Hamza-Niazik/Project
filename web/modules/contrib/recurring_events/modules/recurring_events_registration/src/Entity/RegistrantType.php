<?php

namespace Drupal\recurring_events_registration\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the registrant type entity.
 *
 * @ConfigEntityType(
 *   id = "registrant_type",
 *   label = @Translation("registrant type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\recurring_events_registration\RegistrantTypeListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\recurring_events_registration\Form\RegistrantTypeForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\recurring_events_registration\RegistrantTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "registrant_type",
 *   bundle_of = "registrant",
 *   admin_permission = "administer registrant entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/events/registrants/types/registrant_type/{registrant_type}",
 *     "edit-form" = "/admin/structure/events/registrants/types/registrant_type/{registrant_type}/edit",
 *     "collection" = "/admin/structure/events/registrants/types/registrant_type"
 *   },
 *   config_export = {
 *     "label",
 *     "id",
 *     "description",
 *   }
 * )
 */
class RegistrantType extends ConfigEntityBundleBase implements RegistrantTypeInterface {

  /**
   * The registrant type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The registrant type label.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this Registrant type.
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
