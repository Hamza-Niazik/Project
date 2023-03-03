(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.smartDate = {
    attach: function (context, settings) {
      once('smartDateDuration', '.smartdate--widget select.field-duration', context).forEach(function (element) {
        setInitialDuration(element);
        augmentInputs(element);
        element.addEventListener("change", function () {
          durationChanged(element);
        }, false);
      });
      once('smartDateAllDay', '.allday', context).forEach(function (element) {
        setAllDay(element);
        element.addEventListener("change", function () {
          checkAllDay(element);
        }, false);
      });
      once('smartDateHideSeconds', '.smartdate--widget input[type="time"]', context).forEach(function (element) {
        element.step = 60;
      });
      once('smartDateStartChange', '.smartdate--widget .time-start input', context).forEach(function (element) {
        element.addEventListener("change", function () {
          setEndDate(element);
        }, false);
      });
      once('smartDateEndChange', '.smartdate--widget .time-end', context).forEach(function (element) {
        element.addEventListener("change", function () {
          setDuration(element);
        }, false);
      });

      function setEndDate(element) {
        let wrapper = element.closest('.smartdate--widget');
        let duration_select = wrapper.querySelector('select.field-duration');
        let duration = false;
        if (duration_select.value === 'custom') {
          duration = parseInt(duration_select.dataset.duration);
        }
        else {
          duration = parseInt(duration_select.value);
        }
        if (duration === false || duration == 'custom') { return; }

        let start_date = wrapper.querySelector('.time-start.form-date').value;
        if (!start_date) {
          return;
        }
        let start_time = wrapper.querySelector('.time-start.form-time').value;
        if (!start_time && start_date) {
          // If only the start date has been specified update only the end date.
          wrapper.querySelector('.time-end.form-date').value = start_date;
          return;
        }

        let start_array = start_time.split(':');
        let end = new Date();
        if (start_date.length) {
          // Use Date objects to automatically roll over days when necessary.
          // ISO 8601 string get encoded as UTC so add the timezone offset.
          end = new Date(Date.parse(start_date));
          let is_iso_8061 = start_date.match(/\d{4}-\d{2}-\d{2}/);
          if (is_iso_8061 && end.getTimezoneOffset() != 0) {
            end.setMinutes(end.getMinutes() + end.getTimezoneOffset());
          }
        }

        // Calculate and set End Time only if All Day is not checked.
        if (!wrapper.querySelector('input.allday') || wrapper.querySelector('input.allday').checked == false) {
          end.setHours(start_array[0]);
          end.setMinutes(parseInt(start_array[1]) + duration);

          // Update End Time input.
          let end_val = pad(end.getHours(), 2) + ":" + pad(end.getMinutes(), 2);
          wrapper.querySelector('.time-end.form-time').value = end_val;
        }

        // Update End Date input.
        let new_end = end.getFullYear() + '-' + pad(end.getMonth() + 1, 2) + '-' + pad(end.getDate(), 2);
        wrapper.querySelector('.time-end.form-date').value = new_end;
        checkEndDate(wrapper);
      }

      function durationChanged(element) {
        let current_val = element.value;
        let wrapper = element.closest('.smartdate--widget');
        let end_time_input = wrapper.querySelector('.time-end.form-time');
        let end_date_input = wrapper.querySelector('.time-end.form-date');
        let separator = wrapper.querySelector('.smartdate--separator');
        // A strict comparison is needed, but not sure which type we'll get.
        if (current_val === 0 || current_val === '0') {
          // Hide the end date and time.
          end_time_input.style.display = 'none';
          end_date_input.style.display = 'none';
          hideLabels(wrapper);
          if (separator) {
            separator.style.display = 'none';
          }
        }
        else {
          // If they're hidden, show them.
          end_time_input.style.display = '';
          end_date_input.style.display = '';
          hideLabels(wrapper, false);
          if (separator) {
            separator.style.display = '';
          }
        }
        if (element.value === 'custom') {
          // Reset end time and add focus.
          let wrapper = element.closest('fieldset');
          let end_time = wrapper.querySelector('.time-end.form-time');
          end_time.value = '';
          end_time.focus();
        }
        else {
          // Fire normal setEndDate().
          setEndDate(element);
        }
        checkEndDate(wrapper);
      }

      function setInitialDuration(element) {
        let duration = element.value;
        if (duration == 'custom') {
          let wrapper = element.closest('.smartdate--widget');
          duration = calcDuration(wrapper);
        }
        else if (duration == 0) {
          // Call this to hide the end date and time.
          durationChanged(element);
        }
        // Store the numeric value in a property so it can be used programmatically.
        element.dataset.duration = duration;
      }

      // Add/change inputs based on initial config.
      function augmentInputs(element) {
        // Add "All day checkbox" if config permits.
        if (element.querySelectorAll('select [value="custom"]').length > 0 || element.querySelectorAll('select [value="1439"]').length > 0) {
          // Create the input element.
          let checkbox = document.createElement('input');
          checkbox.type = 'checkbox';
          checkbox.classList.add('allday');

          // Create the label element.
          let label = document.createElement('label');
          label.classList.add('allday-label');
          // Insert the input into the label.
          label.appendChild(checkbox);
          label.appendChild(document.createTextNode(Drupal.t('All day')));

          element.parentElement.insertAdjacentElement('beforebegin', label);
        }
        // If a forced duration, make end date and time read only.
        if (element.querySelectorAll('select [value="custom"]').length == 0) {
          let wrapper = element.closest('fieldset');
          let end_time_input = wrapper.querySelector('.time-end.form-time');
          let end_date_input = wrapper.querySelector('.time-end.form-date');
          end_time_input.readOnly = true;
          end_time_input.ariaReadOnly = true;
          end_date_input.readOnly = true;
          end_date_input.ariaReadOnly = true;
          checkEndDate(wrapper);
        }
      }

      function setDuration(element) {
        let wrapper = element.closest('.smartdate--widget');
        let duration = calcDuration(wrapper);
        if (duration == 0) {
          return;
        }
        let duration_select = wrapper.querySelector('select.field-duration');
        // Store the numeric value in a property so it can be used programmatically.
        duration_select.dataset.duration = duration;
        // Update the select to show the appropriate value.
        if (duration_select.querySelectorAll('option[value="' + duration + '"]').length != 0){
          duration_select.value = duration;
        } else {
          duration_select.value = 'custom';
        }
      }

      function calcDuration(wrapper) {
        let start_time = wrapper.querySelector('.time-start.form-time').value;
        let start_date = wrapper.querySelector('.time-start.form-date').value;
        let end_time = wrapper.querySelector('.time-end.form-time').value;
        let end_date = wrapper.querySelector('.time-end.form-date').value;
        if (!start_time || !start_date || !end_time || !end_date) {
          return 0;
        }
        // Split times into hours and minutes.
        let start_array = start_time.split(':');
        let end_array = end_time.split(':');
        let duration = 0;
        if (start_date !== end_date) {
          // The range spans more than one day, so use Date objects to calculate duration.
          let start = new Date(start_date);
          start.setHours(start_array[0]);
          start.setMinutes(parseInt(start_array[1]));
          let end = new Date(end_date);
          end.setHours(end_array[0]);
          end.setMinutes(parseInt(end_array[1]));
          duration = (end.getTime() - start.getTime()) / (60 * 1000);
        } else {
          // Convert to minutes and get the difference.
          duration = (parseInt(end_array[0]) - parseInt(start_array[0])) * 60 + (parseInt(end_array[1]) - parseInt(start_array[1]));
        }
        return duration;
      }

      function setAllDay(element) {
        let checkbox = element;
        let wrapper = checkbox.closest('.smartdate--widget');
        let start_time = wrapper.querySelector('.time-start.form-time');
        let end_time = wrapper.querySelector('.time-end.form-time');
        let duration = wrapper.querySelector('select.field-duration');
        // Set initial state of checkbox based on initial values.
        if (start_time.value == '00:00:00' && end_time.value == '23:59:00') {
          checkbox.checked = true;
          checkbox.dataset.duration = duration.dataset.default;
          start_time.style.display = 'none';
          end_time.style.display = 'none';
          hideLabels(wrapper);
          let duration_wrapper = duration.parentElement;
          duration_wrapper.style.display = 'none';
        }
        else {
          checkbox.dataset.duration = duration.value;
        }
        checkEndDate(wrapper);
      }

      function checkAllDay(element) {
        let checkbox = element;
        let wrapper = checkbox.closest('.smartdate--widget');
        let start_time = wrapper.querySelector('input.time-start.form-time');
        let end_time = wrapper.querySelector('.time-end.form-time');
        let duration = wrapper.querySelector('select.field-duration');
        let duration_wrapper = duration.parentElement;

        if (checkbox.checked == true) {
          if (checkbox.dataset.duration == 0) {
            let end_date = wrapper.querySelector('input.time-end.form-date');
            end_date.style.display = '';
            let end_date_label = wrapper.querySelector('.time-start + .label');
            if (end_date_label) {
              end_date_label.style.display = '';
            }
          }
          // Save the current start and end_date.
          checkbox.dataset.start = start_time.value;
          checkbox.dataset.end = end_time.value;
          checkbox.dataset.duration = duration.value;
          duration_wrapper.style.visibility = 'hidden';
          // Set the duration to a corresponding value.
          if (duration.querySelectorAll('option[value="custom"]').length != 0) {
            duration.value = 'custom';
          }
          else if (duration.querySelectorAll('option[value="1439"]').length != 0) {
            duration.value = '1439';
          }
          // Set to all day $values and hide time elements.
          start_time.style.display = 'none';
          start_time.value = '00:00';
          end_time.style.display = 'none';
          end_time.value = '23:59';
          hideLabels(wrapper);
          // Force the end date visible.
          let end_date = wrapper.querySelector('.time-end.form-date');
          end_date.style.visibility = 'visible';
        }
        else {
          // Restore from data values.
          if (checkbox.dataset.start) {
            start_time.value = checkbox.dataset.start;
          }
          else {
            start_time.value = '';
          }
          if (checkbox.dataset.end) {
            end_time.value = checkbox.dataset.end;
          }
          else {
            end_time.value = '';
          }
          if (checkbox.dataset.duration || checkbox.dataset.duration === 0 || checkbox.dataset.duration === '0') {
            duration.value = checkbox.dataset.duration;
            duration.dataset.duration = checkbox.dataset.duration;
            if (!end_time.value) {
              setEndDate(start_time);
            }
          }
          // Make time inputs visible.
          start_time.style.display = '';
          end_time.style.display = '';
          duration_wrapper.style.visibility = 'visible';
          hideLabels(wrapper, false);
          if (duration.value == 0) {
            // Call this to hide the end date and time.
            durationChanged(duration);
          }
          checkEndDate(wrapper);
        }
      }

      function pad(str, max) {
        str = str.toString();
        return str.length < max ? pad("0" + str, max) : str;
      }

      function checkEndDate(wrapper) {
        let start_date = wrapper.querySelector('.time-start.form-date');
        let end_date = wrapper.querySelector('.time-end.form-date');
        let hide_me = end_date.dataset.hide;
        let allday = wrapper.querySelector('.allday');
        if (hide_me == 1 && end_date.value == start_date.value && allday && allday.checked == false) {
          end_date.style.visibility = 'hidden';
        }
        else {
          end_date.style.visibility = 'visible';
        }
      }

      function hideLabels(wrapper, hide = true) {
        let display_val = 'none';
        if (!hide) {
          display_val = '';
        }
        wrapper.querySelectorAll('.form-type--date label.form-item__label').forEach(function (label) {
          label.style.display = display_val;
        });
      }
    }
  };
} (Drupal, drupalSettings, once));
