import Component from '../../common/Component';
import ScrollListener from '../../common/utils/ScrollListener';
import PostLoading from './LoadingPost';
import ReplyPlaceholder from './ReplyPlaceholder';
import Button from '../../common/components/Button';

/**
 * The `PostStream` component displays an infinitely-scrollable wall of posts in
 * a discussion. Posts that have not loaded will be displayed as placeholders.
 *
 * ### Props
 *
 * - `discussion`
 * - `state`
 */
export default class PostStream extends Component {
  init() {
    this.discussion = this.props.discussion;
    this.state = this.props.state;

    this.scrollListener = new ScrollListener(this.onscroll.bind(this));
  }

  view() {
    function fadeIn(element, isInitialized, context) {
      if (!context.fadedIn) $(element).hide().fadeIn();
      context.fadedIn = true;
    }

    let lastTime;

    const viewingEnd = this.state.viewingEnd();
    const posts = this.state.posts();
    const postIds = this.discussion.postIds();

    const items = posts.map((post, i) => {
      let content;
      const attrs = { 'data-index': this.state.visibleStart + i };

      if (post) {
        const time = post.createdAt();
        const PostComponent = app.postComponents[post.contentType()];
        content = PostComponent ? PostComponent.component({ post }) : '';

        attrs.key = 'post' + post.id();
        attrs.config = fadeIn;
        attrs['data-time'] = time.toISOString();
        attrs['data-number'] = post.number();
        attrs['data-id'] = post.id();
        attrs['data-type'] = post.contentType();

        // If the post before this one was more than 4 hours ago, we will
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
        attrs.key = 'post' + postIds[this.state.visibleStart + i];

        content = PostLoading.component();
      }

      return (
        <div className="PostStream-item" {...attrs}>
          {content}
        </div>
      );
    });

    if (!viewingEnd && posts[this.state.visibleEnd - this.state.visibleStart - 1]) {
      items.push(
        <div className="PostStream-loadMore" key="loadMore">
          <Button className="Button" onclick={this.state.loadNext.bind(this.state)}>
            {app.translator.trans('core.forum.post_stream.load_more_button')}
          </Button>
        </div>
      );
    }

    // If we're viewing the end of the discussion, the user can reply, and
    // is not already doing so, then show a 'write a reply' placeholder.
    if (viewingEnd && (!app.session.user || this.discussion.canReply())) {
      items.push(
        <div className="PostStream-item" key="reply">
          {ReplyPlaceholder.component({ discussion: this.discussion })}
        </div>
      );
    }

    return <div className="PostStream">{items}</div>;
  }

  config(isInitialized, context) {
    console.log('redrawing', this.state.visibleStart, this.state.visibleEnd);
    if (this.state.needsScroll) {
      this.state.needsScroll = false;
      const locationType = this.state.locationType;
      if (this[locationType] != this.state[locationType]) {
        if (locationType == 'number') {
          this.scrollToNumber(this.state.number, this.state.noAnimationScroll);
        } else if (locationType == 'index') {
          const index = this.state.sanitizeIndex(this.state.index);
          const backwards = index == this.state.count() - 1;
          this.scrollToIndex(index, this.state.noAnimationScroll, backwards);
        }
        this[locationType] = this.state[locationType];
      }
    }

    if (isInitialized) return;
    // This is wrapped in setTimeout due to the following Mithril issue:
    // https://github.com/lhorie/mithril.js/issues/637
    setTimeout(() => this.scrollListener.start());

    context.onunload = () => {
      this.scrollListener.stop();
      clearTimeout(this.calculatePositionTimeout);
    };
  }

  /**
   * When the window is scrolled, check if either extreme of the post stream is
   * in the viewport, and if so, trigger loading the next/previous page.
   *
   * @param {Integer} top
   */
  onscroll(top = window.pageYOffset) {
    if (this.state.paused) return;
    const marginTop = this.getMarginTop();
    const viewportHeight = $(window).height() - marginTop;
    const viewportTop = top + marginTop;
    const loadAheadDistance = 300;

    if (this.state.visibleStart > 0) {
      const $item = this.$('.PostStream-item[data-index=' + this.state.visibleStart + ']');

      if ($item.length && $item.offset().top > viewportTop - loadAheadDistance) {
        this.state.loadPrevious();
      }
    }

    if (this.state.visibleEnd < this.state.count()) {
      const $item = this.$('.PostStream-item[data-index=' + (this.state.visibleEnd - 1) + ']');

      if ($item.length && $item.offset().top + $item.outerHeight(true) < viewportTop + viewportHeight + loadAheadDistance) {
        this.state.loadNext();
      }
    }

    // Throttle calculation of our position (start/end numbers of posts in the
    // viewport) to 100ms.
    clearTimeout(this.calculatePositionTimeout);
    this.calculatePositionTimeout = setTimeout(this.calculatePosition.bind(this, top), 100);

    this.updateScrubber(top);
  }

