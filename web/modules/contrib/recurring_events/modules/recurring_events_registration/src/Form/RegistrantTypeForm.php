<?php

namespace Drupal\recurring_events_registration\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for editing a registant type.
 */
class RegistrantTypeForm extends EntityForm {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs a RegistrantTypeForm object.
   *
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   */
  public function __construct(Messenger $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $registrant_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 32,
      '#default_value' => $registrant_type->label(),
      '#description' => $this->t("Label for the registrant type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $registrant_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\recurring_events_registration\Entity\RegistrantType::load',
      ],
      '#disabled' => !$registrant_type->isNew(),
    ];

    $form['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textarea',
      '#default_value' => $registrant_type->getDescription(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $registrant_type = $this->entity;
    $status = $registrant_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label registrant type.', [
          '%label' => $registrant_type->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label registrant type.', [
          '%label' => $registrant_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($registrant_type->toUrl('collection'));
  }

}
