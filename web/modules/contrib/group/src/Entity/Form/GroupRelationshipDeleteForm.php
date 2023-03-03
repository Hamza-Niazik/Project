<?php

namespace Drupal\group\Entity\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupRelationshipInterface;

/**
 * Provides a form for deleting a relationship entity.
 */
class GroupRelationshipDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * Returns the plugin responsible for this relationship.
   *
   * @return \Drupal\group\Plugin\Group\Relation\GroupRelationInterface
   *   The responsible group relation.
   */
  protected function getPlugin() {
    $group_relationship = $this->getEntity();
    assert($group_relationship instanceof GroupRelationshipInterface);
    return $group_relationship->getPlugin();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelURL() {
    $group_relationship = $this->getEntity();
    assert($group_relationship instanceof GroupRelationshipInterface);
    $group = $group_relationship->getGroup();
    $route_params = [
      'group' => $group->id(),
      'group_relationship' => $group_relationship->id(),
    ];
    return new Url('entity.group_relationship.canonical', $route_params);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $group_relationship = $this->getEntity();
    assert($group_relationship instanceof GroupRelationshipInterface);
    $group = $group_relationship->getGroup();
    $group_relationship->delete();

    \Drupal::logger('group_relationship')->notice('@type: deleted %title.', [
      '@type' => $group_relationship->bundle(),
      '%title' => $group_relationship->label(),
    ]);

    $form_state->setRedirect('entity.group.canonical', ['group' => $group->id()]);
  }

}
