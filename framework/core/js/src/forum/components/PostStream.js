import app from '../../forum/app';
import Component from '../../common/Component';
import ScrollListener from '../../common/utils/ScrollListener';
import LoadingPost from './LoadingPost';
import ReplyPlaceholder from './ReplyPlaceholder';
import Button from '../../common/components/Button';
import ItemList from '../../common/utils/ItemList';
import PostType from './PostType';

/**
 * The `PostStream` component displays an infinitely-scrollable wall of posts in
 * a discussion. Posts that have not loaded will be displayed as placeholders.
 *
 * ### Attrs
 *
 * - `discussion`
 * - `stream`
 * - `targetPost`
 * - `onPositionChange`
 */
export default class PostStream extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.discussion = this.attrs.discussion;
    this.stream = this.attrs.stream;

    this.scrollListener = new ScrollListener(this.onscroll.bind(this));
  }

  view() {
    let lastTime;

    const viewingEnd = this.stream.viewingEnd();
    const posts = this.stream.posts();
    const postIds = this.discussion.postIds();

    const postFadeIn = (vnode) => {
      $(vnode.dom).addClass('fadeIn');
      // 500 is the duration of the fadeIn CSS animation + 100ms,
      // so the animation has time to complete
      setTimeout(() => $(vnode.dom).removeClass('fadeIn'), 500);
    };

    const items = posts.map((post, i) => {
      let content;
      const attrs = { 'data-index': this.stream.visibleStart + i };

      if (post) {
        const time = post.createdAt();
        content = <PostType post={post} />;

        attrs.key = 'post' + post.id();
        attrs.oncreate = postFadeIn;
        attrs['data-time'] = time.toISOString();
        attrs['data-number'] = post.number();
        attrs['data-id'] = post.id();
        attrs['data-type'] = post.contentType();

        // If the post before this one was more than 4 days ago, we will
        // display a 'time gap' indicating how long it has been in between
        // the posts.
        const dt = time - lastTime;

        if (dt > 1000 * 60 * 60 * 24 * 4) {
          content = [
            <div className="PostStream-timeGap">
              <span>{app.translator.trans('core.forum.post_stream.time_lapsed_text', { period: dayjs().add(dt, 'ms').fromNow(true) })}</span>
            </div>,
            content,
          ];
        }

        lastTime = time;
      } else {
        attrs.key = 'post' + postIds[this.stream.visibleStart + i];

        content = <LoadingPost />;
      }

      const postStreamElement = (
        <div className="PostStream-item" {...attrs}>
          {content}
        </div>
      );

      const afterPostItems = post && post.id() === this.discussion.data.relationships.firstPost?.data?.id ? this.afterFirstPostItems().toArray() : [];

      if (afterPostItems.length > 0) {
        return m.fragment({ ...attrs }, [
          postStreamElement,
          <div className="PostStream-item PostStream-afterFirstPost" key="afterFirstPost">
            {afterPostItems}
          </div>,
        ]);
      }

      return postStreamElement;
    });

    if (!viewingEnd && posts[this.stream.visibleEnd - this.stream.visibleStart - 1]) {
      items.push(
        <div className="PostStream-loadMore" key="loadMore">
          <Button className="Button" onclick={this.stream.loadNext.bind(this.stream)}>
            {app.translator.trans('core.forum.post_stream.load_more_button')}
          </Button>
        </div>
      );
    }

    // Allow extensions to add items to the end of the post stream.
    if (viewingEnd) {
      items.push(...this.endItems().toArray());
    }

    // If we're viewing the end of the discussion, the user can reply, and
    // is not already doing so, then show a 'write a reply' placeholder.
    if (viewingEnd && (!app.session.user || this.discussion.canReply())) {
      items.push(
        <div className="PostStream-item" key="reply" data-index={this.stream.count()} oncreate={postFadeIn}>
          <ReplyPlaceholder discussion={this.discussion} />
        </div>
      );
    }

    return (
      <div className="PostStream" role="feed" aria-live="off" aria-busy={this.stream.pagesLoading ? 'true' : 'false'}>
        {items}
      </div>
    );
  }

  /**
   * @returns {ItemList<import('mithril').Children>}
   */
  afterFirstPostItems() {
    const items = new ItemList();

    return items;
  }

  /**
   * @returns {ItemList<import('mithril').Children>}
   */
  endItems() {
    const items = new ItemList();

    return items;
  }

  onupdate(vnode) {
    super.onupdate(vnode);

    this.triggerScroll();
    this.observeNewPostItems();
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.triggerScroll();
    this.setupScrollAnchoring();

    // This is wrapped in setTimeout due to the following Mithril issue:
    // https://github.com/lhorie/mithril.js/issues/637
    setTimeout(() => this.scrollListener.start());
  }

  onremove(vnode) {
    super.onremove(vnode);

    this.scrollListener.stop();
    this.cleanupScrollAnchoring();
    clearTimeout(this.calculatePositionTimeout);
  }

  /**
   * Start scrolling, if appropriate, to a newly-targeted post.
   */
  triggerScroll() {
    if (!this.stream.needsScroll) return;

    const target = this.stream.targetPost;
    this.stream.needsScroll = false;

    if ('number' in target) {
      this.scrollToNumber(target.number, this.stream.animateScroll);
    } else if ('index' in target) {
      this.scrollToIndex(target.index, this.stream.animateScroll, target.reply);
    }
  }

  /**
   *
   * @param {number} top
   */
  onscroll(top = window.scrollY) {
    if (this.stream.paused || this.stream.pagesLoading) return;

    this.updateScrubber(top);

    this.loadPostsIfNeeded(top);

    // While the page is settling after a programmatic scroll, layout shifts
    // are still happening (images loading, etc). Skip position/URL updates
    // until settling completes -- endSettling() will call calculatePosition().
    if (this.settling) return;

    // Throttle calculation of our position (start/end numbers of posts in the
    // viewport) to 100ms.
    clearTimeout(this.calculatePositionTimeout);
    this.calculatePositionTimeout = setTimeout(this.calculatePosition.bind(this, top), 100);
  }

  /**
   * Check if either extreme of the post stream is in the viewport,
   * and if so, trigger loading the next/previous page.
   *
   * @param {number} top
   */
  loadPostsIfNeeded(top = window.scrollY) {
    const marginTop = this.getMarginTop();
    const viewportHeight = $(window).height() - marginTop;
    const viewportTop = top + marginTop;
    const loadAheadDistance = 300;

    if (this.stream.visibleStart > 0) {
      const $item = this.$('.PostStream-item[data-index=' + this.stream.visibleStart + ']');

      if ($item.length && $item.offset().top > viewportTop - loadAheadDistance) {
        this.stream.loadPrevious();
      }
    }

    if (this.stream.visibleEnd < this.stream.count()) {
      const $item = this.$('.PostStream-item[data-index=' + (this.stream.visibleEnd - 1) + ']');

      if ($item.length && $item.offset().top + $item.outerHeight(true) < viewportTop + viewportHeight + loadAheadDistance) {
        this.stream.loadNext();
      }
    }
  }

  updateScrubber(top = window.scrollY) {
    const marginTop = this.getMarginTop();
    const viewportHeight = $(window).height() - marginTop;
    const viewportTop = top + marginTop;

    // Before looping through all of the posts, we reset the scrollbar
    // properties to a 'default' state. These values reflect what would be
    // seen if the browser were scrolled right up to the top of the page,
    // and the viewport had a height of 0.
    const $items = this.$('.PostStream-item[data-index]');
    let visible = 0;
    let period = '';
    let indexFromViewPort = null;

    // Now loop through each of the items in the discussion. An 'item' is
    // either a single post or a 'gap' of one or more posts that haven't
    // been loaded yet.
    $items.each(function () {
      const $this = $(this);
      const top = $this.offset().top;
      const height = $this.outerHeight(true);

      // If this item is above the top of the viewport, skip to the next
      // one. If it's below the bottom of the viewport, break out of the
      // loop.
      if (top + height < viewportTop) {
        return true;
      }
      if (top > viewportTop + viewportHeight) {
        return false;
      }

      // Work out how many pixels of this item are visible inside the viewport.
      // Then add the proportion of this item's total height to the index.
      const visibleTop = Math.max(0, viewportTop - top);
      const visibleBottom = Math.min(height, viewportTop + viewportHeight - top);
      const visiblePost = visibleBottom - visibleTop;

      // We take the index of the first item that passed the previous checks.
      // It is the item that is first visible in the viewport.
      if (indexFromViewPort === null) {
        indexFromViewPort = parseFloat($this.data('index')) + visibleTop / height;
      }

      if (visiblePost > 0) {
        visible += visiblePost / height;
      }

      // If this item has a time associated with it, then set the
      // scrollbar's current period to a formatted version of this time.
      const time = $this.data('time');
      if (time) period = time;
    });

    // If indexFromViewPort is null, it means no posts are visible in the
    // viewport. This can happen, when drafting a long reply post. In that case
    // set the index to the last post.
    this.stream.index = indexFromViewPort !== null ? indexFromViewPort + 1 : this.stream.count();
    this.stream.visible = visible;
    if (period) this.stream.description = app.translator.formatDateTime(dayjs(period), 'core.lib.datetime_formats.scrubber');
  }

  /**
   * Compute which post numbers are currently visible in the viewport, without
   * emitting anything. Pure calculation.
   *
   * @param {number} top
   * @returns {{ startNumber: number|undefined, endNumber: number|undefined }}
   */
  computeVisiblePosition(top = window.scrollY) {
    const marginTop = this.getMarginTop();
    const $window = $(window);
    const viewportHeight = $window.height() - marginTop;
    const scrollTop = $window.scrollTop() + marginTop;
    const viewportTop = top + marginTop;

    let startNumber;
    let endNumber;

    this.$('.PostStream-item').each(function () {
      const $item = $(this);
      const itemTop = $item.offset().top;
      const height = $item.outerHeight(true);
      const visibleTop = Math.max(0, viewportTop - itemTop);

      const threeQuartersVisible = visibleTop / height < 0.75;
      const coversQuarterOfViewport = (height - visibleTop) / viewportHeight > 0.25;
      if (startNumber === undefined && (threeQuartersVisible || coversQuarterOfViewport)) {
        startNumber = $item.data('number');
      }

      if (itemTop + height > scrollTop) {
        if (itemTop + height < scrollTop + viewportHeight) {
          if ($item.data('number')) {
            endNumber = $item.data('number');
          }
        } else return false;
      }
    });

    return { startNumber, endNumber };
  }

  /**
   * Resolve a definitive post number from the current settling target, for use
   * as an immediate-emit fallback when the DOM scan finds no loaded post.
   *
   * Returns undefined when no number can be determined (placeholder targets,
   * reply targets) — callers should skip the emit in that case.
   *
   * @returns {number|undefined}
   */
  resolveTargetStartNumber() {
    const target = this.settlingTarget;
    if (!target) return undefined;

    if ('number' in target) {
      return target.number;
    }

    if ('index' in target && !target.reply) {
      const num = this.$(`.PostStream-item[data-index=${target.index}]`).data('number');
      return num || undefined;
    }

    // { reply: true } — no meaningful post number available
    return undefined;
  }

  /**
   * Emit a position update to the host page. Single path to call onPositionChange.
   *
   * @param {number} startNumber
   * @param {number|undefined} endNumber
   */
  emitPosition(startNumber, endNumber) {
    this.attrs.onPositionChange(startNumber, endNumber, startNumber);
  }

  /**
   * Work out which posts (by number) are currently visible in the viewport, and
   * fire an event with the information.
   *
   * @param {number} top
   * @param {{ fallbackToTarget?: boolean }} options
   *   When `fallbackToTarget` is true and the DOM scan finds no loaded post with
   *   a `data-number`, the settling target's post number is used as the start.
   *   This allows an immediate URL emit right after a programmatic scroll lands,
   *   before async content has finished loading.
   */
  calculatePosition(top = window.scrollY, { fallbackToTarget = false } = {}) {
    let { startNumber, endNumber } = this.computeVisiblePosition(top);

    if (!startNumber && fallbackToTarget) {
      startNumber = this.resolveTargetStartNumber();
    }

    if (startNumber) {
      this.emitPosition(startNumber, endNumber);
    }
  }

  /**
   * Get the distance from the top of the viewport to the point at which we
   * would consider a post to be the first one visible.
   *
   * @return {number}
   */
  getMarginTop() {
    const headerId = app.screen() === 'phone' ? '#app-navigation' : '#header';

    return this.$() && $(headerId).outerHeight() + parseInt(this.$().css('margin-top'), 10);
  }

  /**
   * Scroll down to a certain post by number and 'flash' it.
   *
   * @param {number} number
   * @param {boolean} animate
   * @return {JQueryDeferred}
   */
  scrollToNumber(number, animate) {
    const $item = this.$(`.PostStream-item[data-number=${number}]`);

    return this.scrollToItem($item, animate).then(this.flashItem.bind(this, $item));
  }

  /**
   * Scroll down to a certain post by index.
   *
   * @param {number} index
   * @param {boolean} animate
   * @param {boolean} reply Whether or not to scroll to the reply placeholder.
   * @return {JQueryDeferred}
   */
  scrollToIndex(index, animate, reply) {
    const $item = reply ? $('.PostStream-item:last-child') : this.$(`.PostStream-item[data-index=${index}]`);

    this.scrollToItem($item, animate, true, reply);

    if (reply) {
      this.flashItem($item);
    }
  }

  /**
   * Scroll down to the given post.
   *
   * @param {JQuery} $item
   * @param {boolean} animate
   * @param {boolean} force Whether or not to force scrolling to the item, even
   *     if it is already in the viewport.
   * @param {boolean} reply Whether or not to scroll to the reply placeholder.
   * @return {JQueryDeferred}
   */
  scrollToItem($item, animate, force, reply) {
    const $container = $('html, body').stop(true);
    const index = $item.data('index');

    if ($item.length) {
      const itemTop = $item.offset().top - this.getMarginTop();
      const itemBottom = $item.offset().top + $item.height();
      const scrollTop = $(document).scrollTop();
      const scrollBottom = scrollTop + $(window).height();

      // If the item is already in the viewport, we may not need to scroll.
      // If we're scrolling to the reply placeholder, we'll make sure its
      // bottom will line up with the top of the composer.
      if (force || itemTop < scrollTop || itemBottom > scrollBottom) {
        const top = reply ? itemBottom - $(window).height() + app.composer.computedHeight() : $item.is(':first-child') ? 0 : itemTop;

        if (!animate) {
          $container.scrollTop(top);
        } else if (top !== scrollTop) {
          $container.animate({ scrollTop: top }, 'fast');
        }
      }
    }

    const updateScrubberHeight = () => {
      // We manually set the index because we want to display the index of the
      // exact post we've scrolled to, not just that of the first post within viewport.
      this.updateScrubber();
      if (index !== undefined) this.stream.index = index + 1;
    };

    // If we don't update this before the scroll, the scrubber will start
    // at the top, and animate down, which can be confusing
    updateScrubberHeight();
    this.stream.forceUpdateScrubber = true;

    return Promise.all([$container.promise(), this.stream.loadPromise]).then(() => {
      m.redraw.sync();

      // Rendering post contents will probably throw off our position.
      // To counter this, we'll scroll either:
      //   - To the reply placeholder (aligned with composer top)
      //   - To the top of the page if we're on the first post
      //   - To the top of a post (if that post exists)
      // If the post does not currently exist, it's probably
      // outside of the range we loaded in, so we won't adjust anything,
      // as it will soon be rendered by the "load more" system.
      let itemOffset;
      if (reply) {
        const $placeholder = $('.PostStream-item:last-child');
        $(window).scrollTop($placeholder.offset().top + $placeholder.height() - $(window).height() + app.composer.computedHeight());
      } else if (index === 0) {
        $(window).scrollTop(0);
      } else if ((itemOffset = $(`.PostStream-item[data-index=${index}]`).offset())) {
        $(window).scrollTop(itemOffset.top - this.getMarginTop());
      }

      // Emit the URL update immediately now that the scroll has landed and the
      // DOM reflects the rendered posts. If all visible items are loading
      // placeholders (no data-number), fallbackToTarget resolves the number
      // directly from the target descriptor. Either way the user sees the
      // correct URL without waiting for the stability timer.
      this.calculatePosition(window.scrollY, { fallbackToTarget: true });

      // Enter "settling" mode. After the initial scroll, asynchronous content
      // (images, embeds, etc.) may cause post elements to resize, shifting the
      // target post out of view. During settling, a ResizeObserver watches for
      // these layout shifts and re-scrolls to the target post synchronously
      // (before the browser paints) so the user never sees the shift.
      //
      // Settling ends when: the user interacts (wheel/touch/key), no resizes
      // occur for 3 seconds (stability), or a 30-second hard timeout elapses.
      // At that point endSettling() calls calculatePosition() to update the URL
      // and scrubber to match the final viewport state.
      //
      // beginSettling tears down any previous settling first, preventing event
      // listener and timer leaks on consecutive programmatic jumps.
      this.beginSettling(this.stream.targetPost);

      // We want to adjust this again after posts have been loaded in
      // and position adjusted so that the scrubber's height is accurate.
      updateScrubberHeight();

      this.stream.paused = false;
      // Check if we need to load more posts after scrolling.
      this.loadPostsIfNeeded();
    });
  }

  /**
   * Set up a ResizeObserver that watches `.PostStream-item` elements for size
   * changes. During the "settling" phase (after a programmatic scroll), any
   * observed resize triggers an immediate re-scroll to the target post so the
   * viewport stays locked on it while asynchronous content loads.
   */
  setupScrollAnchoring() {
    this.settling = false;
    this.settlingTarget = null;
    this.settlingStabilityTimer = null;
    this.settlingHardTimer = null;
    this.observedElements = new Set();

    this.resizeObserver = new ResizeObserver(() => {
      if (!this.settling) return;

      // Re-scroll synchronously. ResizeObserver callbacks run after layout
      // but before paint, so correcting scrollTop here prevents the user
      // from ever seeing the shifted (wrong) position.
      this.reScrollToTarget();
      this.resetStabilityTimer();
    });

    this.observeNewPostItems();
  }

  /**
   * Re-scroll to the locked target post. Uses the same offset logic as
   * scrollToItem's post-load adjustment. Called synchronously from the
   * ResizeObserver callback so the correction is applied before the browser
   * paints, preventing visible flicker.
   */
  reScrollToTarget() {
    const target = this.settlingTarget;
    if (!target) return;

    let $item;
    if ('number' in target) {
      $item = this.$(`.PostStream-item[data-number=${target.number}]`);
    } else if ('index' in target) {
      $item = target.reply ? this.$('.PostStream-item:last-child') : this.$(`.PostStream-item[data-index=${target.index}]`);
    }

    if (!$item || !$item.length) return;

    const desiredTop = $item.is(':first-child') ? 0 : $item.offset().top - this.getMarginTop();
    $(window).scrollTop(desiredTop);
  }

  /**
   * Begin settling mode for the given target. Fully tears down any in-progress
   * settling first to avoid listener and timer leaks on consecutive jumps, then
   * arms fresh listeners and timers.
   *
   * @param {{ number: number }|{ index: number, reply?: boolean }} target
   */
  beginSettling(target) {
    // Tear down any existing settling state without emitting a position update.
    // This prevents orphaned wheel/touchstart/keydown listeners and hanging
    // 30s hard timers when a second jump fires before the first settles.
    this.stopSettlingListeners();
    clearTimeout(this.settlingHardTimer);
    clearTimeout(this.settlingStabilityTimer);

    this.settling = true;
    this.settlingTarget = target;
    this.startSettlingListeners();
    this.resetStabilityTimer();
    this.settlingHardTimer = setTimeout(() => this.endSettling(), 30000);
  }

  /**
   * Register global event listeners that end settling when the user takes
   * control (scrolling, touching, or pressing a key).
   */
  startSettlingListeners() {
    this._onUserInteraction = () => this.endSettling();
    window.addEventListener('wheel', this._onUserInteraction, { passive: true });
    window.addEventListener('touchstart', this._onUserInteraction, { passive: true });
    window.addEventListener('keydown', this._onUserInteraction, { passive: true });
  }

  /**
   * Remove the user-interaction listeners registered by startSettlingListeners.
   */
  stopSettlingListeners() {
    if (!this._onUserInteraction) return;
    window.removeEventListener('wheel', this._onUserInteraction);
    window.removeEventListener('touchstart', this._onUserInteraction);
    window.removeEventListener('keydown', this._onUserInteraction);
    this._onUserInteraction = null;
  }

  /**
   * Reset (or start) the stability timer. If no ResizeObserver callback fires
   * within 3 seconds, we consider the layout stable and exit settling.
   */
  resetStabilityTimer() {
    clearTimeout(this.settlingStabilityTimer);
    this.settlingStabilityTimer = setTimeout(() => this.endSettling(), 3000);
  }

  /**
   * Exit settling mode: stop watching for layout shifts, remove listeners,
   * clear timers, and optionally update the URL/scrubber to match the final viewport.
   *
   * @param {{ updatePosition?: boolean }} options
   *   `updatePosition` defaults to `true` (normal end-of-settling reconciliation).
   *   Pass `false` when called from the unmount path so no stale history entry is
   *   written after the user has already navigated away.
   */
  endSettling({ updatePosition = true } = {}) {
    if (!this.settling) return;
    this.settling = false;
    this.settlingTarget = null;
    this.stopSettlingListeners();
    clearTimeout(this.settlingStabilityTimer);
    clearTimeout(this.settlingHardTimer);
    this.settlingStabilityTimer = null;
    this.settlingHardTimer = null;
    if (updatePosition) this.calculatePosition();
  }

  /**
   * Observe any newly-rendered `.PostStream-item` elements that are not yet
   * tracked. Called on every `onupdate` to pick up items added by infinite
   * scroll or lazy loading.
   */
  observeNewPostItems() {
    if (!this.resizeObserver) return;

    const items = this.element.querySelectorAll('.PostStream-item');

    for (const item of items) {
      if (!this.observedElements.has(item)) {
        this.observedElements.add(item);
        this.resizeObserver.observe(item);
      }
    }
  }

  /**
   * Disconnect the ResizeObserver, end settling mode, and release tracked data.
   * Called from `onremove` to prevent memory leaks.
   *
   * Passes `updatePosition: false` to endSettling so that no history entry is
   * written after the user has already navigated away from this discussion.
   */
  cleanupScrollAnchoring() {
    this.endSettling({ updatePosition: false });
    if (this.resizeObserver) {
      this.resizeObserver.disconnect();
      this.resizeObserver = null;
    }
    if (this.observedElements) {
      this.observedElements.clear();
      this.observedElements = null;
    }
  }

  /**
   * 'Flash' the given post, drawing the user's attention to it.
   *
   * @param {JQuery} $item
   */
  flashItem($item) {
    // This might execute before the fadeIn class has been removed in PostStreamItem's
    // oncreate, so we remove it just to be safe and avoid a double animation.
    $item.removeClass('fadeIn');
    $item.addClass('flash').on('animationend webkitAnimationEnd', (e) => {
      $item.removeClass('flash');
    });
  }
}
