(function ($) {
  'use strict';

  $(function () {
    var config = window.BEAUTYCORE_SERVICE_ADMIN || {};
    var modal = $('#beautycore-service-modal');
    var modalBody = $('#beautycore-service-modal-body');
    var frame;
    var filterTimer;

    function submitFilters(form) {
      window.clearTimeout(filterTimer);
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
      } else {
        form.submit();
      }
    }

    $(document).on('change', '.beautycore-service-filters select', function () {
      submitFilters(this.form);
    });

    $(document).on('input', '.beautycore-service-filters input[type="search"]', function () {
      var form = this.form;
      window.clearTimeout(filterTimer);
      filterTimer = window.setTimeout(function () {
        submitFilters(form);
      }, 500);
    });

    function closeModal(event) {
      if (event) {
        event.preventDefault();
      }
      if (!modal.length) {
        if (event && event.currentTarget && event.currentTarget.href) {
          window.location.href = event.currentTarget.href;
        }
        return;
      }
      modal.removeClass('is-open').prop('hidden', true).attr('aria-hidden', 'true');
      $('body').removeClass('beautycore-modal-open');
      modalBody.empty();
    }

    function openModal(serviceId, errorMessage) {
      if (!modal.length) {
        window.location.href = 'admin.php?page=beautycore-service-edit' + (serviceId ? '&id=' + serviceId : '');
        return;
      }

      modal.removeAttr('hidden').addClass('is-open').attr('aria-hidden', 'false');
      $('body').addClass('beautycore-modal-open');
      modalBody.html('<p class="beautycore-modal-loading">Đang tải biểu mẫu...</p>');
      $('#beautycore-service-modal-title').text(serviceId ? 'Sửa dịch vụ' : 'Thêm dịch vụ');

      $.get(config.ajaxUrl, {
        action: 'beautycore_service_form',
        service_id: serviceId || 0,
        _ajax_nonce: config.nonce
      }).done(function (response) {
        if (!response || !response.success) {
          modalBody.html('<div class="notice notice-error"><p>Không thể tải biểu mẫu dịch vụ.</p></div>');
          return;
        }
        modalBody.html(response.data);
        if (errorMessage) {
          modalBody.prepend('<div class="notice notice-error"><p>' + $('<div>').text(errorMessage).html() + '</p></div>');
        }
      }).fail(function () {
        modalBody.html('<div class="notice notice-error"><p>Không thể kết nối đến máy chủ.</p></div>');
      });
    }

    $(document).on('click', '.beautycore-add-service', function (event) {
      event.preventDefault();
      openModal(0, '');
    });

    $(document).on('click', '.beautycore-edit-service', function (event) {
      event.preventDefault();
      openModal($(this).data('service-id'), '');
    });

    $(document).on('click', '#beautycore-generate-slug', function (event) {
      event.preventDefault();
      var title = $('#title').val() || '';
      var slug = title.toString().toLowerCase();
      if (slug.normalize) {
        slug = slug.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
      }
      slug = slug.replace(/đ/g, 'd').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
      $('#slug').val(slug).trigger('change');
    });

    $(document).on('click', '[data-beautycore-modal-close]', function (event) {
      closeModal(event);
    });
    $(document).on('keydown', function (event) {
      if (event.key === 'Escape' && modal.hasClass('is-open')) {
        closeModal();
      }
    });

    $(document).on('click', '#beautycore-select-image', function (event) {
      event.preventDefault();
      if (frame) {
        frame.open();
        return;
      }
      frame = wp.media({
        title: 'Chọn ảnh dịch vụ',
        button: { text: 'Dùng ảnh này' },
        multiple: false,
        library: { type: 'image' }
      });
      frame.on('select', function () {
        var attachment = frame.state().get('selection').first().toJSON();
        $('#beautycore-image-id').val(attachment.id);
        $('#image_url').val(attachment.url);
        $('#beautycore-image-preview').html('<img src="' + attachment.url + '" alt="">');
      });
      frame.open();
    });

    $(document).on('click', '#beautycore-remove-image', function (event) {
      event.preventDefault();
      $('#beautycore-image-id').val('0');
      $('#image_url').val('');
      $('#beautycore-image-preview').html('<span>Chưa chọn ảnh</span>');
    });

  });
})(jQuery);
