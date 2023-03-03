<?php

namespace Drupal\smart_date_recur;

/**
 * Provides friendly methods for smart date range.
 */
trait SmartDateRecurTrait {

  /**
   * Helper function to massage an array for inclusion in output.
   */
  protected function massageForOutput($output, array $settings, $add_classes = NULL) {
    if (!$add_classes) {
      $add_classes = $this->getSetting('add_classes');
    }
    if ($settings['date_first']) {
      // Time should be first so reverse the array.
      ksort($output);
    }
    $temp_array['start'] = $output;
    if ($add_classes) {
      $this->addRangeClasses($temp_array);
    }
    return $temp_array['start'];
  }

  /**
   * Helper function to create a collapsed display of events within a day.
   */
  protected function formatWithinDay(array $instances, array $settings) {
    $settings_notime = $this->settingsFormatNoTime($settings);
    $settings_nodate = $this->settingsFormatNoDate($settings);
    $settings_notz = $this->settingsFormatNoTz($settings_nodate);
    $output = [];
    foreach ($instances as $time_set) {
      $this_output = [];
      $time_output = [];
      $last_time = array_pop($time_set);
      foreach ($time_set as $key => $instance) {
        $time_output[$key] = static::formatSmartDate($instance->value, $instance->end_value, $settings_notz, $instance->timezone);
        $time_output[$key]['#suffix'] = ', ';
      }
      $time_output[] = static::formatSmartDate($last_time->value, $last_time->end_value, $settings_nodate, $last_time->timezone);
      $this_output['time'] = $time_output;
      $this_output['join'] = ['#markup' => $settings['join']];
      $this_output['date']['#markup'] = static::formatSmartDate($last_time->value, $last_time->value, $settings_notime, $last_time->timezone, 'string');
      $this_output['#attributes']['class'] = ['smart-date--daily-times'];
      $this_output['#type'] = 'container';
      $output[] = $this->massageForOutput($this_output, $settings);
    }
    return $output;
  }

}
