(function ($) {
  'use strict';

  $(function () {
    var frame;
    $('#beautycore-select-image').on('click', function (event) {
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

    $('#beautycore-remove-image').on('click', function (event) {
      event.preventDefault();
      $('#beautycore-image-id').val('0');
      $('#image_url').val('');
      $('#beautycore-image-preview').html('<span>Chưa chọn ảnh</span>');
    });
  });
})(jQuery);
