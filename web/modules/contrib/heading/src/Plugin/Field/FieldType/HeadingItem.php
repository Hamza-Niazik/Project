<?php

namespace Drupal\heading\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a field type "heading" containing a heading type and text.
 *
 * @FieldType(
 *   id = "heading",
 *   label = @Translation("Heading"),
 *   description = @Translation("Adds a text field with customizable heading type."),
 *   category = @Translation("Text"),
 *   module = "heading",
 *   default_formatter = "heading",
 *   default_widget = "heading",
 *   column_groups = {
 *     "size" = {
 *       "label" = @Translation("Size"),
 *       "translatable" = FALSE
 *     },
 *     "text" = {
 *       "label" = @Translation("Text"),
 *       "translatable" = TRUE
 *     },
 *   },
 * )
 */
class HeadingItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'size' => [
          'type' => 'char',
          'length' => 2,
          'not null' => FALSE,
          'description' => 'The heading size (h1-h6).',
        ],
        'text' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
          'description' => 'The text within the heading.',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['size'] = DataDefinition::create('string');
    $properties['text'] = DataDefinition::create('string');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $size = $this->get('size')->getValue();
    $text = $this->get('text')->getValue();
    return empty($size) && empty($text);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = [
      'label' => 'Heading',
      'allowed_sizes' => ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
    ];
    return $settings + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $default_settings = self::defaultFieldSettings();

    $default_label_settings = $this->getSetting('label');
    $default_label = !empty($default_label_settings)
      ? $default_label_settings
      : $default_settings['label'];
    $element['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Set the form label for the text field of the heading.'),
      '#default_value' => $default_label,
    ];

    $allowed_sizes_settings = $this->getSetting('allowed_sizes');
    $default_allowed_sizes = is_array($allowed_sizes_settings) && !empty($allowed_sizes_settings)
      ? $allowed_sizes_settings
      : $default_settings['allowed_sizes'];
    $element['allowed_sizes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed sizes'),
      '#description' => $this->t('Limit the allowed heading sizes.'),
      '#options' => $this->getSizes(),
      '#default_value' => $default_allowed_sizes,
    ];

    return $element;
  }

  /**
   * Get all possible sizes.
   *
   * @return array
   *   The heading size labels keyed by their size (h1-h6).
   */
  protected function getSizes() {
    return [
      'h1' => $this->t('Heading 1'),
      'h2' => $this->t('Heading 2'),
      'h3' => $this->t('Heading 3'),
      'h4' => $this->t('Heading 4'),
      'h5' => $this->t('Heading 5'),
      'h6' => $this->t('Heading 6'),
    ];
  }

}
