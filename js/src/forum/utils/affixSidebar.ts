/**
 * Setup the sidebar DOM element to be affixed to the top of the viewport
 * using hcSticky.
 */
export default function affixSidebar(vnode) {
    const element = vnode.dom;
    const $sidebar = $(element);
    const $header = $('#header');
    const $affixElement = $sidebar.find('> ul')[0];

    $(window).off('.affix');

    new hcSticky($affixElement, {
        stickTo: element,
        top: $header.outerHeight(true) + parseInt($sidebar.css('margin-top'), 10),
    });
}
