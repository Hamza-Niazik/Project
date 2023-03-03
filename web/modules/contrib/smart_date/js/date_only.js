(function (Drupal, drupalSettings, once) {
  'use strict';

  Drupal.behaviors.smartDate = {
    attach: function (context, settings) {
      // Update the end values when the start is changed.
      once('smartDateStartChange', '.smartdate--widget .time-start input', context).forEach(function (element) {
        element.addEventListener("change", function () {
          startChanged(element);
        }, false);
      });
      once('smartDateEndChange', '.smartdate--widget .time-end input', context).forEach(function (element) {
        // Set initial duration.
        endChanged(element);
        // Update the duration when end changed.
        element.addEventListener("change", function () {
          endChanged(element);
        }, false);
      });

      Date.prototype.addDays = function (days) {
        let new_date = this.getDate() + parseInt(days);
        this.setDate(new_date);
      }

      function startChanged(element) {
        if (!element.value) {
          return;
        }
        let wrapper = element.closest('fieldset');
        let duration = element.dataset.duration;
        let start_date = element.value;
        let end = new Date(Date.parse(start_date));
        let new_end = element.value;
        // Update end date if a duration is set.
        if (!isNaN(duration) && duration > 0) {
          end.addDays(duration);
          // ISO 8601 string get encoded as UTC so add the timezone offset.
          let is_iso_8061 = start_date.match(/\d{4}-\d{2}-\d{2}/);
          if (is_iso_8061 && end.getTimezoneOffset() != 0) {
            end.setMinutes(end.getMinutes() + end.getTimezoneOffset());
            new_end = end.getFullYear() + '-' + pad(end.getMonth() + 1, 2) + '-' + pad(end.getDate(), 2);
          } else {
            new_end = end.toLocaleDateString();
          }
        }
        wrapper.querySelector('.time-end.form-date').value = new_end;
      }

      function endChanged(element) {
        let wrapper = element.closest('fieldset');
        let start = wrapper.querySelector('.time-start.form-date');
        let end = element;
        let start_date = new Date(Date.parse(start.value));
        let end_date = new Date(Date.parse(end.value));
        // Update duration if a number can be determined.
        let duration = (end_date - start_date) / (1000 * 60 * 60 * 24);
        if (duration === 0 || duration > 0) {
          start.dataset.duration = duration;
        }
      }

      function pad(str, max) {
        str = str.toString();
        return str.length < max ? pad("0" + str, max) : str;
      }
    }
  };
} (Drupal, drupalSettings, once));
