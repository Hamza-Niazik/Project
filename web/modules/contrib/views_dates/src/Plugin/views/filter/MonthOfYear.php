<?php

namespace Drupal\views_dates\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter to handle dates stored as a timestamp.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("views_dates_month_of_year")
 */
class MonthOfYear extends InOperator {

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
    // PostgreSQL can use EXTRACT (MONTH FROM field), result is numeric.
    // Alt MySQL can use EXTRACT (MONTH FROM field), result is numeric.
    // SQLite can use strftime("%c", field), result is numeric.
    $fieldexpr = $this->getDateField();
    $lhs = "DATE_FORMAT({$fieldexpr}, '%c')";

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
      $this->valueOptions = [
        '1' => $this->t('January'),
        '2' => $this->t('February'),
        '3' => $this->t('March'),
        '4' => $this->t('April'),
        '5' => $this->t('May'),
        '6' => $this->t('June'),
        '7' => $this->t('July'),
        '8' => $this->t('August'),
        '9' => $this->t('September'),
        '10' => $this->t('October'),
        '11' => $this->t('November'),
        '12' => $this->t('December'),
      ];
    }

    return $this->valueOptions;
  }


}
