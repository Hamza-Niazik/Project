<?php

namespace Drupal\group\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\group\Entity\Storage\GroupRelationshipStorageInterface;
use Drupal\group\Entity\Storage\GroupRoleStorageInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the relationship entity.
 *
 * @ingroup group
 *
 * @ContentEntityType(
 *   id = "group_relationship",
 *   label = @Translation("Group relationship"),
 *   label_singular = @Translation("group relationship"),
 *   label_plural = @Translation("group relationships"),
 *   label_count = @PluralTranslation(
 *     singular = "@count group relationship",
 *     plural = "@count group relationships"
 *   ),
 *   bundle_label = @Translation("Group relationship type"),
 *   handlers = {
 *     "storage" = "Drupal\group\Entity\Storage\GroupRelationshipStorage",
 *     "storage_schema" = "Drupal\group\Entity\Storage\GroupRelationshipStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\group\Entity\Views\GroupRelationshipViewsData",
 *     "list_builder" = "Drupal\group\Entity\Controller\GroupRelationshipListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\group\Entity\Routing\GroupRelationshipRouteProvider",
 *     },
 *     "form" = {
 *       "add" = "Drupal\group\Entity\Form\GroupRelationshipForm",
 *       "edit" = "Drupal\group\Entity\Form\GroupRelationshipForm",
 *       "delete" = "Drupal\group\Entity\Form\GroupRelationshipDeleteForm",
 *       "group-join" = "Drupal\group\Form\GroupJoinForm",
 *       "group-leave" = "Drupal\group\Form\GroupLeaveForm",
 *     },
 *     "access" = "Drupal\group\Entity\Access\GroupRelationshipAccessControlHandler",
 *   },
 *   base_table = "group_relationship",
 *   data_table = "group_relationship_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *     "langcode" = "langcode",
 *     "bundle" = "type",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/group/{group}/content/add/{plugin_id}",
 *     "add-page" = "/group/{group}/content/add",
 *     "canonical" = "/group/{group}/content/{group_relationship}",
 *     "collection" = "/group/{group}/content",
 *     "create-form" = "/group/{group}/content/create/{plugin_id}",
 *     "create-page" = "/group/{group}/content/create",
 *     "delete-form" = "/group/{group}/content/{group_relationship}/delete",
 *     "edit-form" = "/group/{group}/content/{group_relationship}/edit"
 *   },
 *   bundle_entity_type = "group_relationship_type",
 *   field_ui_base_route = "entity.group_relationship_type.edit_form",
 *   permission_granularity = "bundle",
 *   constraints = {
 *     "GroupRelationshipCardinality" = {}
 *   }
 * )
 */
