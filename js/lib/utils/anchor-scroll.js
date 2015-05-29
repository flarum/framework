export default function anchorScroll(element, callback) {
  var scrollAnchor = $(element).offset().top - $(window).scrollTop();

  callback();

  $(window).scrollTop($(element).offset().top - scrollAnchor);
}
