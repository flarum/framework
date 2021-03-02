import Component from '../../common/Component';

/**
 * The `AffixedSidebar` component uses Bootstrap's "affix" plugin to keep a
 * sidebar navigation at the top of the viewport when scrolling.
 *
 * ### Children
 *
 * The component must wrap an element that itself wraps an <ul> element, which
 * will be "affixed".
 *
 * @see https://getbootstrap.com/docs/3.4/javascript/#affix
 */
export default class AffixedSidebar extends Component {
  view(vnode) {
    return vnode.children[0];
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    // Register the affix plugin to execute on every window resize (and trigger)
    this.boundOnresize = this.onresize.bind(this);
    $(window).on('resize', this.boundOnresize).resize();
  }

  onremove() {
    $(window).off('resize', this.boundOnresize);
  }

  onresize() {
    const $sidebar = this.$();
    const $header = $('#header');
    const $footer = $('#footer');
    const $affixElement = $sidebar.find('> ul');

    $(window).off('.affix');
    $affixElement.removeClass('affix affix-top affix-bottom').removeData('bs.affix');

    // Don't affix the sidebar if it is taller than the viewport (otherwise
    // there would be no way to scroll through its content).
    if ($sidebar.outerHeight(true) > $(window).height() - $header.outerHeight(true)) return;

    $affixElement.affix({
      offset: {
        top: () => $sidebar.offset().top - $header.outerHeight(true) - parseInt($sidebar.css('margin-top'), 10),
        bottom: () => (this.bottom = $footer.outerHeight(true)),
      },
    });
  }
}
