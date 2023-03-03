<?php

namespace Drupal\smart_date\Normalizer;

use Drupal\serialization\Normalizer\TimestampNormalizer;
use Drupal\smart_date\TypedData\Plugin\DataType\SmartDate;

/**
 * Enhances the smart date field so it can be denormalized.
 */
class SmartDateNormalizer extends TimestampNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = SmartDate::class;

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    if (!empty($data['format'])) {
      // REST request sender may provide own data format, try to deploy it.
      // Parent classes override $format anyway.
      $context['datetime_allowed_formats'] =
        empty($context['datetime_allowed_formats']) ? [] : $context['datetime_allowed_formats'] + ['user_format' => $data['format']];
    }
    /*
    @todo check this suggestion
    not sure if this needed, seems properties should go from
    \TypedData\Plugin\DataType\SmartData and fall down to existing
    serializers may be via
    TypedDataInternalPropertiesHelper::getNonInternalProperties()
    but most inheritance done from Timestamps.
     */
    $res = [
      'value' => parent::denormalize($data['value'], $class, $format, $context),
      'end_value' => parent::denormalize($data['end_value'], $class, $format, $context),
      // StringData and IntegerData do not have normalizers.
      'duration' => @$data['duration'],
      'rrule' => @$data['rrule'],
      'rrule_index' => @$data['rrule_index'],
      'timezone' => @$data['timezone'],
    ];

    return $res;
  }

}
