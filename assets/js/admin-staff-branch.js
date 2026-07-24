(function ($) {
  "use strict";

  $(function () {
    var config = window.BEAUTYCORE_STAGE4_ADMIN || {};
    var modal = $("#beautycore-stage4-modal");
    var modalBody = $("#beautycore-stage4-modal-body");
    var filterTimer;
    var mediaFrame;

    function submitFilters(form) {
      window.clearTimeout(filterTimer);
      if (typeof form.requestSubmit === "function") {
        form.requestSubmit();
      } else {
        form.submit();
      }
    }

    $(document).on("change", "[data-beautycore-stage4-filter] select", function () {
      submitFilters(this.form);
    });

    $(document).on("input", '[data-beautycore-stage4-filter] input[type="search"]', function () {
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
      modal.removeClass("is-open").prop("hidden", true).attr("aria-hidden", "true");
      $("body").removeClass("beautycore-modal-open");
      modalBody.empty();
    }

    function openModal(entity, objectId, fallbackUrl) {
      if (!modal.length) {
        window.location.href = fallbackUrl;
        return;
      }

      var isEdit = Number(objectId) > 0;
      var entityLabel = entity === "staff" ? "nhân viên" : "chi nhánh";
      modal.removeAttr("hidden").addClass("is-open").attr("aria-hidden", "false");
      $("body").addClass("beautycore-modal-open");
      modalBody.html('<p class="beautycore-modal-loading">Đang tải biểu mẫu...</p>');
      $("#beautycore-stage4-modal-title").text((isEdit ? "Sửa " : "Thêm ") + entityLabel);

      $.get(config.ajaxUrl, {
        action: "beautycore_stage4_form",
        entity: entity,
        object_id: objectId || 0,
        _ajax_nonce: config.nonce,
      })
        .done(function (response) {
          if (!response || !response.success) {
            modalBody.html('<div class="notice notice-error"><p>Không thể tải biểu mẫu.</p><p><a class="button" href="' + fallbackUrl + '">Mở biểu mẫu riêng</a></p></div>');
            return;
          }
          modalBody.html(response.data);
        })
        .fail(function () {
          modalBody.html('<div class="notice notice-error"><p>Không thể kết nối đến máy chủ.</p><p><a class="button" href="' + fallbackUrl + '">Mở biểu mẫu riêng</a></p></div>');
        });
    }

    $(document).on("click", ".beautycore-stage4-open", function (event) {
      event.preventDefault();
      openModal($(this).data("entity"), $(this).data("object-id") || 0, this.href);
    });

    $(document).on("click", "[data-beautycore-stage4-close]", function (event) {
      closeModal(event);
    });

    $(document).on("keydown", function (event) {
      if (event.key === "Escape" && modal.hasClass("is-open")) {
        closeModal();
      }
    });

    $(document).on("click", "[data-beautycore-select-staff-image]", function (event) {
      event.preventDefault();
      if (mediaFrame) {
        mediaFrame.open();
        return;
      }
      mediaFrame = wp.media({
        title: "Chọn ảnh nhân viên",
        button: { text: "Dùng ảnh này" },
        multiple: false,
        library: { type: "image" },
      });
      mediaFrame.on("select", function () {
        var attachment = mediaFrame.state().get("selection").first().toJSON();
        $("#beautycore-staff-image-id").val(attachment.id);
        $(".beautycore-stage4-image-preview").html('<img src="' + attachment.url + '" alt="">');
      });
      mediaFrame.open();
    });

    $(document).on("click", "[data-beautycore-remove-staff-image]", function (event) {
      event.preventDefault();
      $("#beautycore-staff-image-id").val("0");
      $(".beautycore-stage4-image-preview").html("<span>Chưa chọn ảnh</span>");
    });

    $(document).on("click", "[data-beautycore-add-special]", function (event) {
      event.preventDefault();
      var container = $(this).closest(".beautycore-special-schedule");
      var template = container.find("template[data-beautycore-special-template]").html() || "";
      var rows = container.find("[data-beautycore-special-rows]");
      var nextIndex = Number(rows.data("next-index"));
      if (!Number.isFinite(nextIndex)) {
        nextIndex = rows.find("[data-beautycore-special-row]").length;
      }
      rows.append(template.replace(/__INDEX__/g, String(nextIndex)));
      rows.data("next-index", nextIndex + 1);
    });

    $(document).on("click", "[data-beautycore-remove-special]", function (event) {
      event.preventDefault();
      $(this).closest("[data-beautycore-special-row]").remove();
    });

    $(document).on("change", '[data-beautycore-special-row] select[name*="[status]"]', function () {
      var row = $(this).closest("[data-beautycore-special-row]");
      var disabled = this.value === "closed";
      row.find('input[type="time"]').prop("disabled", disabled);
    });
  });
})(jQuery);
