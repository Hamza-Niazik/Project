<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\group\Entity\GroupRelationshipTypeInterface;

/**
 * Provides post install tasks for the group_membership relation plugin.
 */
class GroupMembershipPostInstall implements PostInstallInterface {

  use PostInstallTrait;
  use StringTranslationTrait;

  /**
   * Constructs a new GroupMembershipPostInstall.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PostInstallInterface $parent
   *   The default post install handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(PostInstallInterface $parent, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->parent = $parent;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstallTasks() {
    $tasks = $this->parent->getInstallTasks();
    $tasks['install-group-roles-field'] = [$this, 'installGroupRolesField'];
    return $tasks;
  }

  /**
   * Installs the group_roles field.
   *
   * @param \Drupal\group\Entity\GroupRelationshipTypeInterface $relationship_type
   *   The GroupRelationshipType created by installing the plugin.
   * @param $is_syncing
   *   Whether config is syncing.
   */
  public function installGroupRolesField(GroupRelationshipTypeInterface $relationship_type, $is_syncing) {
    // Only create config objects while config import is not in progress.
    if ($is_syncing === TRUE) {
      return;
    }

    $fc_storage = $this->entityTypeManager()->getStorage('field_config');
    $fsc_storage = $this->entityTypeManager()->getStorage('field_storage_config');
    $efd_storage = $this->entityTypeManager()->getStorage('entity_form_display');
    $evd_storage = $this->entityTypeManager()->getStorage('entity_view_display');

    // Add the group_roles field to the newly added relationship type. The
    // field storage for this is defined in the config/install folder. The
    // default handler for 'group_role' target entities in the 'group_type'
    // handler group is GroupTypeRoleSelection.
    $relationship_type_id = $relationship_type->id();
    $field_storage = $fsc_storage->load('group_relationship.group_roles');
    $field = $fc_storage->load("group_relationship.$relationship_type_id.group_roles");
    if (!empty($field)) {
      throw new \RuntimeException(sprintf('The field group_roles already exists on relationship type "%s".', $relationship_type_id));
    }
    $fc_storage->save($fc_storage->create([
      'field_storage' => $field_storage,
      'bundle' => $relationship_type_id,
      'label' => $this->t('Roles'),
      'settings' => [
        'handler' => 'group_type:group_role',
        'handler_settings' => [
          'group_type_id' => $relationship_type->getGroupTypeId(),
        ],
      ],
    ]));

    // Build the 'default' display ID for both the entity form and view mode.
    $default_display_id = "group_relationship.$relationship_type_id.default";

    // Build or retrieve the 'default' form mode.
    if (!$form_display = $efd_storage->load($default_display_id)) {
      $form_display = $efd_storage->create([
        'targetEntityType' => 'group_relationship',
        'bundle' => $relationship_type_id,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    // Build or retrieve the 'default' view mode.
    if (!$view_display = $evd_storage->load($default_display_id)) {
      $view_display = $evd_storage->create([
        'targetEntityType' => 'group_relationship',
        'bundle' => $relationship_type_id,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }

    // Assign widget settings for the 'default' form mode.
    $efd_storage->save($form_display->setComponent('group_roles', [
      'type' => 'options_buttons',
    ]));

    // Assign display settings for the 'default' view mode.
    $evd_storage->save($view_display->setComponent('group_roles', [
      'label' => 'above',
      'type' => 'entity_reference_label',
      'settings' => [
        'link' => 0,
      ],
    ]));
  }

}
