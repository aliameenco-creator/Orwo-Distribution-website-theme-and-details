/* Film details: choose/remove the banner and homepage tile images from the Media Library. */
jQuery(function ($) {
  $('.orwod-image-field').each(function () {
    const box = $(this);
    const input = box.find('input[type=hidden]');
    const preview = box.find('.orwod-image-preview');
    const remove = box.find('.orwod-image-remove');
    let frame;
    box.find('.orwod-image-choose').on('click', function (e) {
      e.preventDefault();
      if (!frame) {
        frame = wp.media({ title: 'Choose image', library: { type: 'image' }, button: { text: 'Use this image' }, multiple: false });
        frame.on('select', function () {
          const img = frame.state().get('selection').first().toJSON();
          input.val(img.id);
          const url = (img.sizes && img.sizes.medium ? img.sizes.medium.url : img.url);
          preview.empty().append($('<img>', { src: url, alt: '' }).css({ maxWidth: '100%', height: 'auto' }));
          remove.prop('hidden', false);
        });
      }
      frame.open();
    });
    remove.on('click', function (e) {
      e.preventDefault();
      input.val('');
      preview.empty();
      remove.prop('hidden', true);
    });
  });
});
