<?php

namespace Drupal\recurring_events\Plugin\FullcalendarViewProcessor;

use Drupal\fullcalendar_view\Plugin\FullcalendarViewProcessorBase;
use Drupal\recurring_events\Entity\EventInstance;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Recurring Events Full Calendar View Processor plugin.
 *
 * @FullcalendarViewProcessor(
 *   id = "fullcalendar_view_recurring_events",
 *   label = @Translation("Recurring Events Full Calendar View Processor"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class RecurringEventsFullCalendarProcessor extends FullcalendarViewProcessorBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function process(array &$variables) {
    /** @var \Drupal\views\ViewExecutable $view */
    $view = $variables['view'];
    $options = $view->style_plugin->options;
    $fields = $view->field;

    $view_index = key($variables['#attached']['drupalSettings']['fullCalendarView']);
    $calendar_options = json_decode($variables['#attached']['drupalSettings']['fullCalendarView'][$view_index]['calendar_options'], TRUE);

    // We only want to process eventinstances.
    if (empty($calendar_options) || $view->getBaseEntityType()->id() !== 'eventinstance') {
      return;
    }

    // Color for bundle types.
    $color_content = $options['color_bundle'];
    // Color for taxonomies.
    $color_tax = $options['color_taxonomies'];
    // Taxonomy field name.
    $tax_field = $options['tax_field'];

    $entries = $calendar_options['events'];

    foreach ($view->result as $key => $row) {
      $current_entity = $row->_entity;

      // Set the row_index property used by advancedRender function.
      $view->row_index = $row->index;

      // Render all fields to so they can be used in rewrite.
      foreach ($fields as $field) {
        if (method_exists($field, 'advancedRender')) {
          // Set the row_index property used by advancedRender function.
          $field->view->row_index = $row->index;
          $field->advancedRender($row);
        }
      }

      // Event title.
      if (empty($options['title']) || $options['title'] == 'title') {
        $title = $fields['title']->advancedRender($row);
      }
      elseif (!empty($fields[$options['title']])) {
        $title = $fields[$options['title']]->advancedRender($row);
      }
      else {
        $title = $this->t('Invalid event title');
      }

      $entries[$key]['title'] = $title;
      $entries[$key]['eid'] = $current_entity->id();
      $entries[$key]['url'] = $current_entity->toUrl('canonical')->toString();
      $entries[$key]['start'] = $current_entity->date->start_date->format(DATE_ATOM);
      $entries[$key]['end'] = $current_entity->date->end_date->format(DATE_ATOM);
      $entries[$key]['duration'] = $this->getDuration($current_entity);
      $entries[$key]['allDay'] = $this->isDateAllDay($current_entity);
      $entries[$key]['eventDurationEditable'] = FALSE;
      $entries[$key]['editable'] = FALSE;

      $event_type = NULL;
      if (!empty($tax_field) && $current_entity->hasField($tax_field)) {
        // Event type.
        $event_type = $current_entity->get($tax_field)->target_id;
      }

      $entity_bundle = $current_entity->bundle();

      if (isset($event_type) && isset($color_tax[$event_type])) {
        $entries[$key]['backgroundColor'] = $color_tax[$event_type];
      }
      elseif (isset($color_content[$entity_bundle])) {
        $entries[$key]['backgroundColor'] = $color_content[$entity_bundle];
      }
    }

    // Update the entries.
    if ($entries) {
      $calendar_options['events'] = $entries;
      $variables['#attached']['drupalSettings']['fullCalendarView'][$view_index]['calendar_options'] = json_encode($calendar_options);
    }
  }

  /**
   * Calculate if the date is all day.
   *
   * @var Drupal\recurring_events\Entity\EventInstance $event_instance
   *   The event instance entity.
   *
   * @return bool
   *   TRUE if all-day; FALSE otherwise.
   */
  private function isDateAllDay(EventInstance $event_instance) {
    $all_day = FALSE;
    $config = \Drupal::config('recurring_events.eventseries.config');
    $min = $config->get('min_time');
    $max = $config->get('max_time');

    $start = $event_instance->date->start_date;
    $end = $event_instance->date->end_date;

    if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
      if ($start->format('h:ia') == $min && $end->format('h:ia') == $max) {
        $all_day = TRUE;
      }
    }
    return $all_day;
  }

  /**
   * Calculate the events duration.
   *
   * @var Drupal\recurring_events\Entity\EventInstance $event_instance
   *   The event instance entity.
   *
   * @return int
   *   The duration of the event.
   */
  private function getDuration(EventInstance $event_instance) {
    $start = $event_instance->date->start_date;
    $end = $event_instance->date->end_date;
    $interval = $start->diff($end);
    // Return diff in seconds.
    return $interval->s;
  }

}
