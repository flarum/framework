/**
 * Setup the sidebar DOM element to be affixed to the top of the viewport
 * using Bootstrap's affix plugin.
 *
 * @param {DOMElement} element
 * @param {Boolean} isInitialized
 */
export default function affixSidebar(element, isInitialized) {
  if (isInitialized) return;

  const $sidebar = $(element);
  const $header = $('#header');
  const $footer = $('#footer');

  // Don't affix the sidebar if it is taller than the viewport (otherwise
  // there would be no way to scroll through its content).
  if ($sidebar.outerHeight(true) > $(window).height() - $header.outerHeight(true)) return;

  $sidebar.find('> ul').affix({
    offset: {
      top: () => $sidebar.offset().top - $header.outerHeight(true) - parseInt($sidebar.css('margin-top'), 10),
      bottom: () => this.bottom = $footer.outerHeight(true)
    }
  });
}
