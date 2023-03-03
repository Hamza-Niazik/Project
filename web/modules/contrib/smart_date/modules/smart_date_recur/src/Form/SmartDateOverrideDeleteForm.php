<?php

namespace Drupal\smart_date_recur\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\smart_date_recur\Controller\Instances;

/**
 * Provides a deletion confirmation form for Smart Date Overrides.
 */
class SmartDateOverrideDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this
      ->t('Are you sure you want to revert this instance to its default values?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $rrule = $this->entity->rrule->getString();
    return new Url('smart_date_recur.instances', ['rrule' => (int) $rrule]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this
      ->t('Revert to Default');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Delete override entity, if it exists.
    $this->entity
      ->delete();
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $rrid = $this->entity->rrule->getString();
    /** @var \Drupal\smart_date_recur\Entity\SmartDateRule $rrule */
    $rrule = $entityTypeManager->getStorage('smart_date_rule')->load($rrid);
    $instanceController = new Instances();
    // Force refresh of parent entity.
    $instanceController->applyChanges($rrule);
    // Output message about operation performed.
    $this->messenger()->addMessage($this->t('The instance has been reverted to default.'));
    $form_state
      ->setRedirectUrl($this
        ->getCancelUrl());
  }

}
