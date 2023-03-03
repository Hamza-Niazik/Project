<?php

namespace Drupal\recurring_events_registration\Plugin\views\access;

use Drupal\views\Plugin\views\access\AccessPluginBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\recurring_events_registration\AccessHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Custom access plugin for Event Registration views.
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *   id = "event_registration_list_access",
 *   title = @Translation("View Event Registration List"),
 *   help = @Translation("Access will be granted depending on the registration configuration of an event.")
 * )
 */
class EventRegistrationListAccess extends AccessPluginBase {

  /**
   * The access handler.
   *
   * @var \Drupal\recurring_events_registration\AccessHandler
   */
  protected $accessHandler;

  /**
   * Constructs an EventRegistrationListAccess object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Drupal\recurring_events_registration\AccessHandler $access_handler
   *   The access handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccessHandler $access_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->accessHandler = $access_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('recurring_events_registration.access_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('Event Registration Access');
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $this->accessHandler->eventHasRegistration() && $this->accessHandler->userHasPermission($account);
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_custom_access', 'recurring_events_registration.access_handler::eventRegistrationListAccess');
  }

}
