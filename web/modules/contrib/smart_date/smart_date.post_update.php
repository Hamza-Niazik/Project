<?php

/**
 * @file
 * Post-update functions for Smart Date module.
 */

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Clear caches to ensure schema changes are read.
 */
function smart_date_post_update_translatable_separator() {
  // Empty post-update hook to cause a cache rebuild.
}

/**
 * Migrate smartdate_default field formatter settings to smartdate_custom.
 */
function smart_date_post_update_translatable_config() {

  // Loop through all configured entity view displays, and compile information
  // about the smartdate_default field settings.
  $displays = EntityViewDisplay::loadMultiple();
  foreach ($displays as $display) {
    if ($display instanceof EntityViewDisplay) {
      $components = $display->getComponents();
      foreach ($components as $fieldName => $component) {
        if (isset($component['type'])
          && $component['type'] === 'smartdate_default'
          && isset($component['settings'])
        ) {
          // Keep the settings the same but change it to the custom display.
          $component['type'] = 'smartdate_custom';
          $display->setComponent($fieldName, $component);
          $display->save();
        }
      }
    }
  }
  // Now ensure defaults are imported.
  // If there are already smart date format entities then nothing is needed.
  $storage = \Drupal::entityTypeManager()->getStorage('smart_date_format');
  $existing = $storage->loadMultiple();
  if ($existing) {
    return;
  }

  // Obtain configuration from yaml files.
  $config_path = \Drupal::service('extension.list.module')->getPath('smart_date') . '/config/install/';
  $source      = new FileStorage($config_path);

  // Load the provided default entities.
  $storage->create($source->read('smart_date.smart_date_format.compact'))
    ->save();
  $storage->create($source->read('smart_date.smart_date_format.date_only'))
    ->save();
  $storage->create($source->read('smart_date.smart_date_format.default'))
    ->save();
  $storage->create($source->read('smart_date.smart_date_format.time_only'))
    ->save();
}
