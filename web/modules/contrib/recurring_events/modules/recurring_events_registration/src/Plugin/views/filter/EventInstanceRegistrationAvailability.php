<?php

namespace Drupal\recurring_events_registration\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter handler to show the availability of registrations for event instances.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("eventinstance_registration_availability")
 */
class EventInstanceRegistrationAvailability extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function canBuildGroup() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed');
    }
    return $this->options['value'];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['available'] = [
      'default' => 'available',
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }
    $this->valueOptions = [
      'available' => $this->t('Spaces Available'),
      'full' => $this->t('Event Full'),
    ];
    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $default_value = (array) $this->value;

    $exposed = $form_state->get('exposed');
    if ($exposed) {
      if (empty($default_value)) {
        $keys = array_keys($this->getValueOptions());
        $default_value = array_shift($keys);
      }
      else {
        $copy = $default_value;
        $default_value = array_shift($copy);
      }
    }

    if (!$this->isExposed()) {
      $form['value'] = [
        '#title' => $this->t('Availability.'),
        '#type' => 'select',
        '#options' => $this->getValueOptions(),
        '#default_value' => $default_value,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);

    $default_value = (array) $this->value;

    $exposed = $form_state->get('exposed');
    if ($exposed) {
      if (empty($default_value)) {
        $keys = array_keys($this->getValueOptions());
        $default_value = array_shift($keys);
      }
      else {
        $copy = $default_value;
        $default_value = array_shift($copy);
      }
    }

    if ($this->isExposed()) {
      $form['value'] = [
        '#title' => $this->t('Availability.'),
        '#type' => 'select',
        '#options' => $this->getValueOptions(),
        '#default_value' => $default_value,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Set -1 as the default value so that if no events match the checks, then
    // we should get no results, rather than all results.
    $items = ['-1'];
    $table = $this->ensureMyTable();

    // Grab the current view being executed.
    $view = clone $this->query->view;
    $filters = $view->filter;
    // Remove any instances of this filter from the filters.
    if (!empty($filters)) {
      foreach ($filters as $key => $filter) {
        if ($filter instanceof EventInstanceRegistrationAvailability) {
          unset($view->filter[$key]);
        }
      }
    }
    // Execute the current view with the filters removed, so we can reduce the
    // number of event instances we need to examine to find their availability.
    // This makes the query more efficient and avoids having to do messy union
    // selects across multiple tables to determine the availability of an event.
    $view->preExecute();
    $view->execute();

    $available = $this->value;
    if (is_array($available)) {
      $available = reset($this->value);
    }

    if (!empty($view->result)) {
      foreach ($view->result as $key => $result) {
        $availability = $result->_entity->get('availability_count')->getValue()[0]['value'] ?? -1;

        switch ($available) {
          // Filtering for available events means unlimited availability of an
          // availability greater than zero.
          case 'available':
            if ($availability === -1 || $availability > 0) {
              $items[] = $result->_entity->id();
            }
            break;

          // Filtering for full events means an event with exactly zero
          // availability.
          case 'full':
            if ($availability == 0) {
              $items[] = $result->_entity->id();
            }
            break;
        }
      }
    }

    // Filter this view by the events which match the availability above.
    $items = implode(',', $items);
    $this->query->addWhereExpression($this->options['group'], "$table.id IN (" . $items . ")");
  }

}
