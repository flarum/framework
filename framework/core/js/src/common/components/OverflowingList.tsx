import Component, { ComponentAttrs } from '../Component';
import Dropdown from './Dropdown';
import listItems, { ModdedChildrenWithItemName } from '../helpers/listItems';
import classList from '../utils/classList';
import countItemsThatFit from '../utils/countItemsThatFit';
import extractText from '../utils/extractText';
import app from '../app';
import type Mithril from 'mithril';

export interface IOverflowingListAttrs extends ComponentAttrs {
  /** The items to lay out, as returned by `ItemList.toArray()`. */
  items: ModdedChildrenWithItemName[];
  /** A class name to apply to the list element. */
  className?: string;
  /** The label used to describe the overflow menu to assistive readers. */
  accessibleToggleLabel?: string;
}

/**
 * Lays out a list of items on a single row, moving those that do not fit into
 * a dropdown at the end of the row.
 *
 * The list is rendered in full and measured after each paint, so item widths
 * come from the real laid-out DOM rather than an estimate. That keeps the
 * result correct whatever an item happens to contain — an icon, a long label,
 * a translated string, or something an extension added.
 */
export default class OverflowingList extends Component<IOverflowingListAttrs> {
  /**
   * How many leading items are shown in the row. `null` means "not measured
   * yet", during which everything renders so the widths can be read.
   */
  protected visibleCount: number | null = null;

  /**
   * Width of each item, by index, captured while all of them were on the row.
   * Measuring once and reusing avoids feeding the widths of an already
   * collapsed row back into the next calculation.
   */
  protected itemWidths: number[] = [];

  protected toggleWidth = 0;

  protected observer?: ResizeObserver;

  protected onWindowResize?: () => void;

  view(vnode: Mithril.Vnode<IOverflowingListAttrs, this>) {
    const items = this.attrs.items || [];
    const count = this.visibleCount ?? items.length;
    const visible = items.slice(0, count);
    const overflowed = items.slice(count);

    return (
      <ul className={classList('OverflowingList', this.attrs.className)}>
        {listItems(visible)}
        {overflowed.length > 0 && (
          <li className="OverflowingList-toggle" itemName="overflow">
            <Dropdown
              className="OverflowingList-dropdown"
              buttonClassName="Button Button--link"
              menuClassName="Dropdown-menu--right"
              icon="fas fa-ellipsis-h"
              // The ellipsis already says "there is more here"; the caret a
              // dropdown adds by default only doubles up on that. An empty
              // string rather than null, since the default is applied with
              // `??=` and would overwrite a nullish value.
              caretIcon=""
              accessibleToggleLabel={
                this.attrs.accessibleToggleLabel ?? extractText(app.translator.trans('core.lib.overflowing_list.toggle_accessible_label'))
              }
            >
              {overflowed}
            </Dropdown>
          </li>
        )}
      </ul>
    );
  }

  oncreate(vnode: Mithril.VnodeDOM<IOverflowingListAttrs, this>) {
    super.oncreate(vnode);

    // Watch the container rather than the row itself. The row's own width is
    // a *result* of collapsing — observing it would mean reacting to this
    // component's own output and never seeing the space open back up. The
    // container tracks the viewport, and its other children (a logo, the
    // controls opposite) can change width with no resize event of their own.
    //
    // Where there is no ResizeObserver the row still works: it lays out on
    // creation and on window resizes, and only misses changes that resize a
    // sibling without resizing the window.
    if (typeof ResizeObserver !== 'undefined') {
      this.observer = new ResizeObserver(() => this.recalculate());

      const list = this.element as HTMLElement;
      const container = list.parentElement?.parentElement ?? list.parentElement ?? list;
      this.observer.observe(container);
    }

    // Sibling controls can change width without the container resizing at all
    // — a label dropping at a breakpoint, a badge appearing. Nothing reports
    // that, so the row also re-checks on viewport changes.
    this.onWindowResize = () => this.recalculate();
    window.addEventListener('resize', this.onWindowResize);
    window.addEventListener('orientationchange', this.onWindowResize);

    this.recalculate();
  }

  onupdate(vnode: Mithril.VnodeDOM<IOverflowingListAttrs, this>) {
    super.onupdate(vnode);

    this.recalculate();
  }

  onremove(vnode: Mithril.VnodeDOM<IOverflowingListAttrs, this>) {
    super.onremove(vnode);

    this.observer?.disconnect();

    if (this.onWindowResize) {
      window.removeEventListener('resize', this.onWindowResize);
      window.removeEventListener('orientationchange', this.onWindowResize);
    }
  }

