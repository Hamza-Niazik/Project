<?php

namespace Drupal\heading\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'heading' formatter.
 *
 * @FieldFormatter(
 *   id = "heading",
 *   label = @Translation("Heading"),
 *   field_types = {
 *     "heading"
 *   }
 * )
 */
class HeadingFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Heading');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      // Do not print empty headings.
      if (empty($item->text)) {
        continue;
      }

      $element[$delta] = [
        '#theme' => 'heading',
        '#size' => $item->size,
        '#text' => $item->text,
      ];
    }

    return $element;
  }

}
