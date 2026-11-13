import app from '../../forum/app';
import Component, { type ComponentAttrs } from '../../common/Component';
import formatNumber from '../../common/utils/formatNumber';
import ScrollListener from '../../common/utils/ScrollListener';
import ItemList from '../../common/utils/ItemList';
import classList from '../../common/utils/classList';
import Icon from '../../common/components/Icon';
import Button from '../../common/components/Button';
import type Mithril from 'mithril';
import type PostStreamState from '../states/PostStreamState';

export interface IPostStreamScrubberAttrs extends ComponentAttrs {
  stream: PostStreamState;
  className?: string;
}

export interface PostStreamScrubberUpdateOptions {
  fromScroll?: boolean;
  forceHeightChange?: boolean;
  animate?: boolean;
}

export interface PostStreamScrubberPercentPerPost {
  /**
   * Per post outside the handle.
   */
  index: number;
  /**
   * Per post inside the handle, which is larger while the handle is held at
   * its minimum height.
   */
  visible: number;
}

/**
 * The parts core sizes or fills in outside of rendering, by class.
 */
const SYNCED_PARTS = [
  'Scrubber-scrollbar',
  'Scrubber-before',
  'Scrubber-handle',
  'Scrubber-after',
  'Scrubber-unread',
  'Scrubber-index',
  'Scrubber-description',
];

/**
 * What core has set on one element outside of rendering, and for which part.
 */
interface SyncedValues {
  part: string;
  styles: Record<string, string>;
  text?: Node | null;
}

/**
 * The vnode that rendered an element, searched for from a rendered tree.
 */
function findElementVnode(vnode: unknown, element: Element): Mithril.Vnode | undefined {
  if (!vnode || typeof vnode !== 'object') return undefined;

  const { tag, dom, children, instance } = vnode as Mithril.VnodeDOM & { instance?: unknown };

  if (typeof tag === 'string' && dom === element) return vnode as Mithril.Vnode;

  for (const child of typeof tag === 'string' ? (Array.isArray(children) ? children : []) : [instance]) {
    const found = findElementVnode(child, element);
    if (found) return found;
  }

  return undefined;
}

/**
 * The style properties a vnode declares, as CSS property names.
 */
function declaredStyleProperties(vnode: Mithril.Vnode | undefined): string[] {
  const style = (vnode?.attrs as { style?: unknown } | undefined)?.style;

  if (typeof style === 'string') {
    return style.split(';').map((declaration) => declaration.split(':')[0].trim().toLowerCase());
  }

  if (style && typeof style === 'object') {
    return Object.keys(style).map((key) => (key.startsWith('--') ? key : key.replace(/[A-Z]/g, (letter) => '-' + letter.toLowerCase())));
  }

  return [];
}

export default class PostStreamScrubber<CustomAttrs extends IPostStreamScrubberAttrs = IPostStreamScrubberAttrs> extends Component<CustomAttrs> {
  /**
   * The smallest height, in pixels, the handle shrinks to in long discussions.
   */
  static minHandleHeight = 50;

  stream!: PostStreamState;
  handlers: {
    onresize?: () => void;
    onmousemove?: (e: JQuery.TriggeredEvent) => void;
    onmouseup?: () => void;
  } = {};
  scrollListener!: ScrollListener;

  dragging = false;
  mouseStart = 0;
  indexStart = 0;
  adjustingHeight = false;
  showing = false;
  closeWatcher?: CloseWatcher;

  /**
   * The synced parts as of the last check, by element.
   */
  protected syncedParts = new Map<Element, string>();

  /**
   * What core has set on each element outside of rendering.
   */
  protected syncedValues = new Map<Element, SyncedValues>();

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.stream = this.attrs.stream;
    this.handlers = {};