  /**
   * Work out how many items fit, and redraw if that has changed.
   */
  protected recalculate(): void {
    const list = this.element as HTMLElement;
    if (!list) return;

    const children = Array.from(list.children) as HTMLElement[];
    const itemCount = (this.attrs.items || []).length;

    // Nothing can be measured before the row is in a document, so leave every
    // item showing until it is.
    if (!list.isConnected) return;

    // Collapsing only makes sense while the items share a single line. In the
    // drawer the same list is laid out as a stack of block-level entries with a
    // whole screen height to grow into, so there is nothing to save and
    // everything should simply render.
    //
    // What decides that is whether the list lays its items out in a row at all,
    // which means the display type: `flex-direction` is reported as `row` on
    // everything, flex container or not, so it cannot answer this on its own.
    const style = getComputedStyle(list);
    const laysOutInARow = (style.display === 'flex' || style.display === 'inline-flex') && style.flexDirection.startsWith('row');

    // A hidden row measures zero, and zero available space would collapse
    // every item. Leave the count alone until it can be measured for real.
    if (!laysOutInARow || !list.clientWidth) {
      if (!laysOutInARow && this.visibleCount !== itemCount) {
        this.visibleCount = itemCount;
        m.redraw();
      }
      return;
    }

    // Widths are read whenever every item is on the row, which is the only
    // time they are all measurable. Re-reading rather than trusting the first
    // measurement matters because what an item is worth changes underneath
    // this: a label hidden at a breakpoint, a font that finishes loading, a
    // count appearing on a badge.
    if (this.visibleCount === null || this.visibleCount === itemCount) {
      const measured = children.slice(0, itemCount).map((child) => this.outerWidth(child));
      if (measured.length === itemCount) this.itemWidths = measured;
      // Nothing has overflowed, so the toggle is not in the DOM to measure.
      // Reserve a conservative width for it; it is replaced with the real
      // figure as soon as the toggle renders.
      if (!this.toggleWidth) this.toggleWidth = 48;
    } else {
      const toggle = list.querySelector('.OverflowingList-toggle') as HTMLElement | null;
      if (toggle) this.toggleWidth = this.outerWidth(toggle);
    }

    const available = this.availableWidth(list);
    if (!available || this.itemWidths.length !== itemCount) return;

    // Decided from the measured widths and the container's width — never from
    // the row's current width, which is a consequence of the last decision and
    // would turn this into a feedback loop.
    const fits = countItemsThatFit(this.itemWidths, available, this.toggleWidth);

    if (fits !== this.visibleCount) {
      this.visibleCount = fits;
      m.redraw();
    }
  }

  /**
   * How much room the row could occupy, rather than how much it currently
   * does.
   *
   * The list sits in a flex parent that shrink-wraps its contents, so once
   * items have been collapsed the element reports the narrower width it took
   * *because* of the collapse. Measuring that would be circular — the row
   * could never grow back. What matters is the gap to whatever sits beside it.
   */
  protected availableWidth(list: HTMLElement): number {
    const parent = list.parentElement;
    if (!parent) return list.clientWidth;

    const container = parent.parentElement;
    if (!container) return parent.clientWidth;

    const parentBox = parent.getBoundingClientRect();
    const containerStyle = getComputedStyle(container);
    const containerBox = container.getBoundingClientRect();

    // Start from the container's inner edge, then walk the siblings and take
    // out whatever they occupy along with the gaps between them.
    let free = containerBox.width - parseFloat(containerStyle.paddingLeft || '0') - parseFloat(containerStyle.paddingRight || '0');

    const gap = parseFloat(containerStyle.columnGap || containerStyle.gap || '0') || 0;
    let siblings = 0;

    Array.from(container.children).forEach((child) => {
      if (child === parent) return;
      siblings++;
      free -= (child as HTMLElement).getBoundingClientRect().width;
    });

    free -= gap * siblings;

    // Anything the row's own parent adds around it — padding, a border, a
    // margin — is not usable by the items themselves.
    free -= parentBox.width - parent.clientWidth;

    return Math.max(0, Math.floor(free));
  }

  protected outerWidth(el: HTMLElement): number {
    const style = getComputedStyle(el);

    return el.getBoundingClientRect().width + parseFloat(style.marginLeft || '0') + parseFloat(style.marginRight || '0');
  }
}
