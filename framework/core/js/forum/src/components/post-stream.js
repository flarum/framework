import Component from 'flarum/component';
import ScrollListener from 'flarum/utils/scroll-listener';
import PostLoading from 'flarum/components/post-loading';
import anchorScroll from 'flarum/utils/anchor-scroll';
import mixin from 'flarum/utils/mixin';
import evented from 'flarum/utils/evented';
import ReplyPlaceholder from 'flarum/components/reply-placeholder';

class PostStream extends mixin(Component, evented) {
  constructor(props) {
    super(props);

    this.discussion = this.props.discussion;
    this.setup(this.props.includedPosts);

    this.scrollListener = new ScrollListener(this.onscroll.bind(this));

    this.paused = m.prop(false);

    this.loadPageTimeouts = {};
    this.pagesLoading = 0;
  }

  /**
    Load and scroll to a post with a certain number.
   */
  goToNumber(number, noAnimation) {
    this.paused(true);

    var promise = this.loadNearNumber(number);

    m.redraw(true);

    return promise.then(() => {
      m.redraw(true);

      this.scrollToNumber(number, noAnimation).done(this.unpause.bind(this));
    });
  }

  /**
    Load and scroll to a certain index within the discussion.
   */
  goToIndex(index, backwards, noAnimation) {
    this.paused(true);

    var promise = this.loadNearIndex(index);

    m.redraw(true);

    return promise.then(() => {
      anchorScroll(this.$('.item:'+(backwards ? 'last' : 'first')), () => m.redraw(true));

      this.scrollToIndex(index, noAnimation, backwards).done(this.unpause.bind(this));
    });
  }

  /**
    Load and scroll up to the first post in the discussion.
   */
  goToFirst() {
    return this.goToIndex(0);
  }

  /**
    Load and scroll down to the last post in the discussion.
   */
  goToLast() {
    return this.goToIndex(this.count() - 1, true);
  }

  /**
    Update the stream to reflect any posts that have been added/removed from the
    discussion.
   */
  sync() {
    var addedPosts = this.discussion.addedPosts();
    if (addedPosts) addedPosts.forEach(this.pushPost.bind(this));
    this.discussion.pushData({links: {addedPosts: null}});

    var removedPosts = this.discussion.removedPosts();
    if (removedPosts) removedPosts.forEach(this.removePost.bind(this));
    this.discussion.pushData({removedPosts: null});
  }

  /**
    Add a post to the end of the stream. Nothing will be done if the end of the
    stream is not visible.
   */
  pushPost(post) {
    if (this.visibleEnd >= this.count() - 1 && this.posts.indexOf(post) === -1) {
      this.posts.push(post);
      this.visibleEnd++;
    }
  }

  /**
    Search for and remove a specific post from the stream. Nothing will be done
    if the post is not visible.
   */
  removePost(id) {
    this.posts.some((item, i) => {
      if (item && item.id() == id) {
        this.posts.splice(i, 1);
        this.visibleEnd--;
        return true;
      }
    });
  }

  /**
    Get the total number of posts in the discussion.
   */
  count() {
    return this.discussion.postIds().length;
  }

  /**
    Make sure that the given index is not outside of the possible range of
    indexes in the discussion.
   */
  sanitizeIndex(index) {
    return Math.max(0, Math.min(this.count(), index));
  }

  /**
    Set up the stream with the given array of posts.
   */
  setup(posts) {
    this.posts = posts;
    this.visibleStart = this.discussion.postIds().indexOf(posts[0].id());
    this.visibleEnd = this.visibleStart + posts.length;
  }

  /**
    Clear the stream and fill it with placeholder posts.
   */
  clear(start, end) {
    this.visibleStart = start || 0;
    this.visibleEnd = end || this.constructor.loadCount;
    this.posts = [];
    for (var i = this.visibleStart; i < this.visibleEnd; i++) {
      this.posts.push(null);
    }
  }

  /**
    Construct a vDOM containing an element for each post that is visible in the
    stream. Posts that have not been loaded will be rendered as placeholders.
   */
  view() {
    function fadeIn(element, isInitialized, context) {
      if (!context.fadedIn) $(element).hide().fadeIn();
      context.fadedIn = true;
    }

    var lastTime;

    return m('div.discussion-posts.posts', {config: this.onload.bind(this)},
      this.posts.map((post, i) => {
        var content;
        var attributes = {};
        attributes['data-index'] = attributes.key = this.visibleStart + i;

        if (post) {
          var PostComponent = app.postComponentRegistry[post.contentType()];
          content = PostComponent ? PostComponent.component({post}) : '';
          attributes.config = fadeIn;
          attributes['data-time'] = post.time().toISOString();
          attributes['data-number'] = post.number();

          var dt = post.time() - lastTime;
          if (dt > 1000 * 60 * 60 * 24 * 4) {
            content = [
              m('div.time-gap', m('span', moment.duration(dt).humanize(), ' later')),
              content
            ];
          }
          lastTime = post.time();
        } else {
          content = PostLoading.component();
        }

        return m('div.item', attributes, content);
      }),

      // If we're viewing the end of the discussion, the user can reply, and
      // is not already doing so, then show a 'write a reply' placeholder.
      this.visibleEnd === this.count() &&
        (!app.session.user() || this.discussion.canReply()) &&
        !app.composingReplyTo(this.discussion)
        ? m('div.item', ReplyPlaceholder.component({discussion: this.discussion}))
        : ''
    );
  }

