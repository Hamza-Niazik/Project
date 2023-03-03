<?php

namespace Drupal\smart_date\TypedData\Plugin\DataType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\TypedData\Type\DateTimeInterface;
use Drupal\Core\TypedData\Plugin\DataType\Timestamp;

/**
 * The SmartDate data type.
 *
 * @DataType(
 *   id = "smart_date",
 *   label = @Translation("Smart Date")
 * )
 */
class SmartDate extends Timestamp implements DateTimeInterface {

  /**
   * The data value as a UNIX timestamp.
   *
   * @var int
   */
  protected $value;
  protected $end_value;
  protected $duration;
  protected $rrule;
  protected $rrule_index;
  protected $timezone;

  /**
   * {@inheritdoc}
   */
  public function getDateTime() {
    if (isset($this->value)) {
      return DrupalDateTime::createFromTimestamp($this->value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setDateTime(DrupalDateTime $dateTime, $notify = TRUE) {
    $this->value = $dateTime->getTimestamp();
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
