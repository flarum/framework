import Component from "../../common/Component";

export default class UserPageSidebar extends Component {
  view(vnode) {
    return (
      <nav className="sideNav UserPage-nav">
        <ul>{vnode.children}</ul>
      </nav>)
  }

  onresize(vnode) {
    const $sidebar = $(vnode.dom);
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
  };

  oncreate(vnode) {
    super.oncreate(vnode);

    // Register the affix plugin to execute on every window resize (and trigger)
    $(window).on('resize', this.onresize.bind(this, vnode)).resize();
  }

  onremove(vnode) {
    $(window).off('resize', this.onresize.bind(this, vnode));
  }
}
