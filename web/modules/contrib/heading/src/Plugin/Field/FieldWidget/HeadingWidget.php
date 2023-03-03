<?php

namespace Drupal\heading\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Heading widget for the heading field type.
 *
 * @FieldWidget(
 *   id = "heading",
 *   label = @Translation("Heading"),
 *   field_types = {
 *     "heading"
 *   }
 * )
 */
class HeadingWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['#attached']['library'][] = 'heading/widget';

    $class = ['heading-widget--container'];
    if (count($this->getTypes()) > 1) {
      $class[] = 'heading-widget--container-with-size';
    }

    $element['container'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => $class,
      ],
    ];

    $element['container']['text'] = [
      '#type' => 'textfield',
      '#title' => $this->fieldDefinition->getLabel(),
      '#default_value' => $items[$delta]->text,
      '#required' => $element['#required'],
    ];

    $element['container']['size'] = $this->formElementSize($items, $delta);

    return $element;
  }

  /**
   * Create the size form element.
   *
   * The heading size select will be hidden if there is only one heading size
   * allowed.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items.
   * @param int $delta
   *   The current field delta.
   *
   * @return array
   *   Form element render array.
   */
  protected function formElementSize(FieldItemListInterface $items, $delta) {
    $size_options = $this->getTypes();
    if (count($size_options) === 1) {
      reset($size_options);
      return [
        '#type' => 'value',
        '#value' => key($size_options),
      ];
    }

    $size_options_keys = array_keys($size_options);
    $size_default = isset($items[$delta]->size)
      ? $items[$delta]->size
      : reset($size_options_keys);

    return [
      '#type' => 'select',
      '#title' => $this->t('Size'),
      '#default_value' => $size_default,
      '#options' => $size_options,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $data) {
      $values[$delta]['text'] = $data['container']['text'];
      $values[$delta]['size'] = $data['container']['size'];
      unset($values[$delta]['container']);
    }

    return $values;
  }

  /**
   * Get the available heading types.
   *
   * @return array
   *   The heading size labels keyed by their size (h1-h6).
   */
  protected function getTypes() {
    $allowed_sizes = array_filter($this->fieldDefinition->getSetting('allowed_sizes'));
    $sizes = [
      'h1' => $this->t('Heading 1'),
      'h2' => $this->t('Heading 2'),
      'h3' => $this->t('Heading 3'),
      'h4' => $this->t('Heading 4'),
      'h5' => $this->t('Heading 5'),
      'h6' => $this->t('Heading 6'),
    ];

    return array_intersect_key($sizes, $allowed_sizes);
  }

}
