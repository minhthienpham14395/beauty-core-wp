(function () {
  'use strict';

  function initAppointmentFilters() {
    var form = document.querySelector('.beautycore-appointment-filters[data-beautycore-auto-filter]');
    if (!form || form.getAttribute('data-beautycore-filter-ready') === 'true') {
      return;
    }

    form.setAttribute('data-beautycore-filter-ready', 'true');
    var filterTimer;

    function submitFilters() {
      window.clearTimeout(filterTimer);
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
      } else {
        form.submit();
      }
    }

    form.addEventListener('change', function (event) {
      if (event.target.matches('select, input[type="date"]')) {
        submitFilters();
      }
    });

    var search = form.querySelector('input[type="search"]');
    if (search) {
      search.addEventListener('input', function () {
        window.clearTimeout(filterTimer);
        filterTimer = window.setTimeout(submitFilters, 500);
      });
    }
  }

  function initAppointmentModal() {
    initAppointmentFilters();

    if (window.BEAUTYCORE_APPOINTMENT_ADMIN_READY) {
      return;
    }

    var config = window.BEAUTYCORE_APPOINTMENT_ADMIN || {};
    var modal = document.getElementById('beautycore-appointment-modal');
    var modalBody = document.getElementById('beautycore-appointment-modal-body');

    if (!modal || !modalBody) {
      return;
    }
    window.BEAUTYCORE_APPOINTMENT_ADMIN_READY = true;
    document.documentElement.classList.add('beautycore-appointment-js-ready');

    function editUrl(appointmentId) {
      return 'admin.php?page=beautycore-appointment-edit' + (appointmentId ? '&id=' + appointmentId : '');
    }

    function closeModal(event) {
      if (event) {
        event.preventDefault();
      }
      modal.classList.remove('is-open');
      modal.hidden = true;
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('beautycore-modal-open');
      modalBody.innerHTML = '';
    }

    function showFallback(appointmentId, message) {
      modalBody.innerHTML = '<div class="notice notice-error"><p>' + message + '</p><p><a class="button" href="' + editUrl(appointmentId) + '">Mở biểu mẫu riêng</a></p></div>';
    }

    function openModal(appointmentId) {
      if (!config.ajaxUrl || !config.nonce) {
        window.location.href = editUrl(appointmentId);
        return;
      }

      modal.hidden = false;
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('beautycore-modal-open');
      modalBody.innerHTML = '<p class="beautycore-modal-loading">Đang tải biểu mẫu...</p>';
      document.getElementById('beautycore-appointment-modal-title').textContent = appointmentId ? 'Sửa lịch hẹn' : 'Tạo lịch hẹn';

      var query = new URLSearchParams({
        action: 'beautycore_appointment_form',
        appointment_id: String(appointmentId || 0),
        _ajax_nonce: config.nonce
      });

      fetch(config.ajaxUrl + (config.ajaxUrl.indexOf('?') === -1 ? '?' : '&') + query.toString(), {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (response) {
          return response.json().then(function (data) {
            return { ok: response.ok, data: data };
          });
        })
        .then(function (result) {
          if (!result.ok || !result.data || !result.data.success) {
            showFallback(appointmentId, 'Không thể tải biểu mẫu lịch hẹn.');
            return;
          }
          modalBody.innerHTML = result.data.data;
        })
        .catch(function () {
          showFallback(appointmentId, 'Không thể kết nối đến máy chủ.');
        });
    }

    document.addEventListener('click', function (event) {
      var target = event.target && event.target.closest ? event.target : null;
      if (!target) {
        return;
      }

      var addButton = target.closest('.beautycore-add-appointment');
      if (addButton) {
        event.preventDefault();
        openModal(0);
        return;
      }

      var editButton = target.closest('.beautycore-edit-appointment');
      if (editButton) {
        event.preventDefault();
        openModal(Number(editButton.getAttribute('data-appointment-id')) || 0);
        return;
      }

      if (target.closest('[data-beautycore-modal-close]')) {
        closeModal(event);
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal.classList.contains('is-open')) {
        closeModal();
      }
    });

    document.addEventListener('change', function (event) {
      if (!event.target || event.target.id !== 'service_id') {
        return;
      }
      var option = event.target.options[event.target.selectedIndex];
      if (!option) {
        return;
      }
      var duration = option.getAttribute('data-duration');
      var price = option.getAttribute('data-price');
      var durationField = document.getElementById('duration');
      var priceField = document.getElementById('price');
      if (duration && durationField && !durationField.value) {
        durationField.value = duration;
      }
      if (price && priceField && !priceField.value) {
        priceField.value = price;
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAppointmentModal);
  } else {
    initAppointmentModal();
  }
})();
