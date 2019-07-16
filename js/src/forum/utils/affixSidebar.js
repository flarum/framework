/**
 * Setup the sidebar DOM element to be affixed to the top of the viewport
 * using Bootstrap's affix plugin.
 *
 * @param {DOMElement} element
 * @param {Boolean} isInitialized
 * @param {Object} context
 */
export default function affixSidebar(element, isInitialized) {
  if (isInitialized) return;

  const $sidebar = $(element);
  const $header = $('#header');
  const $affixElement = $sidebar.find('> ul')[0];

  $(window).off('.affix');

  new hcSticky($affixElement, {
    stickTo: element,
    top: $header.outerHeight(true) + parseInt($sidebar.css('margin-top'), 10),
  });
}
