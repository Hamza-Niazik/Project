<?php

namespace Drupal\field_inheritance\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\field_inheritance\FieldInheritancePluginManager;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Provides a form for managing field inheritance entities.
 */
class FieldInheritanceForm extends EntityForm {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

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
   * The field inheritance plugin manager.
   *
   * @var \Drupal\field_inheritance\FieldInheritancePluginManager
   */
  protected $fieldInheritance;

  /**
   * Construct an FieldInheritanceForm.
   *
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entity_type_bundle_info
   *   The entity type bundle info service.
   * @param \Drupal\field_inheritance\FieldInheritancePluginManager $field_inheritance
   *   The field inheritance plugin manager.
   */
  public function __construct(Messenger $messenger, EntityFieldManager $entity_field_manager, EntityTypeManager $entity_type_manager, EntityTypeBundleInfo $entity_type_bundle_info, FieldInheritancePluginManager $field_inheritance) {
    $this->messenger = $messenger;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->fieldInheritance = $field_inheritance;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('plugin.manager.field_inheritance')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $field_inheritance = $this->entity;

    // This form needs AJAX support.
    $form['#attached']['library'][] = 'core/jquery.form';
    $form['#attached']['library'][] = 'core/drupal.ajax';

    $form['#prefix'] = '<div id="field-inheritance-add-form--wrapper">';
    $form['#suffix'] = '</div>';

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $field_inheritance->label(),
      '#description' => $this->t("Label for the Field inheritance."),
      '#required' => TRUE,
    ];

