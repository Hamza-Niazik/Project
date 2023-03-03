<?php

namespace Drupal\group\Entity\Controller;

use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the user permissions administration form for a specific group type.
 */
class GroupTypeController extends ControllerBase {

  /**
   * The group type to use in this controller.
   *
   * @var \Drupal\group\Entity\GroupTypeInterface
   */
  protected $groupType;

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $pluginManager;

  /**
   * The module manager.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GroupTypeController.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $plugin_manager
   *   The group relation type manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, GroupRelationTypeManagerInterface $plugin_manager) {
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('group_relation_type.manager')
    );
  }

  /**
   * Builds an admin interface to manage the group type's group relations.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to build an interface for.
   *
   * @return array
   *   The render array for the page.
   */
  public function content(GroupTypeInterface $group_type) {
    $this->groupType = $group_type;

    $rows['installed'] = $rows['available'] = [];
    $installed_ids = $this->pluginManager->getInstalledIds($group_type);

    foreach ($this->pluginManager->getDefinitions() as $plugin_id => $group_relation_type) {
      assert($group_relation_type instanceof GroupRelationTypeInterface);
      $is_installed = in_array($plugin_id, $installed_ids, TRUE);
      $status = $is_installed ? 'installed' : 'available';
      $rows[$status][$plugin_id] = $this->buildRow($plugin_id, $group_relation_type, $is_installed);
    }

    $page['information'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Information about content plugins'),
    ];

    $page['information']['intro']['#markup'] = $this->t('<p>In order to be able to relate entities to groups of this group type, a so-called relation plugin needs to be installed. This plugin informs the Group module on how the entity type can be added to a group, what rules apply and whether it should control access over said entity type. When a plugin is installed, you should check out its configuration form to see what options are available to further customize the plugin behavior.</p>');
    $page['information']['fields']['#markup'] = $this->t('<p>Should you choose to show the relationship entities that track which entity belongs to which group or should the module that provided the module enforce this, you can control which fields are available on that relation entity and how they are presented in the front-end.</p>');
    $page['information']['install_types'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('<strong>Manual</strong> content plugins can be (un)installed freely by the user'),
        $this->t('<strong>Code-only</strong> content plugins can only be (un)installed through code, this is often done when certain conditions are met in the module that provided the plugin'),
        $this->t('<strong>Enforced</strong> content plugins are always enabled and cannot be uninstalled'),
      ],
      '#prefix' => $this->t('<p>The following installation types are available:</p>'),
    ];

    $page['system_compact_link'] = [
      '#id' => FALSE,
      '#type' => 'system_compact_link',
    ];

    $page['content'] = [
      '#type' => 'table',
      '#header' => [
        'info' => $this->t('Plugin information'),
        'provider' => $this->t('Provided by'),
        'entity_type_id' => $this->t('Applies to'),
        'status' => $this->t('Status'),
        'install_type' => $this->t('Installation type'),
        'operations' => $this->t('Operations'),
      ],
    ];
    $page['content'] += $rows['installed'];
    $page['content'] += $rows['available'];

    return $page;
  }

  /**
   * Builds a row for a group relation type.
   *
   * @param string $plugin_id
   *   The relation plugin ID.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface $group_relation_type
   *   The group relation type.
   * @param bool $is_installed
   *   Whether the group relation type is installed.
   *
   * @return array
   *   A render array to use as a table row.
   */
  public function buildRow($plugin_id, GroupRelationTypeInterface $group_relation_type, $is_installed) {
    $status = $is_installed ? $this->t('Installed') : $this->t('Available');

    $install_type = $this->t('Manual');
    if ($group_relation_type->isEnforced()) {
      $install_type = $this->t('Enforced');
    }
    elseif ($group_relation_type->isCodeOnly()) {
      $install_type = $this->t('Code-only');
    }

    $row = [
      'info' => [
        '#type' => 'inline_template',
        '#template' => '<div class="description"><span class="label">{{ label }}</span>{% if description %}<br/>{{ description }}{% endif %}</div>',
        '#context' => [
          'label' => $group_relation_type->getLabel(),
        ],
      ],
      'provider' => [
        '#markup' => $this->moduleHandler->getName($group_relation_type->getProvider())
      ],
      'entity_type_id' => [
        '#markup' => $this->entityTypeManager->getDefinition($group_relation_type->getEntityTypeId())->getLabel()
      ],
      'status' => ['#markup' => $status],
      'install_type' => ['#markup' => $install_type],
      'operations' => $this->buildOperations($plugin_id),
    ];

    // Show the group relation description if toggled on.
    if (!system_admin_compact_mode()) {
      $row['info']['#context']['description'] = $group_relation_type->getDescription();
    }

    return $row;
  }

  /**
   * Builds operation links for the group type's relation plugins.
   *
   * @param string $plugin_id
   *   The relation plugin ID.
   *
   * @return array
   *   A render array of operation links.
   */
  public function buildOperations($plugin_id) {
    $build = [
      '#type' => 'operations',
      '#links' => $this->pluginManager->getOperationProvider($plugin_id)->getOperations($this->groupType),
    ];
    uasort($build['#links'], '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $build;
  }

}
