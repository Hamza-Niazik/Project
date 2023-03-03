<?php

namespace Drupal\field_inheritance\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormState;

/**
 * The FieldInheritanceController class.
 */
class FieldInheritanceController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Gets the creation form in a modal.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $entity_bundle
   *   The entity bundle.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Returns an ajax response.
   */
  public function ajaxCreationForm($entity_type = NULL, $entity_bundle = NULL) {
    $inheritance_entity = $this->entityTypeManager()->getStorage('field_inheritance')->create();
    $inheritance_entity->setDestinationEntityType($entity_type);
    $inheritance_entity->setDestinationEntityBundle($entity_bundle);

    $form_object = $this->entityTypeManager()->getFormObject('field_inheritance', 'ajax');
    $form_object->setEntity($inheritance_entity);
    $form_state = (new FormState())
      ->setFormObject($form_object)
      ->disableRedirect();

    $modal_form = $this->formBuilder()->buildForm($form_object, $form_state);
    $modal_form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $response = new AjaxResponse();
    $response->addCommand(new OpenModalDialogCommand('Add Field Inheritance', $modal_form, ['width' => '800']));
    return $response;
  }

}
