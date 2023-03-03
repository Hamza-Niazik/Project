<?php

namespace Drupal\recurring_events\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the eventinstance entity clone form.
 *
 * @ingroup recurring_events
 */
class EventInstanceCloneForm extends EventInstanceForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->entity = $this->entity->createDuplicate();
    return parent::buildForm($form, $form_state);
  }

}