  /**
    Store a reference to the component's DOM and begin listening for the
    window's scroll event.
   */
  onload(element, isInitialized, context) {
    this.element(element);

    if (isInitialized) { return; }

    context.onunload = this.ondestroy.bind(this);

    // This is wrapped in setTimeout due to the following Mithril issue:
    // https://github.com/lhorie/mithril.js/issues/637
    setTimeout(() => this.scrollListener.start());
  }

  /**
    Stop listening for the window's scroll event, and cancel outstanding
    timeouts.
   */
  ondestroy() {
    this.scrollListener.stop();
    clearTimeout(this.calculatePositionTimeout);
  }

  /**
    When the window is scrolled, check if either extreme of the post stream is
    in the viewport, and if so, trigger loading the next/previous page.
   */
  onscroll(top) {
    if (this.paused()) return;

    var marginTop = this.getMarginTop();
    var viewportHeight = $(window).height() - marginTop;
    var viewportTop = top + marginTop;
    var loadAheadDistance = viewportHeight;

    if (this.visibleStart > 0) {
      var $item = this.$('.item[data-index='+this.visibleStart+']');

      if ($item.offset().top > viewportTop - loadAheadDistance) {
        this.loadPrevious();
      }
    }

    if (this.visibleEnd < this.count()) {
      var $item = this.$('.item[data-index='+(this.visibleEnd - 1)+']');

      if ($item.offset().top + $item.outerHeight(true) < viewportTop + viewportHeight + loadAheadDistance) {
        this.loadNext();
      }
    }

    clearTimeout(this.calculatePositionTimeout);
    this.calculatePositionTimeout = setTimeout(this.calculatePosition.bind(this), 500);
  }

  /**
    Load the next page of posts.
   */
  loadNext() {
    var start = this.visibleEnd;
    var end = this.visibleEnd = this.sanitizeIndex(this.visibleEnd + this.constructor.loadCount);

    for (var i = start; i < end; i++) {
      this.posts.push(null);
    }

    // If the posts which are two pages back from the page we're currently
    // loading still haven't loaded, we can assume that the user is scrolling
    // pretty fast. Thus, we will unload them.
    var twoPagesAway = start - this.constructor.loadCount * 2;
    if (twoPagesAway >= 0 && !this.posts[twoPagesAway - this.visibleStart]) {
      this.posts.splice(0, twoPagesAway + this.constructor.loadCount - this.visibleStart);
      this.visibleStart = twoPagesAway + this.constructor.loadCount;
      clearTimeout(this.loadPageTimeouts[twoPagesAway]);
    }

    this.loadPage(start, end);
  }

  /**
    Load the previous page of posts.
   */
  loadPrevious() {
    var end = this.visibleStart;
    var start = this.visibleStart = this.sanitizeIndex(this.visibleStart - this.constructor.loadCount);

    for (var i = start; i < end; i++) {
      this.posts.unshift(null);
    }

    // If the posts which are two pages back from the page we're currently
    // loading still haven't loaded, we can assume that the user is scrolling
    // pretty fast. Thus, we will unload them.
    var twoPagesAway = start + this.constructor.loadCount * 2;
    if (twoPagesAway <= this.count() && !this.posts[twoPagesAway - this.visibleStart]) {
      this.posts.splice(twoPagesAway - this.visibleStart);
      this.visibleEnd = twoPagesAway;
      clearTimeout(this.loadPageTimeouts[twoPagesAway]);
    }

    this.loadPage(start, end, true);
  }

  /**
    Load a page of posts into the stream and redraw.
   */
  loadPage(start, end, backwards) {
    var redraw = () => {
      if (start < this.visibleStart || end > this.visibleEnd) return;

      var anchorIndex = backwards && $(window).scrollTop() > 0 ? this.visibleEnd - 1 : this.visibleStart;
      anchorScroll(this.$('.item[data-index='+anchorIndex+']'), () => m.redraw(true));

      this.unpause();
    };
    redraw();

    this.pagesLoading++;

    this.loadPageTimeouts[start] = setTimeout(() => {
      this.loadRange(start, end).then(() => {
        redraw();
        this.pagesLoading--;
      });
    }, this.pagesLoading ? 1000 : 0);
  }

