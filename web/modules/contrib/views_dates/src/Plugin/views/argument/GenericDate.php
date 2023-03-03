<?php

namespace Drupal\views_dates\Plugin\views\argument;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\argument\Date;

/**
 * Argument handler for different date formats:
 *  - CCYYMMDD
 *  - CCYYMM
 *  - CCYYWW
 *  - CCYY
 *
 * @ViewsArgument("views_dates_date_generic")
 */
class GenericDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $format = 'F j, Y';

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'Ymd';

  /**
   * Argument type.
   */
  protected $argumentType = NULL;

  /**
   * {@inheritdoc}
   */
  public function defaultArgumentForm(&$form, FormStateInterface $form_state) {
    parent::defaultArgumentForm($form, $form_state);
    $form['default_argument_type']['#options'] += ['views_dates_generic_date' => $this->t('Generic date as query parameter')];
  }

  /**
   * Set the input for this argument
   *
   * @param $arg
   *
   * @return TRUE if it successfully validates; FALSE if it does not.
   */
  public function setArgument($arg) {
    list($this->argumentType, $this->argument) = explode('_', $arg);
    if (!$this->argumentType) {
      return FALSE;
    }

    switch ($this->argumentType) {
      case 'CCYYMMDD':
        $this->format = 'F j, Y';
        $this->argFormat = 'Ymd';
        break;

      case 'CCYYMM':
        $this->format = 'j, Y';
        $this->argFormat = 'Ym';
        break;

      case 'CCYYWW':
        $this->format = 'W, Y';
        $this->argFormat = 'YW';
        break;

      case 'CCYY':
        $this->format = 'Y';
        $this->argFormat = 'Y';
        break;

      default:
        return FALSE;

    }

    return $this->validateArgument($arg);
  }

}
