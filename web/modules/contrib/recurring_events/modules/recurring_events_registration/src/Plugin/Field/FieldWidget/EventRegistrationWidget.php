<?php

namespace Drupal\recurring_events_registration\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeDefaultWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\user\Entity\Role;

/**
 * Plugin implementation of the 'event registration' widget.
 *
 * @FieldWidget (
 *   id = "event_registration",
 *   label = @Translation("Event registration widget"),
 *   field_types = {
 *     "event_registration"
 *   }
 * )
 */
class EventRegistrationWidget extends DateRangeDefaultWidget {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_enable_waitlist' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['show_enable_waitlist'] = [
      '#type' => 'radios',
      '#options' => [
        '1' => $this->t('Show'),
        '0' => $this->t('Hide'),
      ],
      '#title' => $this->t('Enable Waiting List'),
      '#default_value' => $this->getSetting('show_enable_waitlist'),
      '#description' => $this->t('This will show/hide the "Enable Waiting List" checkbox in the Add/Edit Series form'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $enable_waitlist = $this->getSetting('show_enable_waitlist') ? $this->t('On') : $this->t('Off');
    $summary[] = $this->t('Enable Waiting list is: @value', ['@value' => $enable_waitlist]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#type'] = 'container';

    $element['registration'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Registration'),
      '#description' => $this->t('Select this box to enable registrations for this event. By doing so you will be able to specify the capacity of the event, and if applicable enable a waitlist.'),
      '#weight' => 0,
      '#default_value' => $items[$delta]->registration ?: '',
    ];

    $element['unique_email_address'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Restrict registration to once per email address?'),
      '#description' => $this->t('Select this box to only allow an email address to register for an event one time.'),
      '#weight' => 1,
      '#default_value' => $items[$delta]->unique_email_address ?: '',
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $role_options = [];
    $roles = Role::loadMultiple();
    if (!empty($roles)) {
      foreach ($roles as $role) {
        $role_options[$role->id()] = $role->label();
      }
    }
    $element['permitted_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Which roles can register for this series?'),
      '#description' => $this->t('Select all the roles that can register, or leave blank to allow anybody to register.'),
      '#weight' => 1,
      '#default_value' => $items[$delta]->permitted_roles ? explode(',', $items[$delta]->permitted_roles) : [],
      '#options' => $role_options,
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['registration_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Registration Type'),
      '#description' => $this->t('Select whether registrations are for the entire series, or for individual instances.'),
      '#weight' => 2,
      '#default_value' => $items[$delta]->registration_type ?: 'instance',
      '#options' => [
        'instance' => $this->t('Individual Event Registration'),
        'series' => $this->t('Entire Series Registration'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['registration_dates'] = [
      '#type' => 'radios',
      '#title' => $this->t('Registration Dates'),
      '#description' => $this->t('Choose between open or scheduled registration. Open registration ends when the event begins.'),
      '#weight' => 3,
      '#default_value' => $items[$delta]->registration_dates ?: 'open',
      '#options' => [
        'open' => $this->t('Open Registration'),
        'scheduled' => $this->t('Scheduled Registration'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['series_registration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Series Registration'),
      '#weight' => 4,
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
          ':input[name="event_registration[0][registration_type]"]' => ['value' => 'series'],
          ':input[name="event_registration[0][registration_dates]"]' => ['value' => 'scheduled'],
        ],
      ],
    ];

    $element['series_registration']['value'] = $element['value'];
    $element['series_registration']['end_value'] = $element['end_value'];
    unset($element['value']);
    unset($element['end_value']);

    $element['series_registration']['value']['#title'] = $this->t('Registration Opens');
    $element['series_registration']['end_value']['#title'] = $this->t('Registration Closes');

    $element['instance_registration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Instance Registration'),
      '#weight' => 4,
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
          ':input[name="event_registration[0][registration_type]"]' => ['value' => 'instance'],
          ':input[name="event_registration[0][registration_dates]"]' => ['value' => 'scheduled'],
        ],
      ],
    ];

    $element['instance_registration']['instance_schedule_open'] = [
      '#type' => 'select',
      '#title' => $this->t('Registration Opens'),
      '#description' => $this->t('Select when to open registration'),
      '#weight' => 0,
      '#default_value' => $items[$delta]->instance_schedule_open,
      '#options' => [
        'now' => $this->t('Now'),
        'start' => $this->t('At the start of the event'),
        'custom' => $this->t('Custom schedule'),
      ],
    ];

    $element['instance_registration']['open_registration'] = [
      '#type' => 'container',
      '#weight' => 1,
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][instance_registration][instance_schedule_open]"]' => ['value' => 'custom'],
        ],
      ],
      '#attributes' => [
        'class' => 'container-inline',
      ],
    ];

    $element['instance_registration']['open_registration']['instance_schedule_open_amount'] = [
      '#type' => 'number',
      '#title' => '',
      '#weight' => 1,
      '#default_value' => $items[$delta]->instance_schedule_open_amount ?? '1',
      '#min' => 0,
    ];

    $element['instance_registration']['open_registration']['instance_schedule_open_units'] = [
      '#type' => 'select',
      '#title' => '',
      '#weight' => 2,
      '#default_value' => $items[$delta]->instance_schedule_open_units ?? 'month',
      '#options' => [
        'month' => $this->t('months'),
        'week' => $this->t('weeks'),
        'day' => $this->t('days'),
        'hour' => $this->t('hours'),
        'minute' => $this->t('minutes'),
        'second' => $this->t('seconds'),
      ],
      '#suffix' => $this->t('before the event starts'),
    ];

    $element['instance_registration']['instance_schedule_close'] = [
      '#type' => 'select',
      '#title' => $this->t('Registration Closes'),
      '#description' => $this->t('Select when to close registration'),
      '#weight' => 3,
      '#default_value' => $items[$delta]->instance_schedule_close,
      '#options' => [
        'start' => $this->t('At the start of the event'),
        'end' => $this->t('At the end of the event'),
        'custom' => $this->t('Custom schedule'),
      ],
    ];

    $element['instance_registration']['close_registration'] = [
      '#type' => 'container',
      '#weight' => 4,
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][instance_registration][instance_schedule_close]"]' => ['value' => 'custom'],
        ],
      ],
      '#attributes' => [
        'class' => 'container-inline',
      ],
    ];

    $element['instance_registration']['close_registration']['instance_schedule_close_amount'] = [
      '#type' => 'number',
      '#title' => '',
      '#weight' => 1,
      '#default_value' => $items[$delta]->instance_schedule_close_amount ?? '1',
      '#min' => 0,
    ];

    $element['instance_registration']['close_registration']['instance_schedule_close_units'] = [
      '#type' => 'select',
      '#title' => '',
      '#weight' => 2,
      '#default_value' => $items[$delta]->instance_schedule_close_units ?? 'week',
      '#min' => 0,
      '#options' => [
        'month' => $this->t('months'),
        'week' => $this->t('weeks'),
        'day' => $this->t('days'),
        'hour' => $this->t('hours'),
        'minute' => $this->t('minutes'),
        'second' => $this->t('seconds'),
      ],
    ];

    $element['instance_registration']['close_registration']['instance_schedule_close_type'] = [
      '#type' => 'select',
      '#title' => '',
      '#weight' => 3,
      '#default_value' => $items[$delta]->instance_schedule_close_type ?: '',
      '#options' => [
        'before' => $this->t('before the event starts'),
        'after' => $this->t('after the event starts'),
      ],
    ];

    $element['capacity'] = [
      '#type' => 'number',
      '#title' => $this->t('Total Number of Spaces Available'),
      '#description' => $this->t('Maximum number of attendees available for each series, or individual event. Leave blank for unlimited.'),
      '#weight' => 6,
      '#default_value' => $items[$delta]->capacity ?: '',
      '#min' => 0,
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $enable_waitlist = $this->getSetting('show_enable_waitlist');
    $waitlist_default_value = $enable_waitlist ? ($items[$delta]->waitlist ?? FALSE) : FALSE;
    $items[$delta]->waitlist = 1;
    $element['waitlist'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Waiting List'),
      '#description' => $this->t('Enable a waiting list if the number of registrations reaches capacity.'),
      '#weight' => 7,
      '#default_value' => $waitlist_default_value,
      '#states' => [
        'visible' => [
          ':input[name="event_registration[0][registration]"]' => ['checked' => TRUE],
        ],
      ],
      '#access' => $enable_waitlist ? TRUE : FALSE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      $item['value'] = $item['series_registration']['value'];
      $item['end_value'] = $item['series_registration']['end_value'];
      $item['instance_schedule_open'] = $item['instance_registration']['instance_schedule_open'];
      $item['instance_schedule_open_amount'] = (int) $item['instance_registration']['open_registration']['instance_schedule_open_amount'];
      $item['instance_schedule_open_units'] = $item['instance_registration']['open_registration']['instance_schedule_open_units'];
      $item['instance_schedule_close'] = $item['instance_registration']['instance_schedule_close'];
      $item['instance_schedule_close_amount'] = (int) $item['instance_registration']['close_registration']['instance_schedule_close_amount'];
      $item['instance_schedule_close_units'] = $item['instance_registration']['close_registration']['instance_schedule_close_units'];
      $item['instance_schedule_close_type'] = $item['instance_registration']['close_registration']['instance_schedule_close_type'];
      $item['capacity'] = (int) $item['capacity'];
      $selected_roles = array_filter($item['permitted_roles'], function ($i) {
        return $i !== 0;
      });
      $item['permitted_roles'] = implode(',', $selected_roles);
      unset($item['series_registration']);
      unset($item['instance_registration']);

      if (empty($item['value'])) {
        $item['value'] = '';
      }

      if (empty($item['end_value'])) {
        $item['end_value'] = '';
      }

      if (empty($item['registration'])) {
        $item['registration'] = 0;
      }

      if (empty($item['registration_type'])) {
        $item['registration_type'] = '';
      }

      if (empty($item['registration_dates'])) {
        $item['registration_dates'] = '';
      }

      if (empty($item['capacity'])) {
        $item['capacity'] = 0;
      }

      if (empty($item['waitlist'])) {
        $item['waitlist'] = 0;
      }

      if (empty($item['instance_schedule_open'])) {
        $item['instance_schedule_open'] = '';
      }

      if (empty($item['instance_schedule_open_amount'])) {
        $item['instance_schedule_open_amount'] = 0;
      }

      if (empty($item['instance_schedule_open_units'])) {
        $item['instance_schedule_open_units'] = '';
      }

      if (empty($item['instance_schedule_close'])) {
        $item['instance_schedule_close'] = '';
      }

      if (empty($item['instance_schedule_close_amount'])) {
        $item['instance_schedule_close_amount'] = 0;
      }

      if (empty($item['instance_schedule_close_units'])) {
        $item['instance_schedule_close_units'] = '';
      }

      if (empty($item['instance_schedule_close_type'])) {
        $item['instance_schedule_close_type'] = '';
      }

      if (empty($item['unique_email_address'])) {
        $item['unique_email_address'] = 0;
      }

      if (empty($item['permitted_roles'])) {
        $item['permitted_roles'] = '';
      }

    }
    $values = parent::massageFormValues($values, $form, $form_state);
    return $values;
  }

  /**
   * Validate callback to ensure that the start date <= the end date.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   */
  public function validateStartEnd(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $start_date = $element['series_registration']['value']['#value']['object'];
    $end_date = $element['series_registration']['end_value']['#value']['object'];

    if ($start_date instanceof DrupalDateTime && $end_date instanceof DrupalDateTime) {
      if ($start_date->getTimestamp() !== $end_date->getTimestamp()) {
        $interval = $start_date->diff($end_date);
        if ($interval->invert === 1) {
          $form_state->setError($element, $this->t('The @title end date cannot be before the start date', ['@title' => $element['#title']]));
        }
      }
    }
  }

}
