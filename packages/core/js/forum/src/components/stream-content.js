import Component from 'flarum/component';
import StreamItem from 'flarum/components/stream-item';
import LoadingIndicator from 'flarum/components/loading-indicator';
import ScrollListener from 'flarum/utils/scroll-listener';
import mixin from 'flarum/utils/mixin';
import evented from 'flarum/utils/evented';

/**

 */
export default class StreamContent extends mixin(Component, evented) {
  /**

   */
  constructor(props) {
    super(props);

    this.loaded = () => this.props.stream.loadedCount();
    this.paused = m.prop(false);
    this.active = () => this.loaded() && !this.paused();

    this.scrollListener = new ScrollListener(this.onscroll.bind(this));

    this.on('loadingIndex', this.loadingIndex.bind(this));
    this.on('loadedIndex', this.loadedIndex.bind(this));

    this.on('loadingNumber', this.loadingNumber.bind(this));
    this.on('loadedNumber', this.loadedNumber.bind(this));
  }

  /**

   */
  view() {
    var stream = this.props.stream;

    return m('div', {className: 'stream '+(this.props.className || ''), config: this.onload.bind(this)},
      stream ? stream.content.map(item => StreamItem.component({
          key: item.start+'-'+item.end,
          item: item,
          loadRange: stream.loadRange.bind(stream),
          ondelete: this.ondelete.bind(this)
        }))
        : LoadingIndicator.component());
  }

  /**

   */
  onload(element, isInitialized, context) {
    this.element(element);

    if (isInitialized) { return; }

    context.onunload = this.ondestroy.bind(this);
    this.scrollListener.start();
  }

  ondelete(post) {
    this.props.stream.removePost(post);
  }

  /**

   */
  ondestroy() {
    this.scrollListener.stop();
    clearTimeout(this.positionChangedTimeout);
  }

  /**

   */
  onscroll(top) {
    if (!this.active()) { return; }

    var $items = this.$('.item');

    var marginTop = this.getMarginTop();
    var $window = $(window);
    var viewportHeight = $window.height() - marginTop;
    var scrollTop = top + marginTop;
    var loadAheadDistance = 300;
    var startNumber;
    var endNumber;

    // Loop through each of the items in the stream. An 'item' is either a
    // single post or a 'gap' of one or more posts that haven't been loaded
    // yet.
    $items.each(function() {
      var $this = $(this);
      var top = $this.offset().top;
      var height = $this.outerHeight();

      // If this item is above the top of the viewport (plus a bit of leeway
      // for loading-ahead gaps), skip to the next one. If it's below the
      // bottom of the viewport, break out of the loop.
      if (top + height < scrollTop - loadAheadDistance) { return; }
      if (top > scrollTop + viewportHeight + loadAheadDistance) { return false; }

      // If this item is a gap, then we may proceed to check if it's a
      // *terminal* gap and trigger its loading mechanism.
      if ($this.hasClass('gap')) {
        var first = $this.is(':first-child');
        var last = $this.is(':last-child');
        var item = $this[0].instance.props.item;
        if ((first || last) && !item.loading) {
          item.direction = first ? 'up' : 'down';
          $this[0].instance.load();
        }
      } else {
        if (top + height < scrollTop + viewportHeight) {
          endNumber = $this.data('number');
        }

        // Check if this item is in the viewport, minus the distance we allow
        // for load-ahead gaps. If we haven't yet stored a post's number, then
        // this item must be the FIRST item in the viewport. Therefore, we'll
        // grab its post number so we can update the controller's state later.
        if (top + height > scrollTop && !startNumber) {
          startNumber = $this.data('number');
        }
      }
    });


    // Finally, we want to update the controller's state with regards to the
    // current viewing position of the discussion. However, we don't want to
    // do this on every single scroll event as it will slow things down. So,
    // let's do it at a minimum of 250ms by clearing and setting a timeout.
    clearTimeout(this.positionChangedTimeout);
    this.positionChangedTimeout = setTimeout(() => this.props.positionChanged(startNumber || 1, endNumber), 500);
  }

  /**
    Get the distance from the top of the viewport to the point at which we
    would consider a post to be the first one visible.
   */
  getMarginTop() {
    return this.$() && $('.global-header').outerHeight() + parseInt(this.$().css('margin-top'));
  }

  /**
    Scroll down to a certain post by number (or the gap which we think the
    post is in) and highlight it.
   */
  scrollToNumber(number, noAnimation) {
    // Clear the highlight class from all posts, and attempt to find and
    // highlight a post with the specified number. However, we don't apply
    // the highlight to the first post in the stream because it's pretty
    // obvious that it's the top one.
    var $item = this.$('.item').removeClass('highlight').filter('[data-number='+number+']');
    if (!$item.is(':first-child')) {
      $item.addClass('highlight');
    }

    // If we didn't have any luck, then a post with this number either
    // doesn't exist, or it hasn't been loaded yet. We'll find the item
    // that's closest to the post with this number and scroll to that
    // instead.
    if (!$item.length) {
      $item = this.findNearestToNumber(number);
    }

    return this.scrollToItem($item, noAnimation);
  }

