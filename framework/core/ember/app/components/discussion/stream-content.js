import Ember from 'ember';

var $ = Ember.$;

/**
  Component which renders items in a `post-stream` object. It handles scroll
  events so that when the user scrolls to the top/bottom of the page, more
  posts will load. In doing this is also sends an action so that the parent
  controller's state can be updated. Finally, it can be sent actions to jump
  to a certain position in the stream and load posts there.
 */
export default Ember.Component.extend({
  classNames: ['stream'],

  // The stream object.
  stream: null,

  // Pause window scroll event listeners. This is set to true while loading
  // posts, because we don't want a scroll event to trigger another block of
  // posts to be loaded.
  paused: false,

  // Whether or not the stream's initial content has loaded.
  loaded: Ember.computed.bool('stream.loadedCount'),

  // When the stream content is not "active", window scroll event listeners
  // will be ignored. For the stream content to be active, its initial
  // content must be loaded and it must not be "paused".
  active: Ember.computed('loaded', 'paused', function() {
    return this.get('loaded') && !this.get('paused');
  }),

  // Whenever the stream object changes (i.e. we have transitioned to a
  // different discussion), pause events and cancel any pending state updates.
  refresh: Ember.observer('stream', function() {
    this.set('paused', true);
    clearTimeout(this.updateStateTimeout);
  }),

  didInsertElement: function() {
    $(window).on('scroll', {view: this}, this.windowWasScrolled);
  },

  willDestroyElement: function() {
    $(window).off('scroll', this.windowWasScrolled);
  },

  windowWasScrolled: function(event) {
    event.data.view.update();
  },

  // Run any checks/updates according to the window's current scroll
  // position. We check to see if any terminal 'gaps' are in the viewport
  // and trigger their loading mechanism if they are. We also update the
  // controller's 'start' query param with the current position. Note: this
  // observes the 'active' property, so if the stream is 'unpaused', then an
  // update will be triggered.
  update: Ember.observer('active', function() {
    if (!this.get('active')) { return; }

    var $items = this.$().find('.item'),
      $window = $(window),
      marginTop = this.getMarginTop(),
      scrollTop = $window.scrollTop() + marginTop,
      viewportHeight = $window.height() - marginTop,
      loadAheadDistance = 300,
      startNumber,
      endNumber;

    // Loop through each of the items in the stream. An 'item' is either a
    // single post or a 'gap' of one or more posts that haven't been
    // loaded yet.
    $items.each(function() {
      var $this = $(this);
      var top = $this.offset().top;
      var height = $this.outerHeight(true);

      // If this item is above the top of the viewport (plus a bit of
      // leeway for loading-ahead gaps), skip to the next one. If it's
      // below the bottom of the viewport, break out of the loop.
      if (top + height < scrollTop - loadAheadDistance) { return; }
      if (top > scrollTop + viewportHeight + loadAheadDistance) { return false; }

      // If this item is a gap, then we may proceed to check if it's a
      // *terminal* gap and trigger its loading mechanism.
      if ($this.hasClass('gap')) {
        var gapView = Ember.View.views[$this.attr('id')];
        if ($this.is(':first-child')) {
          gapView.set('direction', 'up').load();
        } else if ($this.is(':last-child')) {
          gapView.set('direction', 'down').load();
        }
      } else {
        if (top + height < scrollTop + viewportHeight) {
          endNumber = $this.data('number');
        }

        // Check if this item is in the viewport, minus the distance we
        // allow for load-ahead gaps. If we haven't yet stored a post's
        // number, then this item must be the FIRST item in the viewport.
        // Therefore, we'll grab its post number so we can update the
        // controller's state later.
        if (top + height > scrollTop && ! startNumber) {
          startNumber = $this.data('number');
        }
      }
    });

    // Finally, we want to update the controller's state with regards to the
    // current viewing position of the discussion. However, we don't want to
    // do this on every single scroll event as it will slow things down. So,
    // let's do it at a minimum of 250ms by clearing and setting a timeout.
    var view = this;
    clearTimeout(this.updateStateTimeout);
    this.updateStateTimeout = setTimeout(function() {
      view.sendAction('positionChanged', startNumber || 1, endNumber);
    }, 500);
  }),

  loadingNumber: function(number, noAnimation) {
    // The post with this number is being loaded. We want to scroll to where
    // we think it will appear. We may be scrolling to the edge of the page,
    // but we don't want to trigger any terminal post gaps to load by doing
    // that. So, we'll disable the window's scroll handler for now.
    this.set('paused', true);
    this.jumpToNumber(number, noAnimation);
  },

  loadedNumber: function(number, noAnimation) {
    // The post with this number has been loaded. After we scroll to this
    // post, we want to resume scroll events.
    var view = this;
    Ember.run.scheduleOnce('afterRender', function() {
      view.jumpToNumber(number, noAnimation).done(function() {
        view.set('paused', false);
      });
    });
  },

  loadingIndex: function(index, noAnimation) {
    // The post at this index is being loaded. We want to scroll to where we
    // think it will appear. We may be scrolling to the edge of the page,
    // but we don't want to trigger any terminal post gaps to load by doing
    // that. So, we'll disable the window's scroll handler for now.
    this.set('paused', true);
    this.jumpToIndex(index, noAnimation);
  },

  loadedIndex: function(index, noAnimation) {
    // The post at this index has been loaded. After we scroll to this post,
    // we want to resume scroll events.
    var view = this;
    Ember.run.scheduleOnce('afterRender', function() {
      view.jumpToIndex(index, noAnimation).done(function() {
        view.set('paused', false);
      });
    });
  },

  // Scroll down to a certain post by number (or the gap which we think the
  // post is in) and highlight it.
  jumpToNumber: function(number, noAnimation) {
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
  },

  // Scroll down to a certain post by index (or the gap the post is in.)
  jumpToIndex: function(index, noAnimation) {
    var $item = this.findNearestToIndex(index);
    return this.scrollToItem($item, noAnimation);
  },

  scrollToItem: function($item, noAnimation) {
    var $container = $('html, body').stop(true);
    if ($item.length) {
      var marginTop = this.getMarginTop();
      var scrollTop = $item.is(':first-child') ? 0 : $item.offset().top - marginTop;
      if (noAnimation) {
        $container.scrollTop(scrollTop);
      } else if (scrollTop !== $(document).scrollTop()) {
        $container.animate({scrollTop: scrollTop});
      }
    }
    return $container.promise();
  },

  // Find the DOM element of the item that is nearest to a post with a certain
  // number. This will either be another post (if the requested post doesn't
  // exist,) or a gap presumed to contain the requested post.
  findNearestToNumber: function(number) {
    var $nearestItem = $();
    this.$('.item').each(function() {
      var $this = $(this);
      if ($this.data('number') > number) {
        return false;
      }
      $nearestItem = $this;
    });
    return $nearestItem;
  },

  findNearestToIndex: function(index) {
    var $nearestItem = this.$('.item[data-start='+index+'][data-end='+index+']');
    if (! $nearestItem.length) {
      this.$('.item').each(function() {
        $nearestItem = $(this);
        if ($nearestItem.data('end') >= index) {
          return false;
        }
      });
    }
    return $nearestItem;
  },

  // Get the distance from the top of the viewport to the point at which we
  // would consider a post to be the first one visible.
  getMarginTop: function() {
    return $('#header').outerHeight() + parseInt(this.$().css('margin-top'));
  },

  actions: {
    goToNumber: function(number, noAnimation) {
      number = Math.max(number, 1);

      // Let's start by telling our listeners that we're going to load
      // posts near this number. Elsewhere we will listen and
      // consequently scroll down to the appropriate position.
      this.trigger('loadingNumber', number, noAnimation);

      // Now we have to actually make sure the posts around this new start
      // position are loaded. We will tell our listeners when they are.
      // Again, a listener will scroll down to the appropriate post.
      var controller = this;
      this.get('stream').loadNearNumber(number).then(function() {
        controller.trigger('loadedNumber', number, noAnimation);
      });
    },

    goToIndex: function(index, backwards, noAnimation) {
      // Let's start by telling our listeners that we're going to load
      // posts at this index. Elsewhere we will listen and consequently
      // scroll down to the appropriate position.
      this.trigger('loadingIndex', index, noAnimation);

      // Now we have to actually make sure the posts around this index
      // are loaded. We will tell our listeners when they are. Again, a
      // listener will scroll down to the appropriate post.
      var controller = this;
      this.get('stream').loadNearIndex(index, backwards).then(function() {
        controller.trigger('loadedIndex', index, noAnimation);
      });
    },

    goToFirst: function() {
      this.send('goToIndex', 0);
    },

    goToLast: function() {
      this.send('goToIndex', this.get('stream.count') - 1, true);

      // If the post stream is loading some new posts, then after it's
      // done we'll want to immediately scroll down to the bottom of the
      // page.
      if (! this.get('stream.lastLoaded')) {
        this.get('stream').one('postsLoaded', function() {
          Ember.run.scheduleOnce('afterRender', function() {
            $('html, body').stop(true).scrollTop($('body').height());
          });
        });
      }
    },

    loadRange: function(start, end, backwards) {
      this.get('stream').loadRange(start, end, backwards);
    },

    postRemoved: function(post) {
      this.sendAction('postRemoved', post);
    }
  }
});
