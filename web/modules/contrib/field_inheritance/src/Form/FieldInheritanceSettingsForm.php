<?php

namespace Drupal\field_inheritance\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Provides a form for configuring field inheritance settings.
 *
 * @ingroup field_inheritance
 */
class FieldInheritanceSettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'field_inheritance_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['field_inheritance.config'];
  }

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * Construct an FieldInheritanceForm.
   *
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(Messenger $messenger, EntityTypeManager $entity_type_manager, EntityTypeBundleInfo $entity_type_bundle_info) {
    $this->messenger = $messenger;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('field_inheritance.config')
      ->set('included_entities', implode(',', array_filter($form_state->getValue('included_entities'))))
      ->set('included_bundles', implode(',', array_filter($form_state->getValue('included_bundles'))))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Define the form used for Field Inheritance settings.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('field_inheritance.config');

    $entity_types = $this->entityTypeManager->getDefinitions();
    $entity_types = array_keys(array_filter($entity_types, function ($type) {
      return $type->entityClassImplements(FieldableEntityInterface::CLASS);
    }));
    $entity_types = array_combine($entity_types, $entity_types);

    $form['included_entities'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Included Entity Types'),
      '#description' => $this->t('Select the entity types that should be able to inherit data'),
      '#options' => $entity_types,
      '#default_value' => explode(',', $config->get('included_entities')),
    ];

    $entity_bundles = [];
    foreach ($entity_types as $entity_type) {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
      foreach (array_keys($bundles) as $bundle) {
        $entity_bundles[] = $entity_type . ':' . $bundle;
      }
    }
    $entity_bundles = array_combine($entity_bundles, $entity_bundles);

    $form['included_bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Included Entity Bundles'),
      '#description' => $this->t('Select the entity bundles that should be able to inherit data'),
      '#options' => $entity_bundles,
      '#default_value' => explode(',', $config->get('included_bundles')),
    ];

    return parent::buildForm($form, $form_state);
  }

}
