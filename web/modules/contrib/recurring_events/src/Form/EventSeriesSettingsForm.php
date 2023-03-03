<?php

namespace Drupal\recurring_events\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\recurring_events\EventCreationService;
use Drupal\recurring_events\EventInstanceCreatorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring event series settings.
 *
 * @ingroup recurring_events
 */
class EventSeriesSettingsForm extends ConfigFormBase {

  /**
   * The event creation service.
   *
   * @var \Drupal\recurring_events\EventCreationService
   */
  protected $creationService;

  /**
   * The event instance creator plugin manager.
   *
   * @var \Drupal\recurring_events\EventInstanceCreatorPluginManager
   */
  protected $eventInstanceCreatorManager;

  /**
   * Constructs a new EventSeriesSettingsForm.
   *
   * @param \Drupal\recurring_events\EventCreationService $creation_service
   *   The event creation service.
   * @param \Drupal\recurring_events\EventInstanceCreatorPluginManager $creator_manager
   *   The event creation service.
   */
  public function __construct(EventCreationService $creation_service, EventInstanceCreatorPluginManager $creator_manager) {
    $this->creationService = $creation_service;
    $this->eventInstanceCreatorManager = $creator_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('recurring_events.event_creation_service'),
      $container->get('plugin.manager.event_instance_creator')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'eventseries_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['recurring_events.eventseries.config'];
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('recurring_events.eventseries.config')
      ->set('interval', $form_state->getValue('interval'))
      ->set('min_time', $form_state->getValue('min_time'))
      ->set('max_time', $form_state->getValue('max_time'))
      ->set('date_format', $form_state->getValue('date_format'))
      ->set('time_format', $form_state->getValue('time_format'))
      ->set('days', implode(',', array_filter($form_state->getValue('days'))))
      ->set('limit', $form_state->getValue('limit'))
      ->set('excludes', $form_state->getValue('excludes'))
      ->set('includes', $form_state->getValue('includes'))
      ->set('enabled_fields', implode(',', array_filter($form_state->getValue('enabled_fields'))))
      ->set('threshold_warning', $form_state->getValue('threshold_warning'))
      ->set('threshold_count', $form_state->getValue('threshold_count'))
      ->set('threshold_message', $form_state->getValue('threshold_message'))
      ->set('threshold_prevent_save', $form_state->getValue('threshold_prevent_save'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Define the form used for EventSeries settings.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('recurring_events.eventseries.config');

    $plugin_definitions = $this->eventInstanceCreatorManager->getDefinitions();
    $creator_plugins = [];
    foreach ($plugin_definitions as $id => $plugin) {
      $creator_plugins[$id] = (string) $plugin['description'];
    }

    $form['creation'] = [
      '#type' => 'details',
      '#title' => $this->t('Event Creation'),
      '#open' => TRUE,
    ];

    $form['creation']['interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Event Series Time Intervals'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the interval, in minutes, to be used to separate event start times. Default is 15 minutes. Set to 0 to allow users to enter any time.'),
      '#default_value' => $config->get('interval'),
    ];

    $form['creation']['min_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Series Minimum Time'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the earliest an event can start, in h:ia format. For example 08:00am.'),
      '#default_value' => $config->get('min_time'),
    ];

    $form['creation']['max_time'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Series Maximum Time'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the latest an event can start, in h:ia format. For example 11:45pm.'),
      '#default_value' => $config->get('max_time'),
    ];

    $php_date_url = Url::fromUri('https://secure.php.net/manual/en/function.date.php');
    $php_date_link = Link::fromTextAndUrl($this->t('PHP date/time format'), $php_date_url);

    $form['creation']['date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Series Date Format'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the @link used when listing event dates. Default is F jS, Y h:iA.', [
        '@link' => $php_date_link->toString(),
      ]),
      '#default_value' => $config->get('date_format'),
    ];

    $form['creation']['time_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Series Time Format'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the @link used when selecting times. Default is h:i A.', [
        '@link' => $php_date_link->toString(),
      ]),
      '#default_value' => $config->get('time_format'),
    ];

    $days = [
      'monday' => $this->t('Monday'),
      'tuesday' => $this->t('Tuesday'),
      'wednesday' => $this->t('Wednesday'),
      'thursday' => $this->t('Thursday'),
      'friday' => $this->t('Friday'),
      'saturday' => $this->t('Saturday'),
      'sunday' => $this->t('Sunday'),
    ];

    $form['creation']['days'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Event Series Days'),
      '#required' => TRUE,
      '#options' => $days,
      '#description' => $this->t('Select the days of the week available when creating events.'),
      '#default_value' => explode(',', $config->get('days')),
    ];

    $form['creation']['excludes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Event Specific Excluded Dates'),
      '#description' => $this->t('Enable event specific excluded dates? To add global excluded dates visit the @link.', [
        '@link' => Link::createFromRoute($this->t('excluded dates tab'), 'entity.excluded_dates.collection')->toString(),
      ]),
      '#default_value' => $config->get('excludes'),
    ];

    $form['creation']['includes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Event Specific Included Dates'),
      '#description' => $this->t('Enable event specific included dates? To add global included dates visit the @link.', [
        '@link' => Link::createFromRoute($this->t('included dates tab'), 'entity.included_dates.collection')->toString(),
      ]),
      '#default_value' => $config->get('includes'),
    ];

    $fields = $this->creationService->getRecurFieldTypes(FALSE);

    $form['creation']['enabled_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled Recur Field Types'),
      '#required' => TRUE,
      '#options' => $fields,
      '#description' => $this->t('Select the recur field types to enable.'),
      '#default_value' => explode(',', $config->get('enabled_fields')),
    ];

    $form['creation']['threshold_warning'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Event Instance Threshold Warning?'),
      '#description' => $this->t('Display a warning when too many event instances may be created?'),
      '#default_value' => $config->get('threshold_warning'),
    ];

    $form['creation']['threshold_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Event Instance Threshold Count'),
      '#description' => $this->t('The number of event instances to trigger the warning'),
      '#default_value' => $config->get('threshold_count'),
      '#states' => [
        'visible' => [
          'input[name="threshold_warning"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['creation']['threshold_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Event Instance Threshold Message'),
      '#description' => $this->t('Enter the message to be displayed. Use @total as a placeholder for the amount of instances being created.'),
      '#default_value' => $config->get('threshold_message'),
      '#states' => [
        'visible' => [
          'input[name="threshold_warning"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['creation']['threshold_prevent_save'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Event Instance Threshold Prevent Save'),
      '#description' => $this->t('Prevent saving a series if the threshold is exceeded?'),
      '#default_value' => $config->get('threshold_prevent_save'),
      '#states' => [
        'visible' => [
          'input[name="threshold_warning"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['creation']['creator_plugin'] = [
      '#type' => 'radios',
      '#title' => $this->t('Event Instance Creator Plugin'),
      '#description' => $this->t('Select the plugin to use when creating event instances.'),
      '#default_value' => $config->get('creator_plugin'),
      '#options' => $creator_plugins,
    ];

    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Event Display'),
      '#open' => TRUE,
    ];

    $form['display']['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Event Series Items'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the number of items to show per page in the default event series listing table.'),
      '#default_value' => $config->get('limit'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
