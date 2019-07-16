['width', 'height'].forEach(function(dimension) {
  const Dimension = dimension.replace(/./, function (m) {
    return m[0].toUpperCase()
  });

  $.fn[`outer${Dimension}`] = function(margin) {
    const elem = this;

    if (elem) {
      const sides = {'width': ['left', 'right'], 'height': ['top', 'bottom']};
      let size = elem[dimension]();

      sides[dimension].forEach(function(side) {
        if (margin) size += parseInt(elem.css('margin-' + side), 10);
      });

      return size;
    } else {
      return null;
    }
  };
});

$.fn.extend = $.extend.bind($);
