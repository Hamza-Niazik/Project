<?php

namespace Drupal\group\Entity\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupRelationshipTypeInterface;

/**
 * Provides a form for relationship type deletion.
 *
 * Instead of just deleting the relationship type here, we use this form as a
 * mean of uninstalling a group relation which will actually trigger the
 * deletion of the relationship type.
 */
class GroupRelationshipTypeDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $relationship_type = $this->getEntity();
    assert($relationship_type instanceof GroupRelationshipTypeInterface);
    return $this->t('Are you sure you want to uninstall the %plugin plugin?', [
      '%plugin' => $relationship_type->getPlugin()->getRelationType()->getLabel(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $relationship_type = $this->getEntity();
    assert($relationship_type instanceof GroupRelationshipTypeInterface);
    return Url::fromRoute('entity.group_type.content_plugins', ['group_type' => $relationship_type->getGroupTypeId()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $relationship_type = $this->getEntity();
    assert($relationship_type instanceof GroupRelationshipTypeInterface);
    $entity_type_id = $relationship_type->getPlugin()->getRelationType()->getEntityTypeId();
    $replace = [
      '%entity_type' => $this->entityTypeManager->getDefinition($entity_type_id)->getLabel(),
      '%group_type' => $relationship_type->getGroupType()->label(),
    ];
    return $this->t('You will no longer be able to add %entity_type entities to %group_type groups.', $replace);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Uninstall');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_count = $this->entityTypeManager->getStorage('group_relationship')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();

    if (!empty($entity_count)) {
      $form['#title'] = $this->getQuestion();
      $form['description'] = [
        '#markup' => '<p>' . $this->t('You can not uninstall this content plugin until you have removed all of the content that uses it.') . '</p>'
      ];

      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $relationship_type = $this->getEntity();
    assert($relationship_type instanceof GroupRelationshipTypeInterface);
    $group_type = $relationship_type->getGroupType();
    $group_relation_type = $relationship_type->getPlugin()->getRelationType();

    $relationship_type->delete();
    \Drupal::logger('group_relationship_type')->notice('Uninstalled %plugin from %group_type.', [
      '%plugin' => $group_relation_type->getLabel(),
      '%group_type' => $group_type->label(),
    ]);

    $form_state->setRedirect('entity.group_type.content_plugins', ['group_type' => $group_type->id()]);
    $this->messenger()->addStatus($this->t('The content plugin was uninstalled from the group type.'));
  }

}