    this.scrollListener = new ScrollListener(this.updateScrubberValues.bind(this, { fromScroll: true, forceHeightChange: true }));
  }

  view() {
    return <div className={classList('PostStreamScrubber', 'Dropdown', this.attrs.className)}>{this.viewItems().toArray()}</div>;
  }

  viewItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'toggle',
      <button type="button" className="Button Dropdown-toggle" data-toggle="dropdown">
        {this.viewingText()} <Icon name={'fas fa-sort'} />
      </button>,
      100
    );

    items.add(
      'menu',
      <div className="Dropdown-menu dropdown-menu">
        <div className="Scrubber">{this.scrubberItems().toArray()}</div>
      </div>,
      0
    );

    return items;
  }

  scrubberItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'first',
      <Button className="Scrubber-first Button Button--link" onclick={this.goToFirst.bind(this)} icon="fas fa-angle-double-up">
        {this.firstPostLabel()}
      </Button>,
      100
    );

    items.add('scrollbar', <div className="Scrubber-scrollbar">{this.scrollbarItems().toArray()}</div>, 0);

    items.add(
      'last',
      <Button className="Scrubber-last Button Button--link" onclick={this.goToLast.bind(this)} icon="fas fa-angle-double-down">
        {this.lastPostLabel()}
      </Button>,
      -100
    );

    return items;
  }

  scrollbarItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    const count = this.stream.count();
    const unreadCount = this.stream.discussion.unreadCount();
    const unreadPercent = count ? Math.min(count - this.stream.index, unreadCount) / count : 0;

    const styleUnread = (vnode: Mithril.VnodeDOM<unknown, { oldStyle?: JQuery.PlainObject }>) => {
      const $element = $(vnode.dom);
      const newStyle = {
        top: 100 - unreadPercent * 100 + '%',
        height: unreadPercent * 100 + '%',
        opacity: unreadPercent > 0 ? 1 : 0,
      };

      this.syncValues(vnode.dom, 'Scrubber-unread', { styles: { ...newStyle, opacity: String(newStyle.opacity) } });

      if (vnode.state.oldStyle) {
        $element.stop(true).css(vnode.state.oldStyle).animate(newStyle);
      } else {
        $element.css(newStyle);
      }

      vnode.state.oldStyle = newStyle;
    };

    items.add('before', <div className="Scrubber-before" />, 100);

    items.add(
      'handle',
      <div className="Scrubber-handle">
        <div className="Scrubber-bar" />
        <div className="Scrubber-info">{this.infoItems().toArray()}</div>
      </div>,
      80
    );

    items.add('after', <div className="Scrubber-after" />, 60);

    items.add(
      'unread',
      <div className="Scrubber-unread" oncreate={styleUnread} onupdate={styleUnread}>
        {this.unreadLabel(unreadCount)}
      </div>,
      40
    );

    return items;
  }

  infoItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('viewing', <strong>{this.viewingText()}</strong>, 100);
    items.add('description', <span className="Scrubber-description"></span>, 0);

    return items;
  }

  viewingText(): Mithril.Children {
    const count = this.stream.count();

    return app.translator.trans('core.forum.post_scrubber.viewing_text', {
      count,
      index: <span className="Scrubber-index"></span>,
      formattedCount: <span className="Scrubber-count">{formatNumber(count)}</span>,
    });
  }

  firstPostLabel(): Mithril.Children {
    return app.translator.trans('core.forum.post_scrubber.original_post_link');
  }

  unreadLabel(unreadCount: number): Mithril.Children {
    return app.translator.trans('core.forum.post_scrubber.unread_text', { count: unreadCount });
  }

  lastPostLabel(): Mithril.Children {
    return app.translator.trans('core.forum.post_scrubber.now_link');
  }

  onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onupdate(vnode);

    if (this.stream.forceUpdateScrubber) {
      this.stream.forceUpdateScrubber = false;
      this.stream.loadPromise?.then(() => this.updateScrubberValues({ animate: true, forceHeightChange: true }));
    }

    // Extension items coming and going can make Mithril rebuild those parts,
    // or reuse their elements for other items.
    if (this.reconcileSyncedParts(vnode)) {
      this.onresize();
      this.updateScrubberValues({ forceHeightChange: true });
    }
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    if ('CloseWatcher' in window) {
      this.$().on('shown.bs.dropdown', () => {
        this.closeWatcher?.destroy();
        this.closeWatcher = new CloseWatcher();
        this.closeWatcher.onclose = () => {
          // @ts-expect-error - missing dropdown types
          this.$('.Dropdown-toggle').dropdown('toggle');
        };
      });

      this.$().on('hidden.bs.dropdown', () => {
        this.showing = false;
        this.closeWatcher?.destroy();
        this.closeWatcher = undefined;

        if (this.attrs.onhide) {
          this.attrs.onhide();
        }

        m.redraw();
      });
    }

    $(window)
      .on('resize', (this.handlers.onresize = this.onresize.bind(this)))
      .trigger('resize');

    // Delegated from the root, so they still apply when Mithril rebuilds the
    // track or the handle.
    this.$()
      .on('click', '.Scrubber-scrollbar', this.onclick.bind(this))
      // Keep the browser's own drag and text selection out of the way.
      .on('dragstart mousedown touchstart', '.Scrubber-scrollbar', (e) => e.preventDefault())
      .on('mousedown touchstart', '.Scrubber-handle', this.onmousedown.bind(this))
      // Clicking the handle should not jump like clicking the track does.
      .on('click', '.Scrubber-handle', (e) => e.stopPropagation());

    this.dragging = false;
    this.mouseStart = 0;
    this.indexStart = 0;

    $(document)
      .on('mousemove touchmove', (this.handlers.onmousemove = this.onmousemove.bind(this)))
      .on('mouseup touchend', (this.handlers.onmouseup = this.onmouseup.bind(this)));

    setTimeout(() => this.scrollListener.start());

    this.reconcileSyncedParts(vnode);

    this.stream.loadPromise?.then(() => this.updateScrubberValues({ animate: false, forceHeightChange: true }));
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    this.scrollListener.stop();
    $(window).off('resize', this.handlers.onresize);

    $(document).off('mousemove touchmove', this.handlers.onmousemove).off('mouseup touchend', this.handlers.onmouseup);
  }

  /**
   * Sync the handle and the info text with the stream's `index` and `visible`.
   */
  updateScrubberValues(options: PostStreamScrubberUpdateOptions = {}) {
    const index = this.stream.index;
    const count = this.stream.count();
    const visible = this.stream.visible || 1;
    const percentPerPost = this.percentPerPost();

    const $scrubber = this.$();
    this.syncText('Scrubber-index', formatNumber(this.stream.sanitizeIndex(Math.max(1, index))));
    this.syncText('Scrubber-description', this.stream.description);
    $scrubber.toggleClass('disabled', this.stream.disabled());

    const heights: Record<'before' | 'handle' | 'after', number> = { before: 0, handle: 0, after: 0 };
    heights.before = Math.max(0, percentPerPost.index * Math.min(index - 1, count - visible));
    heights.handle = Math.min(100 - heights.before, percentPerPost.visible * visible);
    heights.after = 100 - heights.before - heights.handle;

    // While the stream is paused the page is being scrolled programmatically,
    // and a running height animation is only interrupted when forced.
    if ((options.fromScroll && this.stream.paused) || (this.adjustingHeight && !options.forceHeightChange)) return;

    const func = options.animate ? 'animate' : 'css';
    this.adjustingHeight = true;
    const animationPromises: JQuery.Promise<JQuery<HTMLElement>>[] = [];
    for (const part in heights) {
      const $part = $scrubber.find(`.Scrubber-${part}`).stop(true, true);
      const style = { height: heights[part as keyof typeof heights] + '%' };
      animationPromises.push((func === 'animate' ? $part.animate(style, 'fast') : $part.css(style)).promise());

      // jQuery sets overflow: hidden while animating, which would clip the
      // handle's bar, as it sits in a negative margin.
      if (func === 'animate') $part.css('overflow', 'visible');

      $part.each((_, element) => {
        this.syncValues(element, `Scrubber-${part}`, { styles: func === 'animate' ? { ...style, overflow: 'visible' } : style });
      });
    }
    Promise.all(animationPromises).then(() => (this.adjustingHeight = false));
  }

  goToFirst() {
    this.stream.goToFirst();
    this.updateScrubberValues({ animate: true, forceHeightChange: true });
  }

  goToLast() {
    this.stream.goToLast();
    this.updateScrubberValues({ animate: true, forceHeightChange: true });
  }

  /**
   * Fill the height of the sidebar without overlapping the footer.
   */
  onresize() {
    const scrubber = this.$();
    const scrollbar = this.$('.Scrubber-scrollbar');

    const maxHeight =
      $(window).height()! -
      scrubber.offset()!.top +
      $(window).scrollTop()! -
      parseInt($('#app').css('padding-bottom'), 10) -
      (scrubber.outerHeight()! - scrollbar.outerHeight()!);

    scrollbar.css('max-height', maxHeight);

    if (Number.isFinite(maxHeight)) {
      scrollbar.each((_, element) => this.syncValues(element, 'Scrubber-scrollbar', { styles: { 'max-height': maxHeight + 'px' } }));
    }
  }

  onmousedown(e: JQuery.TriggeredEvent & { redraw?: boolean }) {
    e.redraw = false;
    this.mouseStart = e.clientY || (e.originalEvent as TouchEvent).touches[0].clientY;
    this.indexStart = this.stream.index;
    this.dragging = true;
    $('body').css('cursor', 'move');
    this.$().toggleClass('dragging', this.dragging);
  }

  onmousemove(e: JQuery.TriggeredEvent) {
    if (!this.dragging) return;

    // Pixels dragged → percent of the track → posts, relative to the drag start.
    const deltaPixels = (e.clientY || (e.originalEvent as TouchEvent).touches[0].clientY) - this.mouseStart;
    const deltaPercent = (deltaPixels / this.$('.Scrubber-scrollbar').outerHeight()!) * 100;
    const percentPerPost = this.percentPerPost().index;
    // A handle that fills the track leaves nothing to scrub through.
    const deltaIndex = percentPerPost ? deltaPercent / percentPerPost || 0 : 0;
    const newIndex = Math.min(this.indexStart + deltaIndex, this.stream.count() - 1);

    this.stream.index = Math.max(0, newIndex);
    this.updateScrubberValues();
  }

  onmouseup() {
    this.$().toggleClass('dragging', this.dragging);
    if (!this.dragging) return;

    const moved = this.stream.index !== this.indexStart;

    this.mouseStart = 0;
    this.indexStart = 0;
    this.dragging = false;
    $('body').css('cursor', '');

    this.$().removeClass('open');

    // Pressing and releasing the handle in place is not a drag.
    if (!moved) return;

    // Also loads the posts if the drag ended in a gap.
    const intIndex = Math.floor(this.stream.index);
    this.stream.goToIndex(intIndex);
  }

  /**
   * Jump so that the handle ends up centred on the click.
   */
  onclick(e: JQuery.TriggeredEvent) {
    const $scrollbar = this.$('.Scrubber-scrollbar');
    const offsetPixels = (e.pageY || (e.originalEvent as TouchEvent).touches[0].pageY) - $scrollbar.offset()!.top + $('body').scrollTop()!;
    let offsetPercent = (offsetPixels / $scrollbar.outerHeight()!) * 100;
    offsetPercent = offsetPercent - parseFloat($scrollbar.find<HTMLElement>('.Scrubber-handle')[0].style.height) / 2;
    let offsetIndex = offsetPercent / this.percentPerPost().index;
    offsetIndex = Math.max(0, Math.min(this.stream.count() - 1, offsetIndex));
    this.stream.goToIndex(Math.floor(offsetIndex));
    this.updateScrubberValues({ animate: true, forceHeightChange: true });

    this.$().removeClass('open');
  }

  /**
   * Take back what core set on elements that no longer play the part it was
   * set for, and report whether any part is now played by a different element.
   */
  protected reconcileSyncedParts(vnode: Mithril.Vnode<CustomAttrs, this>): boolean {
    const parts = new Map<Element, string>();

    SYNCED_PARTS.forEach((part) => this.$(`.${part}`).each((_, element) => void parts.set(element, part)));

    let replaced = parts.size !== this.syncedParts.size;
    parts.forEach((part, element) => {
      if (this.syncedParts.get(element) !== part) replaced = true;
    });

    this.syncedValues.forEach((values, element) => {
      if (parts.get(element) !== values.part) this.releaseValues(element, values, findElementVnode(vnode, element));
    });

    this.syncedParts = parts;

    return replaced;
  }

  /**
   * Remember what core sets on a part outside of rendering.
   *
   * Mithril runs this component's onupdate before its children's, so an element
   * that changed parts has been released before anything is set on it again.
   */
  protected syncValues(element: Element, part: string, values: Omit<SyncedValues, 'part'>) {
    const synced = this.syncedValues.get(element) ?? { part, styles: {} };

    Object.assign(synced.styles, values.styles);
    if ('text' in values) synced.text = values.text;

    this.syncedValues.set(element, synced);
  }

  protected syncText(part: string, text: string) {
    this.$(`.${part}`).each((_, element) => {
      element.textContent = text;
      this.syncValues(element, part, { styles: {}, text: element.firstChild });
    });
  }

  /**
   * Take back the styles and text core set on an element, except for the
   * styles the vnode now rendering it declares, which Mithril has just set.
   */
  protected releaseValues(element: Element, values: SyncedValues, vnode?: Mithril.Vnode) {
    const style = (element as HTMLElement).style;
    const declared = declaredStyleProperties(vnode);

    // Stop core's animations where they are: finishing them would write their
    // targets over the declared styles.
    $(element).stop(true, false);

    Object.keys(values.styles).forEach((property) => {
      if (!declared.includes(property)) style.removeProperty(property);
    });

    if (values.text?.parentNode === element) element.removeChild(values.text);

    this.syncedValues.delete(element);
  }

  /**
   * The share of the track's height, in percent, that each post takes up.
   */
  percentPerPost(): PostStreamScrubberPercentPerPost {
    const count = this.stream.count() || 1;
    const visible = this.stream.visible || 1;

    // Keep the handle at least `minHandleHeight` tall; the posts outside it
    // then share whatever height is left.
    const minHandleHeight = (this.constructor as typeof PostStreamScrubber).minHandleHeight;
    let minPercentVisible = (minHandleHeight / this.$('.Scrubber-scrollbar').outerHeight()!) * 100;

    if (Number.isFinite(minPercentVisible)) minPercentVisible = Math.min(100, minPercentVisible);
    const percentPerVisiblePost = Math.max(100 / count, minPercentVisible / visible);
    const handlePercent = percentPerVisiblePost * visible;

    // With a fractional visible count, rounding can leave the posts outside a
    // full-height handle a sliver of the track either side of zero.
    const fillsTrack = count === visible || (Number.isFinite(handlePercent) && handlePercent > 100 - 1e-9);
    const percentPerPost = fillsTrack ? 0 : (100 - handlePercent) / (count - visible);

    return {
      index: percentPerPost,
      visible: percentPerVisiblePost,
    };
  }
}
