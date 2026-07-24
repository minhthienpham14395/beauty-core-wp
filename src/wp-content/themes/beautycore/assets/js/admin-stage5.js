(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var config = window.BEAUTYCORE_STAGE5_ADMIN || {};
    var form = document.querySelector('[data-beautycore-stage5-filter]');
    if (form) {
      var timer;
      var submit = function () {
        if (typeof form.requestSubmit === 'function') {
          form.requestSubmit();
        } else {
          form.submit();
        }
      };
      form.addEventListener('change', function (event) {
        if (event.target.matches('select')) {
          submit();
        }
      });
      var search = form.querySelector('input[type="search"]');
      if (search) {
        search.addEventListener('input', function () {
          window.clearTimeout(timer);
          timer = window.setTimeout(submit, 500);
        });
      }
    }

    var modal = document.getElementById('beautycore-stage5-modal');
    var modalBody = document.getElementById('beautycore-stage5-modal-body');
    if (!modal || !modalBody) {
      return;
    }

    function closeModal() {
      modal.classList.remove('is-open');
      modal.hidden = true;
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('beautycore-modal-open');
      modalBody.innerHTML = '';
    }

    function openModal(entity, objectId) {
      if (!config.ajaxUrl || !config.nonce) {
        return;
      }
      var titles = {
        customer: objectId ? 'Sửa khách hàng' : 'Thêm khách hàng',
        voucher: objectId ? 'Sửa voucher' : 'Thêm voucher',
        review: objectId ? 'Duyệt đánh giá' : 'Thêm đánh giá'
      };
      modal.hidden = false;
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('beautycore-modal-open');
      document.getElementById('beautycore-stage5-modal-title').textContent = titles[entity] || 'Chỉnh sửa';
      modalBody.innerHTML = '<p class="beautycore-modal-loading">Đang tải biểu mẫu...</p>';

      var query = new URLSearchParams({
        action: 'beautycore_stage5_form',
        entity: entity,
        object_id: String(objectId || 0),
        _ajax_nonce: config.nonce
      });
      fetch(config.ajaxUrl + (config.ajaxUrl.indexOf('?') === -1 ? '?' : '&') + query.toString(), { credentials: 'same-origin' })
        .then(function (response) { return response.json(); })
        .then(function (response) {
          if (!response || !response.success) {
            throw new Error('Không thể tải biểu mẫu.');
          }
          modalBody.innerHTML = response.data;
        })
        .catch(function () {
          modalBody.innerHTML = '<div class="notice notice-error"><p>Không thể tải biểu mẫu. Hãy dùng đường dẫn mở biểu mẫu riêng.</p></div>';
        });
    }

    document.addEventListener('click', function (event) {
      var target = event.target && event.target.closest ? event.target : null;
      if (!target) {
        return;
      }
      var trigger = target.closest('.beautycore-stage5-open');
      if (trigger) {
        if (!config.ajaxUrl || !config.nonce) {
          return;
        }
        event.preventDefault();
        openModal(trigger.getAttribute('data-stage5-entity'), Number(trigger.getAttribute('data-object-id')) || 0);
        return;
      }
      if (target.closest('[data-beautycore-stage5-close]')) {
        event.preventDefault();
        closeModal();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal.classList.contains('is-open')) {
        closeModal();
      }
    });
  });
})();
