<?php

namespace Drupal\heading\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin to format string & text fields as a heading.
 *
 * @FieldFormatter(
 *   id = "heading_text",
 *   label = @Translation("Heading"),
 *   field_types = {
 *     "string",
 *     "text"
 *   }
 * )
 */
class TextFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['size' => 'h2'] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['size'] = [
      '#type' => 'select',
      '#title' => $this->t('Size'),
      '#default_value' => $this->getSetting('size'),
      '#options' => [
        'h1' => $this->t('Heading 1'),
        'h2' => $this->t('Heading 2'),
        'h3' => $this->t('Heading 3'),
        'h4' => $this->t('Heading 4'),
        'h5' => $this->t('Heading 5'),
        'h6' => $this->t('Heading 6'),
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t(
      'Heading (@size)',
      ['@size' => strtoupper($this->getSetting('size'))]
    );

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      // Do not print empty headings.
      if (empty($item->value)) {
        continue;
      }

      $element[$delta] = [
        '#theme' => 'heading',
        '#size' => $this->getSetting('size'),
        '#text' => $item->value,
      ];
    }

    return $element;
  }

}
