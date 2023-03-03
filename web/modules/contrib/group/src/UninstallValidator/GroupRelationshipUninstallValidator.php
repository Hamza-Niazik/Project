<?php

namespace Drupal\group\UninstallValidator;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\group\Entity\GroupRelationshipType;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;

class GroupRelationshipUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

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
   * Constructs a new GroupRelationshipUninstallValidator object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $plugin_manager
   *   The group relation type manager.
   */
  public function __construct(TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager, GroupRelationTypeManagerInterface $plugin_manager) {
    $this->stringTranslation = $string_translation;
    $this->entityTypeManager = $entity_type_manager;
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = $plugin_names = [];

    foreach ($this->pluginManager->getDefinitions() as $plugin_id => $group_relation_type) {
      assert($group_relation_type instanceof GroupRelationTypeInterface);
      if ($group_relation_type->getProvider() == $module && $this->hasRelationships($plugin_id)) {
        $plugin_names[] = $group_relation_type->getLabel();
      }
    }

    if (!empty($plugin_names)) {
      $reasons[] = $this->t('The following group relations still have content for them: %plugins.', ['%plugins' => implode(', ', $plugin_names)]);
    }

    return $reasons;
  }

  /**
   * Determines if there is any relationship for a group relation.
   *
   * @param string $plugin_id
   *   The group relation type ID to check for relationships.
   *
   * @return bool
   *   Whether there are relationships for the given plugin ID.
   */
  protected function hasRelationships($plugin_id) {
    $relationship_types = array_keys(GroupRelationshipType::loadByPluginId($plugin_id));

    if (empty($relationship_types)) {
      return FALSE;
    }

    $entity_count = $this->entityTypeManager->getStorage('group_relationship')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $relationship_types, 'IN')
      ->count()
      ->execute();

    return (bool) $entity_count;
  }

}
