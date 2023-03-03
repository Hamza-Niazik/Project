(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.smartDateRecur = {
    attach: function (context, settings) {
      var repeat_labels = {
        'DAILY': '',
        'WEEKLY': '',
        'MONTHLY': '',
        'YEARLY': ''
      };

      var selected_labels = {
        'DAILY': Drupal.t('days', {}, { context: "Smart Date Recur" }),
        'WEEKLY': Drupal.t('weeks', {}, { context: "Smart Date Recur" }),
        'MONTHLY': Drupal.t('months', {}, { context: "Smart Date Recur" }),
        'YEARLY': Drupal.t('years', {}, { context: "Smart Date Recur" })
      };

      // Manipulate the labels for BYDAY checkboxes.
      once('smartDateRecurByDay', '.smartdate--widget .byday-checkboxes label', context).forEach(function (element) {
        element.title = element.textContent;
        element.tabIndex = 0;
        // Check the input on spacebar or return.
        element.addEventListener("keydown", function (event) {
          if (event.keyCode == 13 || event.keyCode == 32) {
            element.previousElementSibling.click();
            event.preventDefault();
          }
        }, false);
      });

      once('smartDateRecurAllDay', '.smartdate--widget .allday', context).forEach(function (element) {
        element.addEventListener("change", function () {
          toggleMinutesHours(element);
        }, false);
      });

      // Manipulate the labels for BYHOUR and BYMINUTE checkboxes.
      once('smartDateRecurHoursMinutes', '.smart-date--minutes input, .smart-date--hours input', context).forEach(function (element) {
        element.tabIndex = 0;
      });

      // special handler for duration updates
      once('smartDateRecurDuration', '.smartdate--widget select.field-duration', context).forEach(function (element) {
        element.addEventListener("change", function () {
          durationToMinutes(element);
        }, false);
      });

      once('smartDateRecurRepeat', '.smartdate--widget select.recur-repeat', context).forEach(function (element) {
        setDataFreq(element);
        setRepeatLabels(element);
        element.addEventListener("change", function () {
          updateInterval(element);
          updateRepeatLabels(element);
        }, false);
      });

      once('smartDateRecurDuration', '.smartdate--widget .time-end', context).forEach(function (element) {
        element.addEventListener("change", function () {
          durationToMinutes(element);
        }, false);
      });

      function durationToMinutes(element) {
        let wrapper = element.closest('fieldset');
        let freq = wrapper.querySelector('.recur-repeat');
        if (freq.value !== 'MINUTELY') {
          // The rest only needed for Minutes.
          return;
        }
        var duration_select = wrapper.querySelector('select.field-duration');
        var duration_val = duration_select.value;
        if (duration_val === 'custom') {
          duration_val = parseInt(duration_select.dataset.duration);
        }
        var interval = wrapper.querySelector('.field-interval');
        interval.value = duration_val;
      }

      function updateInterval(element) {
        var wrapper = element.closest('fieldset');
        var freq = wrapper.querySelector('.recur-repeat');
        if (freq.value === 'MINUTELY') {
          // When changeing to minutes, set to the current duration.
          durationToMinutes(element);
        }
        else if (freq.dataset.freq === 'MINUTELY') {
          // Only reset if changing from minutes.
          var interval = wrapper.querySelector('.field-interval');
          interval.value = '';
        }
        freq.dataset.freq = freq.value;
      }

      function setDataFreq(element) {
        var wrapper = element.closest('fieldset');
        var freq = wrapper.querySelector('.recur-repeat');
        freq.dataset.freq = freq.value;
      }

      function toggleMinutesHours(element) {
        var wrapper = element.closest('fieldset');
        var freq = wrapper.querySelector('.recur-repeat');
        var option_minutes = freq.querySelector("option[value = 'MINUTELY']");
        var option_hours = freq.querySelector("option[value = 'HOURLY']");
        var is_checked = element.checked;
        if (is_checked) {
          if (option_minutes) {
            option_minutes.disabled = true;
          }
          if (option_hours) {
            option_hours.disabled = true;
          }
        }
        else {
          if (option_minutes) {
            option_minutes.disabled = false;
          }
          if (option_hours) {
            option_hours.disabled = false;
          }
        }
      }

      function setRepeatLabels(element) {
        Array.from(element.options).forEach(function (option_element) {
          if (option_element.value) {
            repeat_labels[option_element.value] = option_element.text;
          }
        });
        if (element.value) {
          updateRepeatLabels(element);
        }
      }

      function updateRepeatLabels(element) {
        let past_repeat = element.dataset.repeat;
        // Store the new value for future comparisons.
        element.dataset.repeat = element.value;
        let option = element.querySelector('option[value=""]');
        let new_labels = false;
        if (!past_repeat && element.value) {
          // Recurring enabled, use selected labels.
          new_labels = selected_labels;
        }
        else if (past_repeat && !element.value) {
          // Recurring disabled, use empty labels.
          new_labels = repeat_labels;
        }
        if (new_labels) {
          // Labels set, update appropriately.
          Object.entries(new_labels).forEach(entry => {
            const [value, label] = entry;
            option = element.querySelector('option[value="' + value + '"]');
            if (option) {
              option.text = label;
            }
          });
        }
      }
    }
  };
}(Drupal, drupalSettings, once));
