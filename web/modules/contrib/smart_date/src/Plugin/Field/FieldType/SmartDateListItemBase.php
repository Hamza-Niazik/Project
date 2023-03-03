<?php

namespace Drupal\smart_date\Plugin\Field\FieldType;

use Drupal\options\Plugin\Field\FieldType\ListItemBase;

/**
 * Abstract clss meant to expose parse and related functions for lists.
 */
abstract class SmartDateListItemBase extends ListItemBase {

  /**
   * {@inheritdoc}
   */
  public static function parseValues($values) {
    // Use the ListItemBase parsing function, but don't allow generated keys.
    $result = static::extractAllowedValues($values, 1);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected static function validateAllowedValue($option) {
    // Verify that the duration option is either custom or an integer.
    if (($option != 'custom') && !preg_match('/^-?\\d+$/', $option)) {
      return t('Allowed values list: keys must be integers or "custom".');
    }
  }

}