    $machine_name_prefix = '';
    if ($field_inheritance->isNew()) {
      if (!empty($this->entity->destination_entity_type) && !empty($this->entity->destination_entity_bundle)) {
        $machine_name_prefix = $this->entity->destination_entity_type . '_' . $this->entity->destination_entity_bundle . '_';
      }
      else {
        $machine_name_prefix = '[entity-type]_[bundle]_';
      }
    }

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $field_inheritance->id(),
      '#field_prefix' => $machine_name_prefix,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#disabled' => !$field_inheritance->isNew(),
    ];

    $help = [
      $this->t('<b>Inherit</b> - Pull field data directly from the source.'),
      $this->t('<b>Prepend</b> - Place destination data above source data.'),
      $this->t('<b>Append</b> - Place destination data below source data.'),
      $this->t('<b>Fallback</b> - Show destination data, if set, otherwise show source data.'),
    ];

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Inheritance Strategy'),
      '#description' => $this->t('Select the method/strategy used to inherit data.'),
      '#options' => [
        'inherit' => $this->t('Inherit'),
        'prepend' => $this->t('Prepend'),
        'append' => $this->t('Append'),
        'fallback' => $this->t('Fallback'),
      ],
      '#required' => TRUE,
      '#default_value' => $field_inheritance->type() ?? 'inherit',
    ];
    $form['information'] = [
      '#type' => 'markup',
      '#prefix' => '<p>',
      '#markup' => implode('</p><p>', $help),
      '#suffix' => '</p>',
    ];

    $entity_types = $this->entityTypeManager->getDefinitions();
    $entity_types = array_keys(array_filter($entity_types, function ($type) {
      return $type->entityClassImplements(FieldableEntityInterface::CLASS);
    }));
    $entity_types = array_combine($entity_types, $entity_types);

    $source_entity_bundles = $destination_entity_bundles = [];
    $source_entity_fields = $destination_entity_fields = [];

    $default_values = ['' => $this->t('-- Select --')];

    $field_values = [];
    $form_values = $form_state->getValues();
    if (!$field_inheritance->isNew()) {
      $field_values['source_entity_type'] = $field_inheritance->sourceEntityType();
      $field_values['source_entity_bundle'] = $field_inheritance->sourceEntityBundle();
      $field_values['source_field'] = $field_inheritance->sourceField();
      $field_values['destination_entity_type'] = $field_inheritance->destinationEntityType();
      $field_values['destination_entity_bundle'] = $field_inheritance->destinationEntityBundle();
    }
    elseif (!empty($form_values)) {
      $field_values['source_entity_type'] = $form_values['source_entity_type'];
      $field_values['source_entity_bundle'] = $form_values['source_entity_bundle'];
      $field_values['source_field'] = $form_values['source_field'];
      $field_values['destination_entity_type'] = $form_values['destination_entity_type'];
      $field_values['destination_entity_bundle'] = $form_values['destination_entity_bundle'];
    }

    if (!empty($field_inheritance->destinationEntityType()) && empty($form_values)) {
      $field_values['destination_entity_type'] = $field_inheritance->destinationEntityType();
    }

    if (!empty($field_inheritance->destinationEntityBundle()) && empty($form_values)) {
      $field_values['destination_entity_bundle'] = $field_inheritance->destinationEntityBundle();
    }

    if (!empty($form_values['source_field'])) {
      $field_values['source_field'] = $form_values['source_field'];
    }

    if (!empty($field_values['source_entity_type'])) {
      $source_entity_bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo($field_values['source_entity_type']));
      $source_entity_bundles = array_combine($source_entity_bundles, $source_entity_bundles);
      if (!empty($field_values['source_entity_bundle'])) {
        $source_entity_fields = array_keys($this->entityFieldManager->getFieldDefinitions($field_values['source_entity_type'], $field_values['source_entity_bundle']));
        $source_entity_fields = $default_values + array_combine($source_entity_fields, $source_entity_fields);
      }
    }

    if (!empty($field_values['destination_entity_type'])) {
      $destination_entity_bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo($field_values['destination_entity_type']));
      $destination_entity_bundles = array_combine($destination_entity_bundles, $destination_entity_bundles);
      if (!empty($field_values['destination_entity_bundle'])) {
        $destination_entity_fields = array_keys($this->entityFieldManager->getFieldDefinitions($field_values['destination_entity_type'], $field_values['destination_entity_bundle']));
        $destination_entity_fields = $default_values + array_combine($destination_entity_fields, $destination_entity_fields);

        // You should never be able to use the inherited field as part of an
        // inheritance as that creates an infinite loop.
        if (!empty($field_inheritance->id() && !empty($destination_entity_fields[$field_inheritance->idWithoutTypeAndBundle()]))) {
          unset($destination_entity_fields[$field_inheritance->idWithoutTypeAndBundle()]);
        }
      }
    }

    $form['source'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Source of Data'),
    ];

    $form['source']['source_entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Source Entity Type'),
      '#description' => $this->t('Select the source entity type from which to inherit data.'),
      '#options' => $entity_types,
      '#required' => TRUE,
      '#default_value' => $field_inheritance->sourceEntityType(),
      '#ajax' => [
        'callback' => '::updateFieldOptions',
        'wrapper' => 'field-inheritance-add-form--wrapper',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Fetching source options...'),
        ],
      ],
    ];

    $form['source']['source_entity_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Source Entity Bundle'),
      '#description' => $this->t('Select the source entity bundle from which to inherit data.'),
      '#options' => $source_entity_bundles,
      '#required' => TRUE,
      '#default_value' => $field_inheritance->sourceEntityBundle(),
      '#ajax' => [
        'callback' => '::updateFieldOptions',
        'wrapper' => 'field-inheritance-add-form--wrapper',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Fetching source options...'),
        ],
      ],
      '#states' => [
        'visible' => [
          'select[name="source_entity_type"]' => ['!value' => ''],
        ],
      ],
    ];

    $form['source']['source_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Source Field'),
      '#description' => $this->t('Select the field on the source entity from which to inherit data.'),
      '#options' => $source_entity_fields,
      '#required' => TRUE,
      '#default_value' => $field_inheritance->sourceField(),
      '#ajax' => [
        'callback' => '::updateFieldOptions',
        'wrapper' => 'field-inheritance-add-form--wrapper',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Updating plugin options...'),
        ],
      ],
      '#states' => [
        'visible' => [
          'select[name="source_entity_type"]' => ['!value' => ''],
          'select[name="source_entity_bundle"]' => ['!value' => ''],
        ],
      ],
    ];

    $form['destination'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Destination of Data'),
    ];

    $form['destination']['destination_entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination Entity Type'),
      '#description' => $this->t('Select the destination entity type to which to inherit data.'),
      '#options' => $entity_types,
      '#required' => TRUE,
      '#default_value' => $field_inheritance->destinationEntityType(),
      '#ajax' => [
        'callback' => '::updateFieldOptions',
        'wrapper' => 'field-inheritance-add-form--wrapper',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Fetching destination options...'),
        ],
      ],
    ];

    $form['destination']['destination_entity_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination Entity Bundle'),
      '#description' => $this->t('Select the destination entity bundle to which to inherit data.'),
      '#options' => $destination_entity_bundles,
      '#required' => TRUE,
      '#default_value' => $field_inheritance->destinationEntityBundle(),
      '#ajax' => [
        'callback' => '::updateFieldOptions',
        'wrapper' => 'field-inheritance-add-form--wrapper',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Fetching destination options...'),
        ],
      ],
      '#states' => [
        'visible' => [
          'select[name="destination_entity_type"]' => ['!value' => ''],
        ],
      ],
    ];

    $form['destination']['destination_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination Field'),
      '#description' => $this->t('(Optionally) Select the field on the destination entity to use during inheritance.'),
      '#options' => $destination_entity_fields,
      '#default_value' => $field_inheritance->destinationField(),
      '#states' => [
        'visible' => [
          'select[name="type"]' => ['!value' => 'inherit'],
          'select[name="destination_entity_type"]' => ['!value' => ''],
          'select[name="destination_entity_bundle"]' => ['!value' => ''],
        ],
        'required' => [
          'select[name="type"]' => ['!value' => 'inherit'],
          'select[name="destination_entity_type"]' => ['!value' => ''],
          'select[name="destination_entity_bundle"]' => ['!value' => ''],
        ],
      ],
    ];

    $plugins = [];
    foreach ($this->fieldInheritance->getDefinitions() as $plugin_id => $plugin) {
      $plugins[$plugin_id] = $plugin['name']->__toString();
    }

    $global_plugins = [];
    $prefered_plugin = '';

    // If a source field is set, then hide plugins not applicable to that field
    // type.
    if (!empty($field_values['source_field'])) {
      $source_definitions = $this->entityFieldManager->getFieldDefinitions($field_values['source_entity_type'], $field_values['source_entity_bundle']);
      foreach ($plugins as $key => $plugin) {
        if ($key === '') {
          continue;
        }
        $plugin_definition = $this->fieldInheritance->getDefinition($key);
        $field_types = $plugin_definition['types'];
        if (!in_array('any', $field_types)) {
          $prefered_plugin = $key;
          if (!in_array($source_definitions[$field_values['source_field']]->getType(), $field_types)) {
            unset($plugins[$key]);
          }
        }
        // Global plugins should not take precedent over more specific plugins.
        if (in_array('any', $field_types)) {
          if (empty($prefered_plugin)) {
            $prefered_plugin = $key;
          }
          $global_plugins[$key] = $plugins[$key];
          unset($plugins[$key]);
        }
      }

      // If we have some global plugins, place them at the end of the list.
      if (!empty($global_plugins)) {
        $plugins = array_merge($plugins, $global_plugins);
      }

      $default_plugins = ['' => $this->t('- Select -')];
      if (empty($plugins)) {
        $plugins = $default_plugins;
      }

      $form['advanced'] = [
        '#type' => 'details',
        '#title' => $this->t('Advanced'),
        '#open' => TRUE,
      ];

      $form['advanced']['plugin'] = [
        '#type' => 'select',
        '#title' => $this->t('Inheritance Plugin'),
        '#description' => $this->t('Select the plugin used to perform the inheritance.'),
        '#options' => $plugins,
        '#required' => TRUE,
        '#default_value' => $field_inheritance->isNew() ? $prefered_plugin : ($field_inheritance->plugin() ?? $prefered_plugin),
      ];
    }


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();

    if (!empty($values['source_entity_type'])
      && !empty($values['destination_entity_type'])
      && !empty($values['source_entity_bundle'])
      && !empty($values['destination_entity_bundle'])) {
      if (!empty($values['source_field']) && !empty($values['destination_field'])) {
        $source_definitions = $this->entityFieldManager->getFieldDefinitions($values['source_entity_type'], $values['source_entity_bundle']);
        $destination_definitions = $this->entityFieldManager->getFieldDefinitions($values['destination_entity_type'], $values['destination_entity_bundle']);

        if ($source_definitions[$values['source_field']]->getType() !== $destination_definitions[$values['destination_field']]->getType()) {
          $message = $this->t('Source and destination field definition types must be the same to inherit data. Source - @source_name type: @source_type. Destination - @destination_name type: @destination_type', [
            '@source_name' => $values['source_field'],
            '@source_type' => $source_definitions[$values['source_field']]->getType(),
            '@destination_name' => $values['destination_field'],
            '@destination_type' => $destination_definitions[$values['destination_field']]->getType(),
          ]);
          $form_state->setErrorByName('source_field', $message);
          $form_state->setErrorByName('destination_field', $message);
        }

        $plugin_definition = $this->fieldInheritance->getDefinition($values['plugin']);
        $field_types = $plugin_definition['types'];

        if (!in_array('any', $field_types) && !in_array($source_definitions[$values['source_field']]->getType(), $field_types)) {
          $message = $this->t('The selected plugin @plugin does not support @source_type fields. The supported field types are: @field_types', [
            '@plugin' => $values['plugin'],
            '@source_type' => $source_definitions[$values['source_field']]->getType(),
            '@field_types' => implode(',', $field_types),
          ]);
          $form_state->setErrorByName('source_field', $message);
          $form_state->setErrorByName('plugin', $message);
        }

        if ($values['source_entity_type'] == $values['destination_entity_type'] && $values['source_entity_bundle'] == $values['destination_entity_bundle']) {
          $message = $this->t('You cannot inherit if the source and destination entities and bundles are the same.');
          $form_state->setErrorByName('source_entity_bundle', $message);
          $form_state->setErrorByName('destination_entity_bundle', $message);
        }

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $field_inheritance = $this->entity;
    $field_inheritance->setSourceEntityType($values['source_entity_type']);
    $field_inheritance->setSourceEntityBundle($values['source_entity_bundle']);
    $field_inheritance->setSourceField($values['source_field']);
    $field_inheritance->setDestinationEntityType($values['destination_entity_type']);
    $field_inheritance->setDestinationEntityBundle($values['destination_entity_bundle']);
    $field_inheritance->setDestinationField($values['destination_field']);
    $status = $field_inheritance->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger->addMessage($this->t('Created the %label field inheritance.', [
          '%label' => $field_inheritance->label(),
        ]));
        break;

      default:
        $this->messenger->addMessage($this->t('Saved the %label field inheritance.', [
          '%label' => $field_inheritance->label(),
        ]));
    }
    $this->entityFieldManager->clearCachedFieldDefinitions();
    $form_state->setRedirectUrl($field_inheritance->toUrl('collection'));
  }

  /**
   * AJAX Callback: Update Field Options.
   */
  public function updateFieldOptions(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#field-inheritance-add-form--wrapper', $form));
    return $response;
  }

  /**
   * Determines if the field inheritance already exists.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   *
   * @return bool
   *   TRUE if the display mode exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element) {
    if (!empty($this->entity->destination_entity_type) && !empty($this->entity->destination_entity_bundle)) {
      $id = $this->entity->destination_entity_type . '_' . $this->entity->destination_entity_bundle . '_' . $entity_id;
      $return = (bool) $this->entityTypeManager
        ->getStorage($this->entity->getEntityTypeId())
        ->getQuery()
        ->condition('id', $id)
        ->execute();
      return $return;
    }
  }

}