  /**
    Scroll down to a certain post by index (or the gap the post is in.)
   */
  scrollToIndex(index, noAnimation) {
    var $item = this.findNearestToIndex(index);
    return this.scrollToItem($item, noAnimation);
  }

  /**

   */
  scrollToItem($item, noAnimation) {
    var $container = $('html, body').stop(true);
    if ($item.length) {
      var scrollTop = $item.is(':first-child') ? 0 : $item.offset().top - this.getMarginTop();
      if (noAnimation) {
        $container.scrollTop(scrollTop);
      } else if (scrollTop !== $(document).scrollTop()) {
        $container.animate({scrollTop: scrollTop}, 'fast');
      }
    }
    return $container.promise();
  }

  /**
    Find the DOM element of the item that is nearest to a post with a certain
    number. This will either be another post (if the requested post doesn't
    exist,) or a gap presumed to contain the requested post.
   */
  findNearestToNumber(number) {
    var $nearestItem = $();
    this.$('.item').each(function() {
      var $this = $(this);
      if ($this.data('number') > number) {
        return false;
      }
      $nearestItem = $this;
    });
    return $nearestItem;
  }

  /**

   */
  findNearestToIndex(index) {
    var $nearestItem = this.$('.item[data-start='+index+'][data-end='+index+']');
    if (!$nearestItem.length) {
      this.$('.item').each(function() {
        $nearestItem = $(this);
        if ($nearestItem.data('end') >= index) {
          return false;
        }
      });
    }
    return $nearestItem;
  }

  /**

   */
  loadingIndex(index, noAnimation) {
    // The post at this index is being loaded. We want to scroll to where we
    // think it will appear. We may be scrolling to the edge of the page,
    // but we don't want to trigger any terminal post gaps to load by doing
    // that. So, we'll disable the window's scroll handler for now.
    this.paused(true);
    this.scrollToIndex(index, noAnimation);
  }

  /**

   */
  loadedIndex(index, noAnimation) {
    m.redraw(true);

    // The post at this index has been loaded. After we scroll to this post,
    // we want to resume scroll events.
    this.scrollToIndex(index, noAnimation).done(this.unpause.bind(this));
  }

  /**

   */
  loadingNumber(number, noAnimation) {
    // The post with this number is being loaded. We want to scroll to where
    // we think it will appear. We may be scrolling to the edge of the page,
    // but we don't want to trigger any terminal post gaps to load by doing
    // that. So, we'll disable the window's scroll handler for now.
    this.paused(true);
    if (this.$()) {
      this.scrollToNumber(number, noAnimation);
    }
  }

  /**

   */
  loadedNumber(number, noAnimation) {
    m.redraw(true);

    // The post with this number has been loaded. After we scroll to this
    // post, we want to resume scroll events.
    this.scrollToNumber(number, noAnimation).done(this.unpause.bind(this));
  }

  /**

   */
  unpause() {
    this.paused(false);
    this.scrollListener.update(true);
    this.trigger('unpaused');
  }

  /**

   */
  goToNumber(number, noAnimation) {
    number = Math.max(number, 1);

    // Let's start by telling our listeners that we're going to load
    // posts near this number. Elsewhere we will listen and
    // consequently scroll down to the appropriate position.
    this.trigger('loadingNumber', number, noAnimation);

    // Now we have to actually make sure the posts around this new start
    // position are loaded. We will tell our listeners when they are.
    // Again, a listener will scroll down to the appropriate post.
    var promise = this.props.stream.loadNearNumber(number);
    m.redraw();

    return promise.then(() => this.trigger('loadedNumber', number, noAnimation));
  }

  /**

   */
  goToIndex(index, backwards, noAnimation) {
    // Let's start by telling our listeners that we're going to load
    // posts at this index. Elsewhere we will listen and consequently
    // scroll down to the appropriate position.
    this.trigger('loadingIndex', index, noAnimation);

    // Now we have to actually make sure the posts around this index
    // are loaded. We will tell our listeners when they are. Again, a
    // listener will scroll down to the appropriate post.
    var promise = this.props.stream.loadNearIndex(index, backwards);
    m.redraw();

    return promise.then(() => this.trigger('loadedIndex', index, noAnimation));
  }

  /**

   */
  goToFirst() {
    return this.goToIndex(0);
  }

  /**

   */
  goToLast() {
    var promise = this.goToIndex(this.props.stream.count() - 1, true);

    // If the post stream is loading some new posts, then after it's
    // done we'll want to immediately scroll down to the bottom of the
    // page.
    var items = this.props.stream.content;
    if (!items[items.length - 1].post) {
      promise.then(() => $('html, body').stop(true).scrollTop($('body').height()));
    }

    return promise;
  }
}
