import Component from '../../common/Component';
import heightWithMargin from '../../common/utils/heightWithMargin';

/**
 * The `AffixedSidebar` component uses sticky position to keep a
 * sidebar navigation at the top of the viewport when scrolling.
 *
 * ### Children
 *
 * The component must wrap an element that itself wraps an <ul> element, which
 * will be "affixed".
 */
export default class AffixedSidebar extends Component {
  view(vnode) {
    return vnode.children[0];
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    // Register the affix to execute on every window resize (and trigger)
    this.boundOnresize = this.onresize.bind(this);
    window.addEventListener('resize', this.boundOnresize);
    window.dispatchEvent(new Event('resize'));
  }

  onremove(vnode) {
    super.onremove(vnode);

    window.removeEventListener('resize', this.boundOnresize);
  }

  onresize() {
    const header = document.getElementById('header');
    const affixElement = this.element.querySelector(':scope > ul');
    const pageSidebar = this.element.closest('.Page-sidebar');

    // Don't affix the sidebar if it is taller than the viewport (otherwise
    // there would be no way to scroll through its content).
    const enabled = heightWithMargin(this.element) <= window.innerHeight - heightWithMargin(header);
    affixElement.classList.toggle('affix', enabled);
    if (enabled) {
      const top = heightWithMargin(header) + parseInt(getComputedStyle(pageSidebar ?? this.element).marginTop, 10);
      affixElement.style.position = 'sticky';
      affixElement.style.top = top + 'px';
      this.element.style.display = 'initial'; // Workaround for sticky not working
    } else {
      affixElement.style.position = '';
      affixElement.style.top = '';
      this.element.style.display = '';
    }
  }
}
