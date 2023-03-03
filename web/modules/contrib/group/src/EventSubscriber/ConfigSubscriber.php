<?php

namespace Drupal\group\EventSubscriber;

use Drupal\Core\Config\ConfigImporterEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to configuration imports.
 */
class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a new ConfigSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $plugin_manager
   *   The group relation type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupRelationTypeManagerInterface $plugin_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * Causes a group role synchronization after importing config.
   *
   * @param \Drupal\Core\Config\ConfigImporterEvent $event
   *   The configuration event.
   */
  public function onConfigImport(ConfigImporterEvent $event) {
    $this->pluginManager->installEnforced();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ConfigEvents::IMPORT] = 'onConfigImport';
    return $events;
  }

}
