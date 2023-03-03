<?php

namespace Drupal\group\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupRoleInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks the validity of a group role's scope.
 */
class GroupRoleScopeValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a GroupRoleScopeValidator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($group_role, Constraint $constraint) {
    assert($group_role instanceof GroupRoleInterface);
    assert($constraint instanceof GroupRoleScope);
    if (!isset($group_role)) {
      return;
    }

    $scope = $group_role->getScope();
    if ($scope !== PermissionScopeInterface::INDIVIDUAL_ID) {
      $global_role = $group_role->getGlobalRoleId();

      // Anonymous users cannot be members, so avoid this weird scenario.
      if ($scope === PermissionScopeInterface::INSIDER_ID && $global_role === RoleInterface::ANONYMOUS_ID) {
        $role_label = $this->entityTypeManager->getStorage('user_role')->load($global_role)->label();
        $this->context->buildViolation($constraint->anonymousMemberMessage)
          ->setParameter('%role', $role_label)
          ->atPath('global_role')
          ->addViolation();
      }

      $existing_pairs = $this->entityTypeManager
        ->getStorage('group_role')
        ->getQuery()
        ->condition('group_type', $group_role->getGroupTypeId())
        ->condition('scope', $scope)
        ->condition('global_role', $global_role)
        ->execute();

      if (!empty($existing_pairs)) {
        // If we found ourselves, we can ignore this.
        if (!$group_role->isNew() && count($existing_pairs) === 1 && reset($existing_pairs) == $group_role->id()) {
          return;
        }

        $role_label = $this->entityTypeManager->getStorage('user_role')->load($global_role)->label();
        $this->context->buildViolation($constraint->duplicateScopePairMessage)
          ->setParameter('%group_type', $group_role->getGroupType()->label())
          ->setParameter('@scope', $scope)
          ->setParameter('%role', $role_label)
          ->atPath('global_role')
          ->addViolation();
      }
    }
  }

}