  /**
    Load and inject the specified range of posts into the stream, without
    clearing it.
   */
  loadRange(start, end) {
    return app.store.find('posts', this.discussion.postIds().slice(start, end)).then(posts => {
      if (start < this.visibleStart || end > this.visibleEnd) return;

      this.posts.splice.apply(this.posts, [start - this.visibleStart, end - start].concat(posts));
    });
  }

  /**
    Clear the stream and load posts near a certain number. Returns a promise. If
    the post with the given number is already loaded, the promise will be
    resolved immediately.
   */
  loadNearNumber(number) {
    if (this.posts.some(post => post.number() == number)) {
      return m.deferred().resolve().promise;
    }

    this.clear();

    return app.store.find('posts', {
      discussions: this.discussion.id(),
      near: number
    }).then(this.setup.bind(this));
  }

  /**
    Clear the stream and load posts near a certain index. A page of posts
    surrounding the given index will be loaded. Returns a promise. If the given
    index is already loaded, the promise will be resolved immediately.
   */
  loadNearIndex(index) {
    if (index >= this.visibleStart && index <= this.visibleEnd) {
      return m.deferred().resolve().promise;
    }

    var start = this.sanitizeIndex(index - this.constructor.loadCount / 2);
    var end = start + this.constructor.loadCount;

    this.clear(start, end);

    var ids = this.discussion.postIds().slice(start, end);

    return app.store.find('posts', ids).then(this.setup.bind(this));
  }

  /**
    Work out which posts (by number) are currently visible in the viewport, and
    fire an event with the information.
   */
  calculatePosition() {
    var marginTop = this.getMarginTop();
    var $window = $(window);
    var viewportHeight = $window.height() - marginTop;
    var scrollTop = $window.scrollTop() + marginTop;
    var startNumber;
    var endNumber;

    this.$('.item').each(function() {
      var $item = $(this);
      var top = $item.offset().top;
      var height = $item.outerHeight(true);

      if (top + height > scrollTop) {
        if (!startNumber) {
          startNumber = $item.data('number');
        }

        if (top + height < scrollTop + viewportHeight) {
          if ($item.data('number')) {
            endNumber = $item.data('number');
          }
        } else {
          return false;
        }
      }
    });

    if (startNumber) {
      this.trigger('positionChanged', startNumber || 1, endNumber);
    }
  }

  /**
    Get the distance from the top of the viewport to the point at which we
    would consider a post to be the first one visible.
   */
  getMarginTop() {
    return this.$() && $('.global-header').outerHeight() + parseInt(this.$().css('margin-top'));
  }

  /**
    Scroll down to a certain post by number and 'flash' it.
   */
  scrollToNumber(number, noAnimation) {
    var $item = this.$('.item[data-number='+number+']');

    return this.scrollToItem($item, noAnimation).done(this.flashItem.bind(this, $item));
  }

  /**
    Scroll down to a certain post by index.
   */
  scrollToIndex(index, noAnimation, bottom) {
    var $item = this.$('.item[data-index='+index+']');

    return this.scrollToItem($item, noAnimation, true, bottom);
  }

  /**
    Scroll down to the given post.
   */
  scrollToItem($item, noAnimation, force, bottom) {
    var $container = $('html, body').stop(true);

    if ($item.length) {
      var itemTop = $item.offset().top - this.getMarginTop();
      var itemBottom = itemTop + $item.height();
      var scrollTop = $(document).scrollTop();
      var scrollBottom = scrollTop + $(window).height();

      // If the item is already in the viewport, we may not need to scroll.
      if (force || itemTop < scrollTop || itemBottom > scrollBottom) {
        var scrollTop = bottom ? itemBottom : ($item.is(':first-child') ? 0 : itemTop);

        if (noAnimation) {
          $container.scrollTop(scrollTop);
        } else if (scrollTop !== $(document).scrollTop()) {
          $container.animate({scrollTop: scrollTop}, 'fast');
        }
      }
    }

    return $container.promise();
  }

  /**
    'Flash' the given post, drawing the user's attention to it.
   */
  flashItem($item) {
    $item.addClass('flash').one('animationend webkitAnimationEnd', () => $item.removeClass('flash'));
  }

  /**
    Resume the stream's ability to auto-load posts on scroll.
   */
  unpause() {
    this.paused(false);
    this.scrollListener.update(true);
    this.trigger('unpaused');
  }
}

PostStream.loadCount = 20;

export default PostStream;
