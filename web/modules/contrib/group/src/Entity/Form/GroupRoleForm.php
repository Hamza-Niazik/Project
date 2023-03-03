<?php

namespace Drupal\group\Entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\group\Entity\GroupRoleInterface;
use Drupal\group\PermissionScopeInterface;

/**
 * Form controller for group role forms.
 */
class GroupRoleForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    assert($this->entity instanceof GroupRoleInterface);
    $group_role = $this->entity;
    $group_role_id = '';

    $form['label'] = [
      '#title' => $this->t('Name'),
      '#type' => 'textfield',
      '#default_value' => $group_role->label(),
      '#description' => $this->t('The human-readable name of this group role. This text will be displayed on the group permissions page.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    // Since group role IDs are prefixed by the group type's ID followed by a
    // period, we need to save some space for that.
    $subtract = strlen($group_role->getGroupTypeId()) + 1;

    // Since machine names with periods in it are technically not allowed, we
    // strip the group type ID prefix when editing a group role.
    if ($group_role->id()) {
      [, $group_role_id] = explode('-', $group_role->id(), 2);
    }

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $group_role_id,
      '#maxlength' => EntityTypeInterface::ID_MAX_LENGTH - $subtract,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this group role. It must only contain lowercase letters, numbers, and underscores.'),
      '#disabled' => !$group_role->isNew(),
      '#field_prefix' => $group_role->getGroupTypeId() . '-',
    ];

    $form['weight'] = [
      '#type' => 'value',
      '#value' => $group_role->getWeight(),
    ];

    $form['scope'] = [
      '#title' => $this->t('Scope'),
      '#type' => 'radios',
      '#options' => [
        PermissionScopeInterface::OUTSIDER_ID => $this->t('Outsider: <em>Assigned to all non-members who have the corresponding global role</em>'),
        PermissionScopeInterface::INSIDER_ID => $this->t('Insider: <em>Assigned to all members who have the corresponding global role</em>'),
        PermissionScopeInterface::INDIVIDUAL_ID => $this->t('Individual: <em>Can be assigned to individual members</em>'),
      ],
      '#default_value' => $group_role->getScope() ?? PermissionScopeInterface::INDIVIDUAL_ID,
      '#required' => TRUE,
    ];

    $role_labels = [];
    foreach ($this->entityTypeManager->getStorage('user_role')->loadMultiple() as $role) {
      $role_labels[$role->id()] = $role->label();
    }
    $form['global_role'] = [
      '#title' => $this->t('Global role'),
      '#type' => 'select',
      '#options' => $role_labels,
      '#default_value' => $group_role->getGlobalRoleId(),
      '#states' => [
        'invisible' => [':input[name="scope"]' => ['value' => PermissionScopeInterface::INDIVIDUAL_ID]],
        'disabled' => [':input[name="scope"]' => ['value' => PermissionScopeInterface::INDIVIDUAL_ID]],
      ],
    ];

    $form['admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Admin role'),
      '#description' => $this->t('<strong>Warning</strong>: An admin role will always have all permissions, assign with caution.'),
      '#default_value' => $group_role->isAdmin(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save group role');
    $actions['delete']['#value'] = $this->t('Delete group role');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // '0' is invalid, since elsewhere we might check it using empty().
    $id = trim($form_state->getValue('id'));
    if ($id == '0') {
      $form_state->setErrorByName('id', $this->t('Invalid machine-readable name. Enter a name other than %invalid.', ['%invalid' => $id]));
    }

    // Config entity forms do not validate constraints by default.
    $violations = $this->entity->getTypedData()->validate();
    foreach ($violations as $violation) {
      $name = static::mapViolationPropertyPathToFormName($violation->getPropertyPath());
      $form_state->setErrorByName($name, $violation->getMessage());
    }
  }

  /**
   * Maps a violation property path to a form name.
   *
   * @param string $property_path
   *   The violation property path.
   *
   * @return string
   *   The mapped form name(s) for the violation property path.
   */
  protected static function mapViolationPropertyPathToFormName($property_path) {
    return str_replace('.', '][', $property_path);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    assert($this->entity instanceof GroupRoleInterface);
    $group_role = $this->entity;
    $group_role->set('id', $group_role->getGroupTypeId() . '-' . $group_role->id());
    $group_role->set('label', trim($group_role->label()));

    // Make sure the global_role property is NULL rather than FALSE.
    if ($group_role->getScope() === PermissionScopeInterface::INDIVIDUAL_ID) {
      $group_role->set('global_role', NULL);
    }

    $status = $group_role->save();
    $t_args = ['%label' => $group_role->label()];

    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('The group role %label has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('The group role %label has been added.', $t_args));

      $context = array_merge($t_args, ['link' => $group_role->toLink($this->t('View'), 'collection')->toString()]);
      $this->logger('group')->notice('Added group role %label.', $context);
    }

    $form_state->setRedirectUrl($group_role->toUrl('collection'));
  }

  /**
   * Checks whether a group role ID exists already.
   *
   * @param string $id
   *
   * @return bool
   *   Whether the ID is taken.
   */
  public function exists($id) {
    assert($this->entity instanceof GroupRoleInterface);
    $group_role = $this->entity;
    return (boolean) $this->entityTypeManager->getStorage('group_role')->load($group_role->getGroupTypeId() . '-' .$id);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    if ($route_match->getRawParameter($entity_type_id) !== NULL) {
      return $route_match->getParameter($entity_type_id);
    }

    // If we are on the create form, we can't extract an entity from the route,
    // so we need to create one based on the route parameters.
    $values = [];
    if ($route_match->getRawParameter('group_type') !== NULL) {
      $values['group_type'] = $route_match->getRawParameter('group_type');
    }
    return $this->entityTypeManager->getStorage($entity_type_id)->create($values);
  }

}