  updateScrubber(top = window.pageYOffset) {
    const marginTop = this.getMarginTop();
    const viewportHeight = $(window).height() - marginTop;
    const viewportTop = top + marginTop;

    // Before looping through all of the posts, we reset the scrollbar
    // properties to a 'default' state. These values reflect what would be
    // seen if the browser were scrolled right up to the top of the page,
    // and the viewport had a height of 0.
    const $items = this.$('.PostStream-item[data-index]');
    let index;
    let visible = 0;
    let period = '';

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

      const threeQuartersVisible = visibleTop / height < 0.75;
      const coversQuarterOfViewport = (height - visibleTop) / viewportHeight > 0.25;
      if (index === undefined && (threeQuartersVisible || coversQuarterOfViewport)) {
        index = parseFloat($this.data('index')) + visibleTop / height;
        // If this item has a time associated with it, then set the
        // scrollbar's current period to a formatted version of this time.
        const time = $this.data('time');
        if (time) period = time;
      }

      if (visiblePost > 0) {
        visible += visiblePost / height;
      }
    });

    this.state.index = index;
    this.state.visible(visible);
    this.state.description = period ? dayjs(period).format('MMMM YYYY') : '';
  }

  /**
   * Work out which posts (by number) are currently visible in the viewport, and
   * fire an event with the information.
   */
  calculatePosition(top = window.pageYOffset) {
    const marginTop = this.getMarginTop();
    const $window = $(window);
    const viewportHeight = $window.height() - marginTop;
    const scrollTop = $window.scrollTop() + marginTop;
    const viewportTop = top + marginTop;

    let startNumber;
    let endNumber;

    this.$('.PostStream-item').each(function () {
      const $item = $(this);
      const top = $item.offset().top;
      const height = $item.outerHeight(true);
      const visibleTop = Math.max(0, viewportTop - top);

      const threeQuartersVisible = visibleTop / height < 0.75;
      const coversQuarterOfViewport = (height - visibleTop) / viewportHeight > 0.25;
      if (startNumber === undefined && (threeQuartersVisible || coversQuarterOfViewport)) {
        startNumber = $item.data('number');
      }

      if (top + height > scrollTop) {
        if (top + height < scrollTop + viewportHeight) {
          if ($item.data('number')) {
            endNumber = $item.data('number');
          }
        } else return false;
      }
    });

    if (startNumber) {
      this.props.positionHandler(startNumber || 1, endNumber, startNumber);
    }
  }

  /**
   * Get the distance from the top of the viewport to the point at which we
   * would consider a post to be the first one visible.
   *
   * @return {Integer}
   */
  getMarginTop() {
    return this.$() && $('#header').outerHeight() + parseInt(this.$().css('margin-top'), 10);
  }

  /**
   * Scroll down to a certain post by number and 'flash' it.
   *
   * @param {Integer} number
   * @param {Boolean} noAnimation
   * @return {jQuery.Deferred}
   */
  scrollToNumber(number, noAnimation) {
    console.log('scrollToNumber');
    const $item = this.$(`.PostStream-item[data-number=${number}]`);

    return this.scrollToItem($item, noAnimation).then(this.flashItem.bind(this, $item));
  }

  /**
   * Scroll down to a certain post by index.
   *
   * @param {Integer} index
   * @param {Boolean} noAnimation
   * @param {Boolean} bottom Whether or not to scroll to the bottom of the post
   *     at the given index, instead of the top of it.
   * @return {jQuery.Deferred}
   */
  scrollToIndex(index, noAnimation, bottom) {
    console.log('scrollToIndex');
    const $item = this.$(`.PostStream-item[data-index=${index}]`);

    return this.scrollToItem($item, noAnimation, true, bottom).then(() => {
      if (index == this.state.count() - 1) {
        this.flashItem(this.$('.PostStream-item:last-child'));
      }
    });
  }

  /**
   * Scroll down to the given post.
   *
   * @param {jQuery} $item
   * @param {Boolean} noAnimation
   * @param {Boolean} force Whether or not to force scrolling to the item, even
   *     if it is already in the viewport.
   * @param {Boolean} bottom Whether or not to scroll to the bottom of the post
   *     at the given index, instead of the top of it.
   * @return {jQuery.Deferred}
   */
  scrollToItem($item, noAnimation, force, bottom) {
    console.log('scrollToItem');
    const $container = $('html, body').stop(true);

    if ($item.length) {
      const itemTop = $item.offset().top - this.getMarginTop();
      const itemBottom = $item.offset().top + $item.height();
      const scrollTop = $(document).scrollTop();
      const scrollBottom = scrollTop + $(window).height();

      // If the item is already in the viewport, we may not need to scroll.
      // If we're scrolling to the bottom of an item, then we'll make sure the
      // bottom will line up with the top of the composer.
      if (force || itemTop < scrollTop || itemBottom > scrollBottom) {
        const top = bottom ? itemBottom - $(window).height() + app.composer.computedHeight() : $item.is(':first-child') ? 0 : itemTop;

        if (noAnimation) {
          $container.scrollTop(top);
        } else if (top !== scrollTop) {
          $container.animate({ scrollTop: top }, 'fast');
        }
      }
    }

    return Promise.all([$container.promise(), this.state.loadPromise])
      .then(() => {
        m.redraw(true);
        return $(window).scrollTop($(`.PostStream-item[data-index=${$item.data('index')}]`).offset().top - this.getMarginTop());
      })
      .then(() => {
        return this.calculatePosition();
      })
      .then(() => this.state.unpause());
  }

  /**
   * 'Flash' the given post, drawing the user's attention to it.
   *
   * @param {jQuery} $item
   */
  flashItem($item) {
    $item.addClass('flash').one('animationend webkitAnimationEnd', () => $item.removeClass('flash'));
  }
}
