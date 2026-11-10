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
  onhide?: () => void;
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

    function styleUnread(vnode: Mithril.VnodeDOM<unknown, { oldStyle?: JQuery.PlainObject }>) {
      const $element = $(vnode.dom);
      const newStyle = {
        top: 100 - unreadPercent * 100 + '%',
        height: unreadPercent * 100 + '%',
        opacity: unreadPercent > 0 ? 1 : 0,
      };

      if (vnode.state.oldStyle) {
        $element.stop(true).css(vnode.state.oldStyle).animate(newStyle);
      } else {
        $element.css(newStyle);
      }

      vnode.state.oldStyle = newStyle;
    }

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
    }

    this.$().on('hidden.bs.dropdown', () => {
      this.showing = false;
      this.closeWatcher?.destroy();
      this.closeWatcher = undefined;

      if (this.attrs.onhide) {
        this.attrs.onhide();
      }

      m.redraw();
    });

    $(window)
      .on('resize', (this.handlers.onresize = this.onresize.bind(this)))
      .trigger('resize');

    this.$('.Scrubber-scrollbar')
      .on('click', this.onclick.bind(this))
      // Keep the browser's own drag and text selection out of the way.
      .on('dragstart mousedown touchstart', (e) => e.preventDefault());

    this.dragging = false;
    this.mouseStart = 0;
    this.indexStart = 0;

    this.$('.Scrubber-handle')
      .on('mousedown touchstart', this.onmousedown.bind(this))
      // Clicking the handle should not jump like clicking the track does.
      .on('click', (e) => e.stopPropagation());

    $(document)
      .on('mousemove touchmove', (this.handlers.onmousemove = this.onmousemove.bind(this)))
      .on('mouseup touchend', (this.handlers.onmouseup = this.onmouseup.bind(this)));

    setTimeout(() => this.scrollListener.start());

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
    $scrubber.find('.Scrubber-index').text(formatNumber(this.stream.sanitizeIndex(Math.max(1, index))));
    $scrubber.find('.Scrubber-description').text(this.stream.description);
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

    scrollbar.css(
      'max-height',
      $(window).height()! -
        scrubber.offset()!.top +
        $(window).scrollTop()! -
        parseInt($('#app').css('padding-bottom'), 10) -
        (scrubber.outerHeight()! - scrollbar.outerHeight()!)
    );
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
    const deltaIndex = deltaPercent / this.percentPerPost().index || 0;
    const newIndex = Math.min(this.indexStart + deltaIndex, this.stream.count() - 1);

    this.stream.index = Math.max(0, newIndex);
    this.updateScrubberValues();
  }

  onmouseup() {
    this.$().toggleClass('dragging', this.dragging);
    if (!this.dragging) return;

    this.mouseStart = 0;
    this.indexStart = 0;
    this.dragging = false;
    $('body').css('cursor', '');

    this.$().removeClass('open');

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
   * The share of the track's height, in percent, that each post takes up.
   */
  percentPerPost(): PostStreamScrubberPercentPerPost {
    const count = this.stream.count() || 1;
    const visible = this.stream.visible || 1;

    // Keep the handle at least `minHandleHeight` tall; the posts outside it
    // then share whatever height is left.
    const minHandleHeight = (this.constructor as typeof PostStreamScrubber).minHandleHeight;
    const minPercentVisible = (minHandleHeight / this.$('.Scrubber-scrollbar').outerHeight()!) * 100;
    const percentPerVisiblePost = Math.max(100 / count, minPercentVisible / visible);
    const percentPerPost = count === visible ? 0 : (100 - percentPerVisiblePost * visible) / (count - visible);

    return {
      index: percentPerPost,
      visible: percentPerVisiblePost,
    };
  }
}
