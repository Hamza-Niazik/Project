<?php

namespace Drupal\recurring_events\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\daterange_compact\DateRangeFormatterInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implementation of the 'recurring events eventinstance datecompact' formatter.
 *
 * @FieldFormatter(
 *   id = "recurring_events_eventinstance_datecompact",
 *   label = @Translation("EventInstance Date Compact"),
 *   description = @Translation("Display the date of the referenced eventinstance using Date Compact."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EventInstanceDateCompactFormatter extends EntityReferenceFormatterBase {

  use StringTranslationTrait;

  /**
   * The date range formatter service.
   *
   * @var \Drupal\daterange_compact\DateRangeFormatterInterface
   */
  protected $dateRangeFormatter;

  /**
   * The date range format entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateRangeFormatStorage;

  /**
   * Constructs a new DateRangeCompactFormatter.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\daterange_compact\DateRangeFormatterInterface $date_range_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Entity\EntityStorageInterface $date_range_format_storage
   *   The date format entity storage.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, DateRangeFormatterInterface $date_range_formatter, EntityStorageInterface $date_range_format_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->dateRangeFormatter = $date_range_formatter;
    $this->dateRangeFormatStorage = $date_range_format_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('daterange_compact.date_range.formatter'),
      $container->get('entity_type.manager')->getStorage('date_range_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'link' => TRUE,
      'format_type' => 'medium',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['link'] = [
      '#title' => $this->t('Link date to the referenced entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('link'),
    ];

    $format_types = $this->dateRangeFormatStorage->loadMultiple();
    $options = [];
    foreach ($format_types as $type => $type_info) {
      $options[$type] = $type_info->label();
    }

    $elements['format_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Date and time range format'),
      '#description' => $this->t("Choose a format for displaying the date and time range."),
      '#options' => $options,
      '#default_value' => $this->getSetting('format_type'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->getSetting('link') ? $this->t('Link to the referenced entity') : $this->t('No link');
    $summary[] = $this->t('Format: @format', ['@format' => $this->getSetting('format_type')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'eventinstance') {
      $moduleHandler = \Drupal::service('module_handler');
      return ($moduleHandler->moduleExists('daterange_compact'));
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $output_as_link = $this->getSetting('link');

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $date_string = '';
      $user_timezone = new \DateTimeZone(date_default_timezone_get());
      if (!empty($entity->date->start_date) && !empty($entity->date->end_date)) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
        $start_date = $entity->date->start_date;
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
        $end_date = $entity->date->end_date;

        $start_date->setTimezone($user_timezone);
        $end_date->setTimezone($user_timezone);

        $start_timestamp = $start_date->getTimestamp();
        $end_timestamp = $end_date->getTimestamp();
        $format = $this->getSetting('format_type');

        if ($this->getFieldSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE) {
          $timezone = DateTimeItemInterface::STORAGE_TIMEZONE;
          $date_string = $this->dateRangeFormatter->formatDateRange($start_timestamp, $end_timestamp, $format, $timezone);
        }
        else {
          $timezone = date_default_timezone_get();
          $date_string = $this->dateRangeFormatter->formatDateTimeRange($start_timestamp, $end_timestamp, $format, $timezone);
        }

      }

      // If the link is to be displayed and the entity has a uri, display a
      // link.
      if ($output_as_link && !$entity->isNew()) {
        try {
          $uri = $entity->toUrl();
        }
        catch (UndefinedLinkTemplateException $e) {
          // This exception is thrown by \Drupal\Core\Entity\Entity::urlInfo()
          // and it means that the entity type doesn't have a link template nor
          // a valid "uri_callback", so don't bother trying to output a link for
          // the rest of the referenced entities.
          $output_as_link = FALSE;
        }
      }

      if ($output_as_link && isset($uri) && !$entity->isNew()) {
        $elements[$delta] = [
          '#type' => 'link',
          '#title' => $date_string,
          '#url' => $uri,
          '#options' => $uri->getOptions(),
          '#eventinstance' => $entity,
          '#cache' => [
            'contexts' => [
              'timezone',
            ],
          ],
        ];

        if (!empty($items[$delta]->_attributes)) {
          $elements[$delta]['#options'] += ['attributes' => []];
          $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and shouldn't be rendered in the field template.
          unset($items[$delta]->_attributes);
        }
      }
      else {
        $elements[$delta] = [
          '#plain_text' => $date_string,
          '#eventinstance' => $entity,
        ];
      }
      $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
    }

    usort($elements, function ($a, $b) {
      $a_date = $a['#eventinstance']->date->start_date->getTimestamp();
      $b_date = $b['#eventinstance']->date->start_date->getTimestamp();
      if ($a_date == $b_date) {
        return 0;
      }
      return ($a_date < $b_date) ? -1 : 1;
    });

    return $elements;
  }

}
