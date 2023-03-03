<?php

namespace Drupal\views_dates\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter to handle dates stored as a timestamp.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("views_dates_week_of_year")
 */
class WeekOfYear extends InOperator {

  /**
   * {@inheritdoc}
   */
  protected function opSimple() {
    if (empty($this->value)) {
      return;
    }
    $this->ensureMyTable();

    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;

    $placeholder = $this->placeholder().'[]';
    // PostgreSQL can use EXTRACT (WEEK FROM field), weeks start Monday, week1 incl Jan 4th (ISO 8601), result is numeric.
    // MySQL can use WEEK(field, 3), 3=>weeks start Monday, week1 incl Jan 4th (ISO 8601), result is numeric.
    // SQLite has no equivalent but strftime("%W", field) is close, result is numeric (0-53).
    $fieldexpr = $this->getDateField();
    $lhs = "WEEK({$fieldexpr}, 3)";

    $snippet = $lhs . ' IN (' . $placeholder . ')';
    // Keys are 1..12, to match %c format.
    $possibles = array_keys($this->value);

    $query->addWhereExpression(
      $this->options['group'],
      $snippet,
      [$placeholder => $possibles]
    );
  }

  /**
   * Return an array of month names, keyed by the value of the %c date format.
   *
   * If the 'options callback' is defined, use it to return values, otherwise
   * use the predefined (translatable) versions.
   *
   * Cache the return value in $this->valueOptions for speed.
   *
   * @return array|null
   *   The array of names, keyed by integer 1..12 representing January - December.
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    if (isset($this->definition['options callback']) && is_callable($this->definition['options callback'])) {
      if (isset($this->definition['options arguments']) && is_array($this->definition['options arguments'])) {
        $this->valueOptions = call_user_func_array($this->definition['options callback'], $this->definition['options arguments']);
      }
      else {
        $this->valueOptions = call_user_func($this->definition['options callback']);
      }
    }
    else {
      foreach(range(1,53) as $num) {
        $this->valueOptions[$num] = $num;
      }
    }

    return $this->valueOptions;
  }


}
