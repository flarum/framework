import Component from '../../common/Component';
import ScrollListener from '../../common/utils/ScrollListener';
import PostLoading from './LoadingPost';
import anchorScroll from '../../common/utils/anchorScroll';
import ReplyPlaceholder from './ReplyPlaceholder';
import Button from '../../common/components/Button';

/**
 * The `PostStream` component displays an infinitely-scrollable wall of posts in
 * a discussion. Posts that have not loaded will be displayed as placeholders.
 *
 * ### Props
 *
 * - `state`
 */
export default class PostStream extends Component {
  init() {
    this.state = this.props.state;

    this.scrollListener = new ScrollListener(this.onscroll.bind(this));
    this.loadPageTimeouts = {};
    this.pagesLoading = 0;

    this.state.on('unpaused', () => {
      this.scrollListener.update();
    });
    this.state.on('scrollToLast', () => {
      this.scrollToLast();
    });
    this.state.on('scrollToNumber', (number, noAnimation) => {
      this.scrollToNumber(number, noAnimation);
    });
    this.state.on('scrollToIndex', (index, noAnimation, backwards) => {
      anchorScroll(this.$('.PostStream-item:' + (backwards ? 'last' : 'first')), () => m.redraw(true));
      this.scrollToIndex(index, noAnimation, backwards);
    });
  }

  view() {
    function fadeIn(element, isInitialized, context) {
      if (!context.fadedIn) $(element).hide().fadeIn();
      context.fadedIn = true;
    }

    let lastTime;

    this.state.visibleEnd = this.state.sanitizeIndex(this.state.visibleEnd);
    this.state.viewingEnd = this.state.visibleEnd === this.state.count();

    const posts = this.state.posts();
    const postIds = this.state.discussion.postIds();

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

    if (!this.state.viewingEnd && posts[this.state.visibleEnd - this.state.visibleStart - 1]) {
      items.push(
        <div className="PostStream-loadMore" key="loadMore">
          <Button className="Button" onclick={this.state.loadNext.bind(this)}>
            {app.translator.trans('core.forum.post_stream.load_more_button')}
          </Button>
        </div>
      );
    }

    // If we're viewing the end of the discussion, the user can reply, and
    // is not already doing so, then show a 'write a reply' placeholder.
    if (this.state.viewingEnd && (!app.session.user || this.state.discussion.canReply())) {
      items.push(
        <div className="PostStream-item" key="reply">
          {ReplyPlaceholder.component({ discussion: this.state.discussion })}
        </div>
      );
    }

    return <div className="PostStream js-PostStream">{items}</div>;
  }

  config(isInitialized, context) {
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
  onscroll(top) {
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
    this.calculatePositionTimeout = setTimeout(this.calculatePosition.bind(this), 100);
  }

  /**
   * Work out which posts (by number) are currently visible in the viewport, and
   * fire an event with the information.
   */
  calculatePosition() {
    const marginTop = this.getMarginTop();
    const $window = $(window);
    const viewportHeight = $window.height() - marginTop;
    const scrollTop = $window.scrollTop() + marginTop;
    let startNumber;
    let endNumber;

    this.$('.PostStream-item').each(function () {
      const $item = $(this);
      const top = $item.offset().top;
      const height = $item.outerHeight(true);

      if (top + height > scrollTop) {
        if (!startNumber) {
          startNumber = endNumber = $item.data('number');
        }

        if (top + height < scrollTop + viewportHeight) {
          if ($item.data('number')) {
            endNumber = $item.data('number');
          }
        } else return false;
      }
    });

    if (startNumber) {
      this.props.positionHandler(startNumber || 1, endNumber);
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
    const $item = this.$(`.PostStream-item[data-number=${number}]`);

    return this.scrollToItem($item, noAnimation).done(this.flashItem.bind(this, $item));
  }

  scrollToLast() {
    $('html,body')
      .stop(true)
      .animate(
        {
          scrollTop: $(document).height() - $(window).height(),
        },
        'fast',
        () => {
          this.flashItem(this.$('.PostStream-item:last-child'));
        }
      );
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
    const $item = this.$(`.PostStream-item[data-index=${index}]`);

    return this.scrollToItem($item, noAnimation, true, bottom);
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

    return $container.promise();
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
