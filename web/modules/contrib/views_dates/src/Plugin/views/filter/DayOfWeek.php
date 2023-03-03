<?php

namespace Drupal\views_dates\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

    /**
     * Filter to handle dates stored as a timestamp.
     *
     * @ingroup views_filter_handlers
     *
     * @ViewsFilter("views_dates_day_of_week")
     */
    class DayOfWeek extends InOperator {

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
        $fieldexpr = $this->getDateField();
        // For PostgreSQL use EXTRACT (DOW FROM field), result is numeric.
        // For SQLite use strftime("%w", field), result is numeric.
        // BUT : How to tell if these are needed!!
        $lhs = "DATE_FORMAT({$fieldexpr}, '%w')";
        $snippet = $lhs . ' IN (' . $placeholder . ')';
        // Keys are 0..6, to match %w format.
        $possibles = array_keys($this->value);

        $query->addWhereExpression(
          $this->options['group'],
          $snippet,
          [$placeholder => $possibles]
        );
      }

      /**
       * Return an array of day names, keyed by the value of the %w date format.
       *
       * If the 'options callback' is defined, use it to return values, otherwise
       * use the predefined (translatable) versions.
       *
       * Cache the return value in $this->valueOptions for speed.
       *
       * @return array|null
       *   The array of names, keyed by integer 0..6 representing Sunday - Saturday.
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
          // Keys are 0..6 and starting Sunday, to match %w format.
          $this->valueOptions = [
            '0' => $this->t('Sunday'),
            '1' => $this->t('Monday'),
            '2' => $this->t('Tuesday'),
            '3' => $this->t('Wednesday'),
            '4' => $this->t('Thursday'),
            '5' => $this->t('Friday'),
            '6' => $this->t('Saturday'),
          ];
        }

        return $this->valueOptions;
      }
    }
