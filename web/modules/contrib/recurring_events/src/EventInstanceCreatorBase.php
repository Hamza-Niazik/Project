<?php

namespace Drupal\recurring_events;

use Drupal\Component\Plugin\PluginBase;
use Drupal\recurring_events\Entity\EventSeries;
use Drupal\recurring_events\EventCreationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class for EventInstanceCreator plugins.
 *
 * @see \Drupal\recurring_events\Annotation\EventInstanceCreator
 * @see \Drupal\recurring_events\EventInstanceCreatorInterface
 */
abstract class EventInstanceCreatorBase extends PluginBase implements EventInstanceCreatorInterface {

  /**
   * The event creation service.
   *
   * @var \Drupal\recurring_events\EventCreationService
   */
  protected EventCreationService $creationService;

  /**
   * Constructs a \Drupal\recurring_events\EventInstanceCreatorBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\recurring_events\EventCreationService $creation_service
   *   The event creation service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EventCreationService $creation_service) {
    $this->configuration = $configuration;
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->creationService = $creation_service;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('recurring_events.event_creation_service')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function description() {
    // Retrieve the @description property from the annotation and return it.
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritDoc}
   */
  abstract public function processInstances(EventSeries $series);

}
