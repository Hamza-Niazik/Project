<?php

namespace Drupal\views_dates\Plugin\views\filter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Filter to handle dates stored as a timestamp.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("views_dates_date_generic")
 */
class GenericDate extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $identifier = $this->options['field'];
    if ($value['value'] = \Drupal::request()->query->get($identifier)) {
      $this->value = $this->convertGenericDateValue($value);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['operator']['default'] = 'between';
    $options['value']['default'] = 'CCYYMM_current';
    $options['value']['contains']['value']['default'] = 'CCYYMM_current';

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultExposeOptions() {
    parent::defaultExposeOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = [
      'between' => [
        'title' => $this->t('Is within'),
        'method' => 'opBetween',
        'short' => $this->t('within'),
        'values' => 1,
      ],
      'not between' => [
        'title' => $this->t('Is not within'),
        'method' => 'opBetween',
        'short' => $this->t('not within'),
        'values' => 1,
      ],
    ];

    // if the definition allows for the empty operator, add it.
    if (!empty($this->definition['allow empty'])) {
      $operators += [
        'empty' => [
          'title' => $this->t('Is empty (NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('empty'),
          'values' => 0,
        ],
        'not empty' => [
          'title' => $this->t('Is not empty (NOT NULL)'),
          'method' => 'opEmpty',
          'short' => $this->t('not empty'),
          'values' => 0,
        ],
      ];
    }

    return $operators;
  }

  /**
   * Provide a list of all the operators
   */
  public function operatorOptions($which = 'title') {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      $options[$id] = $info[$which];
    }

    return $options;
  }

  /**
   * Provide a list of operators depending the number of values.
   */
  protected function operatorValues($values = 1) {
    $options = [];
    foreach ($this->operators() as $id => $info) {
      if ($info['values'] == $values) {
        $options[] = $id;
      }
    }

    return $options;
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

    $options = $this->operatorOptions('short');
    $output = $options[$this->operator];
    if (isset($this->value['value']) && !empty($this->value['value'])) {
      $this->value = $this->convertGenericDateValue($this->value);
    }

    if (in_array($this->operator, $this->operatorValues(1))) {
      $output .= ' ';
      if (isset($this->value['arg_type']) && isset($this->value['arg_value']) && $this->value['arg_value'] == 'current') {
        switch ($this->value['arg_type']) {
          case 'CCYYMMDD':
            $output .= t('current day') . ', ';
            break;
          case 'CCYYWW':
            $output .= t('current week') . ', ';
            break;
          case 'CCYYMM':
            $output .= t('current month') . ', ';
            break;
          case 'CCYY':
            $output .= t('current year') . ', ';
            break;
        }
      }
      $output .= date($this->value['display_format'], $this->value['min']);
    }
    elseif (in_array($this->operator, $this->operatorValues(0))) {
      $output .= ' ' . $this->value['value'];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    $form['expose']['use_operator']['#access'] = FALSE;
    $form['expose']['operator_id']['#access'] = FALSE;
    $form['expose']['multiple']['#access'] = FALSE;
  }

  /**
   * Provide a simple textfield for equality
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {

    $which = 'all';

    if ($exposed = $form_state->get('exposed')) {
      $identifier = $this->options['expose']['identifier'];
      $which = in_array($this->operator, $this->operatorValues(2)) ? 'minmax' : 'value';

      if (empty($this->options['expose']['use_operator']) || empty($this->options['expose']['operator_id'])) {
        // exposed and locked.
        $which = in_array($this->operator, $this->operatorValues(2)) ? 'minmax' : 'value';
      }
      else {
        $source = ':input[name="' . $this->options['expose']['operator_id'] . '"]';
      }
    }

    $user_input = $form_state->getUserInput();
    $source = ':input[name="options[operator]"]';
    $identifier = $this->options['field'];
    if ($which == 'all') {
      $form['value']['value'] = [
        '#type' => 'textfield',
        '#title' => !$exposed ? $this->t('Value') : '',
        '#size' => 30,
        '#default_value' => $this->value['value'],
      ];
      if (!empty($this->options['expose']['placeholder'])) {
        $form['value']['value']['#attributes']['placeholder'] = $this->options['expose']['placeholder'];
      }
      // Setup #states for all operators with one value.
      foreach ($this->operatorValues(1) as $operator) {
        $form['value']['value']['#states']['visible'][] = [
          $source => ['value' => $operator],
        ];
      }
      if ($exposed && !isset($user_input[$identifier]['value'])) {
        $user_input[$identifier]['value'] = $this->value['value'];
        $form_state->setUserInput($user_input);
      }
    }
    elseif ($which == 'value') {
      // When exposed we drop the value-value and just do value if
      // the operator is locked.
      $form[$identifier] = [
        '#type' => 'hidden',
        '#value' => $this->value['value'],
      ];
      if ($exposed && !isset($user_input[$identifier])) {
        $user_input[$identifier] = $this->value['value'];
        $form_state->setUserInput($user_input);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    if (!empty($this->options['exposed']) && $form_state->isValueEmpty(['options', 'expose', 'required'])) {
      // Who cares what the value is if it's exposed and non-required.
      return;
    }

    $value = $this->convertGenericDateValue($form_state->getValue(['options', 'value', 'value']));
    $this->validateGenericDateValue($value);
    $this->value = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function validateExposed(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    if (empty($this->options['expose']['required'])) {
      // Who cares what the value is if it's exposed and non-required.
      return;
    }

    $this->validateGenericDateValue($this->value);
  }

  /**
   * {@inheritdoc}
   */
  protected function hasValidGroupedValue(array $group) {
    if (!is_array($group['value']) || empty($group['value'])) {
      return FALSE;
    }

    // Special case when validating grouped date filters because the
    // $group['value'] array contains the type of filter (date or offset) and
    // therefore the number of items the comparison has to be done against is
    // one greater.
    $operators = $this->operators();
    $expected = $operators[$group['operator']]['values'] + 1;
    $actual = count(array_filter($group['value'], 'static::arrayFilterZero'));

    return $actual == $expected;
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    if (empty($this->options['exposed'])) {
      return TRUE;
    }

    if (!isset($this->value['value'])) {
      $this->value = $this->convertGenericDateValue(['value' => reset($this->value)]);
    }

    $input[$this->options['expose']['identifier']] = $this->value['value'];
    return parent::acceptExposedInput($input);
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    if (empty($this->options['exposed'])) {
      return;
    }

    // Build the form and set the value based on the identifier.
    if (!empty($this->options['expose']['identifier'])) {
      // We need to apply url parameters for the case if we can get referer's path and query params.
      // Basically it just can help to refresh the view with these parameters via ajax.
      $url_path = isset($this->view->display_handler->options['exposed_form']['options']['referer_path']) ? $this->view->display_handler->options['exposed_form']['options']['referer_path'] : \Drupal::request()->getPathInfo();
      $url_query_args = isset($this->view->display_handler->options['exposed_form']['options']['referer_query_array']) ? $this->view->display_handler->options['exposed_form']['options']['referer_query_array'] : \Drupal::request()->query->all();

      $identifier = $this->options['expose']['identifier'];

      // Set up value if it's coming from external sources.
      if (isset($url_query_args[$identifier])) {
        $this->value['value'] = $url_query_args[$identifier];
      }

      // Convert value.
      $this->value = $this->convertGenericDateValue($this->value);
      // Prepare possible dates.
      $dates = $this->prepareAvailableDates();

      $this->valueForm($form, $form_state);
      $identifier_filter = $identifier . '_filter';

      // Generate the form.
      $form[$identifier_filter] = [
        '#type' => 'container',
        '#prefix' => '<div class="views-dates--generic-date-filter">',
        '#suffix' => '</div>',
        '#attached' => [
          'library' => ['views_dates/views_dates.module'],
        ],
      ];

      // Render years.
      $form[$identifier_filter]['years'] = [
        '#type' => 'container',
      ];

      $years_output = '<strong>' . t('Years') . ':&nbsp;&nbsp;&nbsp;</strong>';
      $form[$identifier_filter]['years']['output'] = [
        '#type' => 'markup',
        '#markup' => $years_output,
      ];

      foreach ($dates['years'] as $year => $exist) {
        if ($exist) {
          $year_query_args = $url_query_args;
          unset($year_query_args['CCYY']);
          unset($year_query_args['CCYYMM']);
          unset($year_query_args['CCYYMMDD']);
          unset($year_query_args['CCYYWW']);
          $year_query_args[$identifier] = 'CCYY_'.$year;
          $year_url = Url::fromUserInput($url_path, ['query' => $year_query_args]);

          $form[$identifier_filter]['years'][$year] = [
            '#type' => 'link',
            '#title' => $year,
            //          '#attributes' => ['class' => ['button']],
            '#url' => $year_url,
            '#suffix' => '&nbsp;&nbsp;&nbsp;',
          ];
          if ($this->value['arg_type'] == 'CCYY' && isset($this->value['date']['year']) && $this->value['date']['year'] == $year) {
            $form[$identifier_filter]['years'][$year]['#attributes']['class'][] = 'active';
          }
        }
        else {
          $form[$identifier_filter]['years'][$year] = [
            '#type' => 'markup',
            '#markup' => $year,
            '#suffix' => '&nbsp;&nbsp;&nbsp;',
          ];
        }
      }

      // Render months.
      $form[$identifier_filter]['months'] = [
        '#type' => 'container',
      ];

      $months_output = '<strong>' . t('Months') . ':&nbsp;&nbsp;&nbsp;</strong>';
      $form[$identifier_filter]['months']['output'] = [
        '#type' => 'markup',
        '#markup' => $months_output,
      ];

      foreach ($dates['months'][$this->value['date']['year']] as $month => $exist) {
        if ($exist) {
          $month_query_args = $url_query_args;
          unset($month_query_args['CCYY']);
          unset($month_query_args['CCYYMM']);
          unset($month_query_args['CCYYMMDD']);
          unset($month_query_args['CCYYWW']);
          $month_query_args[$identifier] = 'CCYYMM_'.$this->value['date']['year'] . date('m', mktime(0, 0, 0, $month, 1));
          $month_url = Url::fromUserInput($url_path, ['query' => $month_query_args]);

          $form[$identifier_filter]['months'][$month] = [
            '#type' => 'link',
            '#title' => strtoupper(date('M', mktime(0, 0, 0, $month, 1))),
            '#url' => $month_url,
            '#suffix' => '&nbsp;&nbsp;&nbsp;',
          ];
          if ($this->value['arg_type'] == 'CCYYMM' && isset($this->value['date']['month']) && $this->value['date']['month'] == $month) {
            $form[$identifier_filter]['months'][$month]['#attributes']['class'][] = 'active';
          }
        }
        else {
          $form[$identifier_filter]['months'][$month] = [
            '#type' => 'markup',
            '#markup' => strtoupper(date('M', mktime(0, 0, 0, $month, 1))),
            '#suffix' => '&nbsp;&nbsp;&nbsp;',
          ];
        }
      }

      // Render days.
      if ($this->value['date']['month']) {
        $form[$identifier_filter]['days'] = [
          '#type' => 'container',
        ];

        $days_output = '<strong>' . t('Days') . ':&nbsp;&nbsp;&nbsp;</strong>';
        $form[$identifier_filter]['days']['output'] = [
          '#type' => 'markup',
          '#markup' => $days_output,
        ];

        foreach ($dates['days'][$this->value['date']['year']][$this->value['date']['month']] as $day => $exist) {
          if ($exist) {
            $day_query_args = $url_query_args;
            unset($day_query_args['CCYY']);
            unset($day_query_args['CCYYMM']);
            unset($day_query_args['CCYYMMDD']);
            unset($day_query_args['CCYYWW']);
            $day_query_args[$identifier] = 'CCYYMMDD_'.$this->value['date']['year'] . date('m', mktime(0, 0, 0, $this->value['date']['month'], 1)) . date('d', mktime(0, 0, 0, $this->value['date']['month'], $day));
            $day_url = Url::fromUserInput($url_path, ['query' => $day_query_args]);

            $form[$identifier_filter]['days'][$day] = [
              '#type' => 'link',
              '#title' => date('d', mktime(0, 0, 0, $this->value['date']['month'], $day)),
              '#url' => $day_url,
              '#suffix' => '&nbsp;&nbsp;&nbsp;',
            ];
            if ($this->value['arg_type'] == 'CCYYMMDD' && isset($this->value['date']['day']) && $this->value['date']['day'] == $day) {
              $form[$identifier_filter]['days'][$day]['#attributes']['class'][] = 'active';
            }
          }
          else {
            $form[$identifier_filter]['days'][$day] = [
              '#type' => 'markup',
              '#markup' => date('d', mktime(0, 0, 0, $this->value['date']['month'], $day)),
              '#suffix' => '&nbsp;&nbsp;&nbsp;',
            ];
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $field = "$this->tableAlias.$this->realField";

    if (!isset($this->value['value'])) {
      $this->value = ['value' => reset($this->value)];
    }
    $this->value = $this->convertGenericDateValue($this->value);

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }

  /**
   * Implements 'between' operator.
   * @param $field
   */
  protected function opBetween($field) {
    $a = intval($this->value['min'], 0);
    $b = intval($this->value['max'], 0);

    // This is safe because we are manually scrubbing the values.
    // It is necessary to do it this way because $a and $b are formulas when using an offset.
    $operator = strtoupper($this->operator);
    $this->query->addWhereExpression($this->options['group'], "$field $operator $a AND $b");
  }

  /**
   * Converts argument to normal values.
   */
  public function convertGenericDateValue($val) {
    $value['value'] = $val['value'];

    list($value['arg_type'], $value['arg_value']) = explode('_', $value['value']);
    if (!$value['arg_type']) {
      return $val;
    }

    switch ($value['arg_type']) {
      case 'CCYYMMDD':
        $value['display_format'] = 'F j, Y';
        $value['arg_format'] = 'Ymd';
        if ($value['arg_value'] == 'current') {
          $year = date('Y');
          $month = date('m');
          $day = date('d');
        }
        else {
          $year = substr($value['arg_value'], 0, 4);
          $month = substr($value['arg_value'], 4, 2);
          $day = substr($value['arg_value'], 6, 2);
        }
        $date_min = new DrupalDateTime($year . '-' . $month . '-' . $day . ' ' . '00:00:00', 'UTC');
        $date_max = new DrupalDateTime($year . '-' . $month . '-' . $day . ' ' . '23:59:59', 'UTC');
        $value['date']['year'] = (int) $year;
        $value['date']['month'] = (int) $month;
        $value['date']['day'] = (int) $day;
        $value['week']['year'] = 0;
        break;

      case 'CCYYMM':
        $value['display_format'] = 'F, Y';
        $value['arg_format'] = 'Ym';
        if ($value['arg_value'] == 'current') {
          $year = date('Y');
          $month = date('m');
        }
        else {
          $year = substr($value['arg_value'], 0, 4);
          $month = substr($value['arg_value'], 4, 2);
        }
        $date_min = new DrupalDateTime($year . '-' . $month . '-01 ' . '00:00:00', 'UTC');
        $date_max = new DrupalDateTime($year . '-' . $month . '-' . cal_days_in_month(CAL_GREGORIAN, $month, $year) . ' ' . '23:59:59', 'UTC');
        $value['date']['year'] = (int) $year;
        $value['date']['month'] = (int) $month;
        $value['date']['day'] = 0;
        $value['week']['year'] = 0;
        break;

//      case 'CCYYWW':
//        $this->format = 'W, Y';
//        $this->argFormat = 'YW';
//        if ($value['arg_value'] == 'current') {
//          $year = date('Y');
//          $week = date('W');
//        }
//        else {
//          $year = substr($value['arg_value'], 0, 4);
//          $week = substr($value['arg_value'], 4, 2);
//        }
//        DrupalDateTime::createFromFormat('Y-W', '2018-42');
//        $date_min = new DrupalDateTime($year . '-' . $month . '-01 ' . '00:00:00', 'UTC');
//        $date_max = new DrupalDateTime($year . '-' . $month . '-' . cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')) . ' ' . '23:59:59', 'UTC');
//        $value['date']['year'] = $year;
//        $value['date']['month'] = 0;
//        $value['date']['day'] = 0;
//        $value['week']['year'] = (int) $week;
//        break;

      case 'CCYY':
        $value['display_format'] = 'Y';
        $value['arg_format'] = 'Y';
        if ($value['arg_value'] == 'current') {
          $year = date('Y');
        }
        else {
          $year = substr($value['arg_value'], 0, 4);
        }
        $date_min = new DrupalDateTime($year . '-01-01 00:00:00', 'UTC');
        $date_max = new DrupalDateTime($year . '-12-31 23:59:59', 'UTC');
        $value['date']['year'] = (int) $year;
        $value['date']['month'] = 0;
        $value['date']['day'] = 0;
        $value['week']['year'] = 0;
        break;

      default:
        return FALSE;

    }
    $value['min'] = $date_min->getTimestamp();
    $value['max'] = $date_max->getTimestamp();

    return $value;
  }

  /**
   * Validates generic date value.
   */
  public function validateGenericDateValue($value) {
    return TRUE;
  }

  /**
   * Prepares an array of available date chunks.
   */
  public function prepareAvailableDates() {
    $dates = [];

    // Add dates for the current month.
    $current_year = date('Y');
    $current_month = date('m');
    $current_days = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
    $dates['years'][$current_year] = 0;
    for ($month = 1; $month <= 12; $month++) {
      $dates['months'][$current_year][$month] = 0;
    }
    for ($current_day = 1; $current_day <= $current_days; $current_day++) {
      $dates['days'][$current_year][$current_month][$current_day] = 0;
    }

    // Add dates for requested year and month.
    if (isset($this->value['date']['year']) && $this->value['date']['year']) {
      $dates['years'][$this->value['date']['year']] = 0;
      for ($month = 1; $month <= 12; $month++) {
        $dates['months'][$this->value['date']['year']][$month] = 0;
      }
      if (isset($this->value['date']['month']) && $this->value['date']['month']) {
        $current_days = cal_days_in_month(CAL_GREGORIAN, $this->value['date']['month'], $this->value['date']['year']);
        for ($current_day = 1; $current_day <= $current_days; $current_day++) {
          $dates['days'][$this->value['date']['year']][$this->value['date']['month']][$current_day] = 0;
        }
      }
    }

    // Load the view.
    $view = Views::getView($this->view->id());
    if (is_object($view)) {
      // Get display ID for future usage.
      $display_id = $this->view->current_display;
      // Set up contextual arguments if any.
      $view->setArguments($this->view->args);
      // Set up display.
      $view->setDisplay($display_id);
      // Remove the current filter handler to get all possible not filtered results.
      $view->removeHandler($display_id, 'filter', $this->options['expose']['identifier']);
      // Prepare and build the view.
      $view->preExecute();
      $view->build($display_id);
      // Get the SQL query.
      $query = $view->build_info['query'];
      // Remove range if any.
      $query->range(NULL, NULL);
      // Add that field what we're using for filtering.
      $query->addField($this->table, $this->realField);
      // Calculate that field index.
      $fields = $query->getFields();
      $field_index = array_search($this->realField, array_keys($fields));
      $result = $query->execute();

      // Retrieve all possible dates and sort them.
      $array_dates = $result->fetchCol($field_index);
      if (!empty($array_dates)) {
        asort($array_dates);

        // Find all years.
        $year_start = date('Y', reset($array_dates));
        $year_end = date('Y', end($array_dates));
        for ($year = $year_start; $year <= $year_end; $year++) {
          $year_start_datetime = new DrupalDateTime($year . '-01-01 00:00:00', 'UTC');
          $year_end_datetime = new DrupalDateTime($year . '-12-31 23:59:59', 'UTC');
          $year_start_timestamp = $year_start_datetime->getTimestamp();
          $year_end_timestamp = $year_end_datetime->getTimestamp();
          $year_dates = array_filter($array_dates,
            function ($value) use($year_start_timestamp, $year_end_timestamp) {
              return ($value >= $year_start_timestamp && $value <= $year_end_timestamp);
            }
          );
          if (!empty($year_dates)) {
            $dates['years'][$year] = 1;
          }
          else {
            $dates['years'][$year] = 0;
          }
        }

        // Find all months for the each existing year.
        foreach ($dates['years'] as $year => $exist) {
          if ($exist) {
            for ($month = 1; $month <= 12; $month++) {
              $month_start_datetime = new DrupalDateTime($year . '-' . $month . '-01 00:00:00', 'UTC');
              $month_end_datetime = new DrupalDateTime($year . '-' . $month . '-31 23:59:59', 'UTC');
              $month_start_timestamp = $month_start_datetime->getTimestamp();
              $month_end_timestamp = $month_end_datetime->getTimestamp();
              $month_dates = array_filter($array_dates,
                function ($value) use($month_start_timestamp, $month_end_timestamp) {
                  return ($value >= $month_start_timestamp && $value <= $month_end_timestamp);
                }
              );
              if (!empty($month_dates)) {
                $dates['months'][$year][$month] = 1;
              }
              else {
                $dates['months'][$year][$month] = 0;
              }
            }
          }
        }

        // Find all weeks for the each existing year.
        foreach ($dates['years'] as $year => $exist) {
          if ($exist) {
            $weeks_date = new DrupalDateTime;
            $weeks_date->setISODate($year, 53);
            $weeks = (int) $weeks_date->format('W') === 53 ? 53 : 52;
            for ($week = 1; $week <= $weeks; $week++) {
              $year_start_datetime = new DrupalDateTime($year . '-01-01 00:00:00', 'UTC');
              $year_end_datetime = new DrupalDateTime($year . '-12-31 23:59:59', 'UTC');
              $year_start_timestamp = $year_start_datetime->getTimestamp();
              $year_end_timestamp = $year_end_datetime->getTimestamp();
              $week_start_datetime = new DrupalDateTime();
              $week_start_datetime->setISODate($year, $week);
              $week_start_datetime->setTime(0, 0, 0);
              $week_start_datetime->setTimezone(new \DateTimeZone('UTC'));
              $week_end_datetime = new DrupalDateTime();
              $week_end_datetime->setISODate($year, $week, 7);
              $week_end_datetime->setTime(23, 59, 59);
              $week_end_datetime->setTimezone(new \DateTimeZone('UTC'));
              $week_start_timestamp = $week_start_datetime->getTimestamp();
              $week_end_timestamp = $week_end_datetime->getTimestamp();
              if ($week == 1) {
                $week_start_timestamp = $week_start_timestamp < $year_start_timestamp ? $year_start_timestamp : $week_start_timestamp;
              }
              if ($week == 52 || $week == 53) {
                $week_end_timestamp = $week_end_timestamp < $year_end_timestamp ? $year_end_timestamp : $week_end_timestamp;
              }
              $week_dates = array_filter($array_dates,
                function ($value) use($week_start_timestamp, $week_end_timestamp) {
                  return ($value >= $week_start_timestamp && $value <= $week_end_timestamp);
                }
              );
              if (!empty($week_dates)) {
                $dates['weeks'][$year][$week] = 1;
              }
              else {
                $dates['weeks'][$year][$week] = 0;
              }
            }
          }
        }

        // Find all days for the each existing month.
        foreach ($dates['months'] as $year => $months) {
          foreach ($months as $month => $exist) {
            if ($exist) {
              $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
              for ($day = 1; $day <= $days; $day++) {
                $day_start_datetime = new DrupalDateTime($year . '-' . $month . '-' . $day . ' 00:00:00', 'UTC');
                $day_end_datetime = new DrupalDateTime($year . '-' . $month . '-' . $day . ' 23:59:59', 'UTC');
                $day_start_timestamp = $day_start_datetime->getTimestamp();
                $day_end_timestamp = $day_end_datetime->getTimestamp();
                $day_dates = array_filter($array_dates,
                  function ($value) use($day_start_timestamp, $day_end_timestamp) {
                    return ($value >= $day_start_timestamp && $value <= $day_end_timestamp);
                  }
                );
                if (!empty($day_dates)) {
                  $dates['days'][$year][$month][$day] = 1;
                }
                else {
                  $dates['days'][$year][$month][$day] = 0;
                }
              }
            }
          }
        }

      }

    }

    return $dates;
  }

}