class GroupRelationship extends ContentEntityBase implements GroupRelationshipInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function getRelationshipType() {
    return $this->get('type')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->get('gid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupId() {
    return $this->get('gid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupType() {
    return $this->get('group_type')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupTypeId() {
    return $this->get('group_type')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    if ($this->getPlugin()->getRelationType()->handlesConfigEntityType()) {
      if ($entity = $this->get('entity_id')->entity) {
        return $entity->getConfigEntity();
      }
    }
    return $this->get('entity_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityId() {
    if ($this->getPlugin()->getRelationType()->handlesConfigEntityType()) {
      return $this->get('entity_id')->entity->getConfigEntityId();
    }
    return $this->get('entity_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getRelationshipType()->getPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->get('plugin_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByPluginId($plugin_id) {
    $storage = \Drupal::entityTypeManager()->getStorage('group_relationship');
    assert($storage instanceof GroupRelationshipStorageInterface);
    return $storage->loadByPluginId($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByEntity(EntityInterface $entity) {
    $storage = \Drupal::entityTypeManager()->getStorage('group_relationship');
    assert($storage instanceof GroupRelationshipStorageInterface);
    return $storage->loadByEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return _group_relation_type_manager()
      ->getUiTextProvider($this->getPluginId())
      ->getRelationshipLabel($this);
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['group'] = $this->getGroupId();

    // These routes depend on the plugin ID.
    $is_form_rel = in_array($rel, ['add-form', 'create-form']);
    if ($is_form_rel) {
      $uri_route_parameters['plugin_id'] = $this->getPluginId();
    }

    // These parameters are not needed here so let's remove them or else they'll
    // get added as query arguments for no reason.
    if ($is_form_rel || $rel == 'create-page') {
      unset($uri_route_parameters['group_relationship'], $uri_route_parameters['group_relationship_type']);
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage) {
    parent::postCreate($storage);

    // Set the denormalized data from the bundle entity.
    $this->set('plugin_id', $this->getRelationshipType()->getPluginId());
    $this->set('group_type', $this->getRelationshipType()->getGroupTypeId());
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Set the denormalized data from the bundle entity. We repeat this after
    // having set it in ::postCreate() because it's imperative that no-one
    // changes this at all.
    $this->set('plugin_id', $this->getRelationshipType()->getPluginId());
    $this->set('group_type', $this->getRelationshipType()->getGroupTypeId());

    // Set the label so the DB also reflects it.
    $this->set('label', $this->label());
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // For memberships, we generally need to rebuild the group role cache for
    // the member's user account in the target group.
    $rebuild_group_role_cache = $this->getPluginId() == 'group_membership';

    if ($update === FALSE) {
      // We want to make sure that the entity we just added to the group behaves
      // as a grouped entity. This means we may need to update access records,
      // flush some caches containing the entity or perform other operations we
      // cannot possibly know about. Lucky for us, all of that behavior usually
      // happens when saving an entity so let's re-save the added entity.
      $this->getEntity()->save();
    }

    // If a membership gets updated, but the member's roles haven't changed, we
    // do not need to rebuild the group role cache for the member's account.
    elseif ($rebuild_group_role_cache) {
      $new = array_column($this->group_roles->getValue(), 'target_id');
      $old = array_column($this->original->group_roles->getValue(), 'target_id');
      sort($new);
      sort($old);
      $rebuild_group_role_cache = ($new != $old);
    }

    if ($rebuild_group_role_cache) {
      $role_storage = \Drupal::entityTypeManager()->getStorage('group_role');
      assert($role_storage instanceof GroupRoleStorageInterface);
      $role_storage->resetUserGroupRoleCache($this->getEntity(), $this->getGroup());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    foreach ($entities as $group_relationship) {
      assert($group_relationship instanceof GroupRelationshipInterface);
      if ($entity = $group_relationship->getEntity()) {
        // For the same reasons we re-save entities that are added to a group,
        // we need to re-save entities that were removed from one. See
        // ::postSave(). We only save the entity if it still exists to avoid
        // trying to save an entity that just got deleted and triggered the
        // deletion of its relationship entities.
        // @todo Revisit when https://www.drupal.org/node/2754399 lands.
        $entity->save();

        // If a membership gets deleted, we need to reset the internal group
        // roles cache for the member in that group, but only if the user still
        // exists. Otherwise, it doesn't matter as the user ID will become void.
        if ($group_relationship->getPluginId() == 'group_membership') {
          $role_storage = \Drupal::entityTypeManager()->getStorage('group_role');
          assert($role_storage instanceof GroupRoleStorageInterface);
          $role_storage->resetUserGroupRoleCache($group_relationship->getEntity(), $group_relationship->getGroup());
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getListCacheTagsToInvalidate() {
    $tags = parent::getListCacheTagsToInvalidate();

    $group_id = $this->get('gid')->target_id;
    $plugin_id = $this->getRelationshipType()->getPluginId();
    $entity_id = $this->getEntityId();

    // A specific group gets any content, regardless of plugin used.
    // E.g.: A group's list of entities can be flushed with this.
    $tags[] = "group_relationship_list:group:$group_id";

    // A specific entity gets added to any group, regardless of plugin used.
    // E.g.: An entity's list of groups can be flushed with this.
    $tags[] = "group_relationship_list:entity:$entity_id";

    // Any entity gets added to any group using a specific plugin.
    // E.g.: A list of all memberships anywhere can be flushed with this.
    $tags[] = "group_relationship_list:plugin:$plugin_id";

    // A specific group gets any content using a specific plugin.
    // E.g.: A group's list of members can be flushed with this.
    $tags[] = "group_relationship_list:plugin:$plugin_id:group:$group_id";

    // A specific entity gets added to any group using a specific plugin.
    // E.g.: A user's list of memberships can be flushed with this.
    $tags[] = "group_relationship_list:plugin:$plugin_id:entity:$entity_id";

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['gid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent group'))
      ->setDescription(t('The group containing the entity.'))
      ->setSetting('target_type', 'group')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    // Borrowed this logic from the Comment module.
    // Warning! May change in the future: https://www.drupal.org/node/2346347
    $fields['entity_id'] = BaseFieldDefinition::create('group_relationship_target')
      ->setLabel(t('Content'))
      ->setDescription(t('The entity to add to the group.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setReadOnly(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ]);

    $fields['uid']
      ->setLabel(t('Group relationship creator'))
      ->setDescription(t('The username of the group relationship creator.'))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created on'))
      ->setDescription(t('The time that the group relationship was created.'))
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed on'))
      ->setDescription(t('The time that the group relationship was last edited.'))
      ->setTranslatable(TRUE);

    // The following fields are denormalizations of info found on the bundle,
    // but often necessary to write performant joins. As we can't join config
    // entity tables, we explicitly store the info again on this entity type.
    $fields['plugin_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Plugin ID'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['group_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Group type'))
      ->setSetting('target_type', 'group_type')
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    if (\Drupal::moduleHandler()->moduleExists('path')) {
      $fields['path'] = BaseFieldDefinition::create('path')
        ->setLabel(t('URL alias'))
        ->setTranslatable(TRUE)
        ->setDisplayOptions('form', [
          'type' => 'path',
          'weight' => 30,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setComputed(TRUE);
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    if ($relationship_type = GroupRelationshipType::load($bundle)) {
      assert($relationship_type instanceof GroupRelationshipTypeInterface);
      $fields['entity_id'] = clone $base_field_definitions['entity_id'];
      _group_relation_type_manager()
        ->getEntityReferenceHandler($relationship_type->getPluginId())
        ->configureField($fields['entity_id']);

      return $fields;
    }

    return [];
  }

}
