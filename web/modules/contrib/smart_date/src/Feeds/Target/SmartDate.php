<?php

namespace Drupal\smart_date\Feeds\Target;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\feeds\FieldTargetDefinition;
use Drupal\feeds\Exception\EmptyFeedException;
use Drupal\feeds\Exception\TargetValidationException;
use Drupal\feeds\Plugin\Type\Target\FieldTargetBase;

/**
 * Defines a smartdate field mapper.
 *
 * @FeedsTarget(
 *   id = "smart_date_feeds_target",
 *   field_types = {"smartdate"}
 * )
 */
class SmartDate extends FieldTargetBase {

  /**
   * {@inheritdoc}
   */
  protected static function prepareTarget(FieldDefinitionInterface $field_definition) {
    return FieldTargetDefinition::createFromFieldDefinition($field_definition)
      ->addProperty('value')
      ->addProperty('end_value');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValue($delta, array &$values) {
    if (isset($values)) {
      if (isset($values['value']) && !isset($values['end_value'])) {
        $values['end_value'] = $values['value'];
      }
      return $values;
    }
    else {
      throw new EmptyFeedException();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareValues(array $values) {
    $return = [];
    foreach ($values as $delta => $columns) {
      try {
        $this->prepareValue($delta, $columns);
        $return[] = $columns;
      }
      catch (EmptyFeedException $e) {
        // Nothing wrong here.
      }
      catch (TargetValidationException $e) {
        // Validation failed.
        \Drupal::messenger()->addError($e->getMessage());
      }
    }

    return $return;
  }

}
