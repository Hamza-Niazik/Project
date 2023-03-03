<?php

namespace Drupal\group\Plugin\Validation\Constraint;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks the amount of times a single content entity can be added to a group.
 */
class GroupRelationshipCardinalityValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a GroupRelationshipCardinalityValidator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($group_relationship, Constraint $constraint) {
    assert($group_relationship instanceof GroupRelationshipInterface);
    assert($constraint instanceof GroupRelationshipCardinality);
    if (!isset($group_relationship)) {
      return;
    }

    // Only run our checks if a group was referenced.
    if (!$group = $group_relationship->getGroup()) {
      return;
    }

    // Only run our checks if an entity was referenced.
    if (!$entity = $group_relationship->getEntity()) {
      return;
    }

    // Get the plugin for the relationship entity.
    $plugin = $group_relationship->getPlugin();

    // Get the cardinality settings from the plugin.
    $group_cardinality = $plugin->getGroupCardinality();
    $entity_cardinality = $plugin->getEntityCardinality();

    // Exit early if both cardinalities are set to unlimited.
    if ($group_cardinality <= 0 && $entity_cardinality <= 0) {
      return;
    }

    // Get the entity_id field label for error messages.
    $field_name = $group_relationship->getFieldDefinition('entity_id')->getLabel();

    // Get the entity ID to look for, we directly use the entity_id field
    // because it reflects what's actually stored in the DB, even if we're
    // dealing with a wrapped config entity.
    $entity_id = $group_relationship->get('entity_id')->target_id;
    $data_table = $this->entityTypeManager->getDefinition('group_relationship')->getDataTable();

    // Enforce the group cardinality if it's not set to unlimited.
    if ($group_cardinality > 0) {
      // Get the groups this content entity already belongs to, not counting
      // the current group towards the limit.
      $group_count = $this->database->select($data_table, 'gc')
        ->fields('gc', ['gid'])
        ->condition('plugin_id', $group_relationship->getPluginId())
        ->condition('entity_id', $entity_id)
        ->condition('gid', $group->id(), '!=')
        ->distinct()
        ->countQuery()
        ->execute()
        ->fetchField();

      // Raise a violation if the content has reached the cardinality limit.
      if ($group_count >= $group_cardinality) {
        $this->context->buildViolation($constraint->groupMessage)
          ->setParameter('@field', $field_name)
          ->setParameter('%content', $entity->label())
          // We manually flag the entity reference field as the source of the
          // violation so form API will add a visual indicator of where the
          // validation failed.
          ->atPath('entity_id.0')
          ->addViolation();
      }
    }

    // Enforce the entity cardinality if it's not set to unlimited.
    if ($entity_cardinality > 0) {
      // We need to exclude the current relationship from the count, but only if
      // it already existed in the database.
      $relationship_id = $group_relationship->id() ?? -1;
      $entity_count = $this->database->select($data_table, 'gc')
        ->fields('gc', ['gid'])
        ->condition('plugin_id', $group_relationship->getPluginId())
        ->condition('entity_id', $entity_id)
        ->condition('gid', $group->id())
        ->condition('id', $relationship_id, '!=')
        ->distinct()
        ->countQuery()
        ->execute()
        ->fetchField();

      // Raise a violation if the content has reached the cardinality limit.
      if ($entity_count >= $entity_cardinality) {
        $this->context->buildViolation($constraint->entityMessage)
          ->setParameter('@field', $field_name)
          ->setParameter('%content', $entity->label())
          ->setParameter('%group', $group->label())
          // We manually flag the entity reference field as the source of the
          // violation so form API will add a visual indicator of where the
          // validation failed.
          ->atPath('entity_id.0')
          ->addViolation();
      }
    }
  }

}
