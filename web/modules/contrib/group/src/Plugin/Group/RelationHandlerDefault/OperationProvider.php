<?php

namespace Drupal\group\Plugin\Group\RelationHandlerDefault;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\OperationProviderTrait;

/**
 * Provides operations for group relations.
 */
class OperationProvider implements OperationProviderInterface {

  use OperationProviderTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new OperationProvider.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $groupRelationTypeManager
   *   The group relation type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, GroupRelationTypeManagerInterface $groupRelationTypeManager, TranslationInterface $string_translation) {
    $this->moduleHandler = $module_handler;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->groupRelationTypeManager = $groupRelationTypeManager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(GroupTypeInterface $group_type) {
    $operations = [];

    $ui_allowed = !$this->groupRelationType->isEnforced() && !$this->groupRelationType->isCodeOnly();
    if ($relationship_type_id = $this->getRelationshipTypeId($group_type)) {
      $route_params = ['group_relationship_type' => $relationship_type_id];
      $operations['configure'] = [
        'title' => $this->t('Configure'),
        'url' => new Url('entity.group_relationship_type.edit_form', $route_params),
      ];

      if ($ui_allowed) {
        $operations['uninstall'] = [
          'title' => $this->t('Uninstall'),
          'weight' => 99,
          'url' => new Url('entity.group_relationship_type.delete_form', $route_params),
        ];
      }

      // This could be in its own decorator, but then it would live in a module
      // of its own purely for field_ui support. So let's keep it here.
      if ($this->moduleHandler->moduleExists('field_ui')) {
        $relationship_type = $this->entityTypeManager()->getStorage('group_relationship_type')->load($relationship_type_id);
        $operations += field_ui_entity_operation($relationship_type);
      }
    }
    elseif ($ui_allowed) {
      $operations['install'] = [
        'title' => $this->t('Install'),
        'url' => new Url('entity.group_relationship_type.add_form', [
          'group_type' => $group_type->id(),
          'plugin_id' => $this->pluginId,
        ]),
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupOperations(GroupInterface $group) {
    $operations = [];

    $create_permission = $this->permissionProvider->getPermission('create', 'entity');
    if ($create_permission && $group->hasPermission($create_permission, $this->currentUser())) {
      $key_parts = [$this->groupRelationType->id(), 'create'];
      if ($bundle_id = $this->groupRelationType->getEntityBundle()) {
        $key_parts[] = $bundle_id;
      }

      $bundle_entity_type = $this->entityType->getBundleEntityType();
      if ($bundle_id && $bundle_entity_type) {
        $bundle = $this->entityTypeManager()->getStorage($bundle_entity_type)->load($bundle_id);
        $label = $bundle->label();
      }
      else {
        $label = $this->entityType->getSingularLabel();
      }

      $route_params = ['group' => $group->id(), 'plugin_id' => $this->pluginId];
      $operations[implode('-', $key_parts)] = [
        'title' => $this->t('Add @type', ['@type' => $label]),
        'url' => new Url('entity.group_relationship.create_form', $route_params),
        'weight' => 30,
      ];
    }

    return $operations;
  }

}
