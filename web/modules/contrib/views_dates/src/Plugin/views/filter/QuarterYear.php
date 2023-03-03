<?php

namespace Drupal\views_dates\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter to handle dates stored as a timestamp.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("views_dates_quarter_of_year")
 */
class QuarterYear extends InOperator {

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
    // PostgreSQL can use EXTRACT (QUARTER FROM field) result is numeric.
    // MySQL can use WEEK(field, 3) result is numeric.
    // SQLite has no equivalent.
    $fieldexpr = $this->getDateField();
    $lhs = "QUARTER({$fieldexpr})";

    $snippet = $lhs . ' IN (' . $placeholder . ')';
    // Keys are 1..4.
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
      $this->valueOptions = [
        '1' => $this->t('Q1'),
        '2' => $this->t('Q2'),
        '3' => $this->t('Q3'),
        '4' => $this->t('Q4'),
      ];
    }

    return $this->valueOptions;
  }


}
