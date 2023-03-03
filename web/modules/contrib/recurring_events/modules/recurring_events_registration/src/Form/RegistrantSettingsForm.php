<?php

namespace Drupal\recurring_events_registration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\recurring_events_registration\NotificationService;
use Drupal\recurring_events_registration\RegistrationCreationService;
use Drupal\Core\Extension\ModuleHandler;

/**
 * Provides a form for managing registration settings.
 *
 * @ingroup recurring_events_registration
 */
class RegistrantSettingsForm extends ConfigFormBase {

  /**
   * The registration notification service.
   *
   * @var \Drupal\recurring_events_registration\NotificationService
   */
  protected $notificationService;

  /**
   * The registration creation service.
   *
   * @var \Drupal\recurring_events_registration\RegistrationCreationService
   */
  protected $creationService;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a RegistrantSettingsForm object.
   *
   * @param \Drupal\recurring_events_registration\NotificationService $notification_service
   *   The registration notification service.
   * @param \Drupal\recurring_events_registration\RegistrationCreationService $creation_service
   *   The registration creation service.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler service.
   */
  public function __construct(NotificationService $notification_service, RegistrationCreationService $creation_service, ModuleHandler $module_handler) {
    $this->notificationService = $notification_service;
    $this->creationService = $creation_service;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('recurring_events_registration.notification_service'),
      $container->get('recurring_events_registration.creation_service'),
      $container->get('module_handler')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'registrant_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['recurring_events_registration.registrant.config'];
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('recurring_events_registration.registrant.config')
      ->set('show_capacity', $form_state->getValue('show_capacity'))
      ->set('limit', $form_state->getValue('limit'))
      ->set('date_format', $form_state->getValue('date_format'))
      ->set('title', $form_state->getValue('title'))
      ->set('successfully_registered', $form_state->getValue('successfully_registered'))
      ->set('successfully_registered_waitlist', $form_state->getValue('successfully_registered_waitlist'))
      ->set('successfully_updated', $form_state->getValue('successfully_updated'))
      ->set('successfully_updated_waitlist', $form_state->getValue('successfully_updated_waitlist'))
      ->set('already_registered', $form_state->getValue('already_registered'))
      ->set('registration_closed', $form_state->getValue('registration_closed'))
      ->set('email_notifications', $form_state->getValue('email_notifications'));

    $notification_types = [];
    $this->moduleHandler->alter('recurring_events_registration_notification_types', $notification_types);

    $notification_config = [];
    foreach ($notification_types as $type => $notification) {
      $notification_config[$type] = [
        'enabled' => $form_state->getValue($type . '_enabled'),
        'subject' => $form_state->getValue($type . '_subject'),
        'body' => $form_state->getValue($type . '_body'),
      ];
    }

    $config->set('notifications', $notification_config);
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Defines the settings form for Registrant entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('recurring_events_registration.registrant.config');

    $form['process'] = [
      '#type' => 'details',
      '#title' => $this->t('Registration Form'),
      '#open' => TRUE,
    ];

    $form['process']['show_capacity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Capacity?'),
      '#description' => $this->t('When users are registering for events, show the available capacity?'),
      '#default_value' => $config->get('show_capacity'),
    ];

    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Registrant Display'),
      '#open' => TRUE,
    ];

    $form['display']['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Registrant Items'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the number of items to show per page in the default registrant listing table.'),
      '#default_value' => $config->get('limit'),
    ];

    $php_date_url = Url::fromUri('https://secure.php.net/manual/en/function.date.php');
    $php_date_link = Link::fromTextAndUrl($this->t('PHP date/time format'), $php_date_url);

    $form['display']['date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Registrant Date Format'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the @link used when listing registrants. Default is F jS, Y h:iA.', [
        '@link' => $php_date_link->toString(),
      ]),
      '#default_value' => $config->get('date_format'),
    ];

    $registrant_tokens = $this->creationService->getAvailableTokens(['registrant']);

    $form['display']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Registrant Title'),
      '#required' => TRUE,
      '#description' => $this->t('Enter the format for the title field', [
        '@link' => $php_date_link->toString(),
      ]),
      '#default_value' => $config->get('title'),
    ];

    $form['display']['tokens'] = $registrant_tokens;

    $form['messages'] = [
      '#type' => 'details',
      '#title' => $this->t('Registration Messages'),
      '#open' => TRUE,
    ];

    $form['messages']['successfully_registered'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Successful Registration'),
      '#description' => $this->t('This message will show in the message area when a user successfully registers for an event.'),
      '#default_value' => $config->get('successfully_registered'),
    ];

    $form['messages']['successfully_registered_waitlist'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Successful Registration (Waitlist)'),
      '#description' => $this->t("This message will show in the message area when a user successfully registers for an event's waitlist."),
      '#default_value' => $config->get('successfully_registered_waitlist'),
    ];

    $form['messages']['successfully_updated'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Successful Update'),
      '#description' => $this->t('This message will show in the message area when a user successfully updates a registration for an event.'),
      '#default_value' => $config->get('successfully_updated'),
    ];

    $form['messages']['successfully_updated_waitlist'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Successful Update (Waitlist)'),
      '#description' => $this->t("This message will show in the message area when a user successfully updates a registration for an event's waitlist."),
      '#default_value' => $config->get('successfully_updated_waitlist'),
    ];

    $form['messages']['already_registered'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Duplicate Registration'),
      '#description' => $this->t('This message will show in the message area when a user tries to register a second time for the same event.'),
      '#default_value' => $config->get('already_registered'),
    ];

    $form['messages']['registration_closed'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Registration Window Closed'),
      '#description' => $this->t('This message will show in the message area when a user tries to register for an event for which registrations are closed.'),
      '#default_value' => $config->get('registration_closed'),
    ];

    $form['messages']['tokens'] = $registrant_tokens;

    $form['notifications'] = [
      '#type' => 'details',
      '#title' => $this->t('Email Notifications'),
      '#open' => TRUE,
    ];

    $form['notifications']['email_notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send Email Notifications?'),
      '#description' => $this->t('Send email notifications during registration or event updates?'),
      '#default_value' => $config->get('email_notifications'),
    ];

    $form['notifications']['emails'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Emails'),
      '#states' => [
        'visible' => [
          'input[name="email_notifications"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $tokens = $this->notificationService->getAvailableTokens();

    $notification_types = [];
    $this->moduleHandler->alter('recurring_events_registration_notification_types', $notification_types);
    $notification_config = $config->get('notifications');

    foreach ($notification_types as $type => $notification) {
      $form['notifications'][$type] = [
        '#type' => 'details',
        '#title' => $notification['name'],
        '#open' => TRUE,
        '#group' => 'emails',
      ];
      $form['notifications'][$type][$type . '_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $notification['name'],
        '#description' => $notification['description'],
        '#default_value' => $notification_config[$type]['enabled'],
      ];
      $form['notifications'][$type][$type . '_subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
        '#default_value' => $notification_config[$type]['subject'],
        '#maxlength' => 180,
        '#states' => [
          'visible' => [
            'input[name="' . $type . '_enabled"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['notifications'][$type][$type . '_body'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Body'),
        '#default_value' => $notification_config[$type]['body'],
        '#rows' => 15,
        '#states' => [
          'visible' => [
            'input[name="' . $type . '_enabled"]' => ['checked' => TRUE],
          ],
        ],
      ];
      $form['notifications'][$type]['tokens'] = [
        '#type' => 'container',
        'tokens' => $tokens,
        '#states' => [
          'visible' => [
            'input[name="' . $type . '_enabled"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

}
