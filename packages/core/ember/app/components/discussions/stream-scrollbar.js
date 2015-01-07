import Ember from 'ember';

import Scrollbar from '../../utils/scrollbar';
import PostStreamMixin from '../../mixins/post-stream';

export default Ember.View.extend(PostStreamMixin, {

  layoutName: 'components/discussions/stream-scrollbar',
  classNames: ['scrubber', 'discussion-scrubber'],

  // An object which represents/ecapsulates the scrollbar.
  scrollbar: null,

  // Right after the controller finished loading a discussion, we want to
  // trigger a scroll event on the window so the interface is kept up-to-date.
  loadedChanged: function() {
    this.scrollbar.setDisabled(! this.get('controller.loaded'));
  }.observes('controller.loaded'),

  countChanged: function() {
    this.scrollbar.setCount(this.get('controller.postStream.count'));
  }.observes('controller.postStream.count'),

  windowWasResized: function(event) {
    var view = event.data.view;
    // view.scrollbar.$.height($('#sidebar-content').height() + $('#sidebar-content').offset().top - view.scrollbar.$.offset().top - 80);
    view.scrollbar.update();
  },

  windowWasScrolled: function(event) {
    var view = event.data.view,
      $window = $(window);

    if (! view.get('controller.loaded') || $window.data('disableScrollHandler')) {
      return;
    }

    var scrollTop = $window.scrollTop(),
      windowHeight = $window.height();

    // Before looping through all of the posts, we reset the scrollbar
    // properties to a 'default' state. These values reflect what would be
    // seen if the browser were scrolled right up to the top of the page,
    // and the viewport had a height of 0.
    var index = $('.posts .item:first').data('end');
    var visiblePosts = 0;
    var period = '';

    var first = $('.posts .item[data-start=0]');
    var offsetTop = first.length ? first.offset().top : 0;

    // Now loop through each of the items in the discussion. An 'item' is
    // either a single post or a 'gap' of one or more posts that haven't
    // been loaded yet.
    // @todo cache item top positions to speed this up?
    $('.posts .item').each(function(k) {
      var $this  = $(this),
        top    = $this.offset().top - offsetTop,
        height = $this.outerHeight();

      // If this item is above the top of the viewport, skip to the next
      // post. If it's below the bottom of the viewport, break out of the
      // loop.
      if (top + height < scrollTop) {
        return;
      }
      if (top > scrollTop + windowHeight) {
        return false;
      }

      // If the bottom half of this item is visible at the top of the
      // viewport, then add the visible proportion to the visiblePosts
      // counter, and set the scrollbar index to whatever the visible
      // proportion represents. For example, if a gap represents indexes
      // 0-9, and the bottom 50% of the gap is visible in the viewport,
      // then the scrollbar index will be 5.
      if (top <= scrollTop && top + height > scrollTop) {
        visiblePosts = (top + height - scrollTop) / height;
        index = parseFloat($this.data('end')) + 1 - visiblePosts;
      }

      // If the top half of this item is visible at the bottom of the
      // viewport, then add the visible proportion to the visiblePosts
      // counter.
      else if (top + height >= scrollTop + windowHeight) {
        visiblePosts += (scrollTop + windowHeight - top) / height;
      }

      // If the whole item is visible in the viewport, then increment the 
      // visiblePosts counter.
      else {
        visiblePosts++;
      }

      // If this item has a time associated with it, then set the
      // scrollbar's current period to a formatted version of this time.
      if ($this.data('time')) {
        period = $this.data('time');
      }
    });

    // Now that we've looped through all of the items and have worked out
    // the scrollbar's current index and the number of posts visible in the
    // viewport, we can update the scrollbar.
    view.scrollbar.setIndex(index);
    view.scrollbar.setVisible(visiblePosts);
    view.scrollbar.update();

    view.scrollbar.$.find('.index').text(Math.ceil(index + visiblePosts));
    view.scrollbar.$.find('.description').text(moment(period).format('MMMM YYYY'));
  },

  mouseWasMoved: function(event) {
    var view = event.data.view;

    if ( ! event.data.handle) {
      return;
    }

    var offsetPixels = event.clientY - event.data.mouseStart;
    var offsetPercent = offsetPixels / view.scrollbar.$.outerHeight() * 100;

    var offsetIndex = offsetPercent / view.scrollbar.percentPerPost().index;
    var newIndex = Math.max(0, Math.min(event.data.indexStart + offsetIndex, view.scrollbar.count - 1));

    view.scrollToIndex(newIndex);
  },

  mouseWasReleased: function(event) {
    var view = event.data.view;

    if (! event.data.handle) {
      return;
    }

    event.data.mouseStart = 0;
    event.data.indexStart = 0;
    event.data.handle = null;

    $(window).data('disableScrollHandler', false);

    view.get('controller').send('jumpToIndex', Math.floor(view.scrollbar.index));

    $(window).scroll();
    $('body').css('cursor', '');
  },
  
  didInsertElement: function() {
    var view = this;

    // Set up scrollbar object
    this.scrollbar = new Scrollbar($('.discussion-scrubber .scrollbar'));
    this.scrollbar.setDisabled(true);
    this.countChanged();
    this.loadedChanged();

    // Whenever the window is resized, adjust the height of the scrollbar
    // so that it fills the height of the sidebar.
    $(window).on('resize', {view: this}, this.windowWasResized).resize();

    // Define a handler to update the state of the scrollbar to reflect the
    // current scroll position of the page.
    $(window).on('scroll', {view: this}, this.windowWasScrolled);

    this.get('controller').on('loadingIndex', this, this.loadingIndex);

    // Now we want to make the scrollbar handle draggable. Let's start by
    // preventing default browser events from messing things up.
    this.scrollbar.$
      .css('user-select', 'none')
      .bind('dragstart mousedown', function(e) {
        e.preventDefault();
      });
    
    // When the mouse is pressed on the scrollbar handle, we need to capture
    // some information about the current position.
    var scrollData = {
      view: this,
      mouseStart: 0,
      indexStart: 0,
      handle: null
    };
    
    this.scrollbar.$.find('.scrollbar-slider').css('cursor', 'move').mousedown(function(e) {
      scrollData.mouseStart = e.clientY;
      scrollData.indexStart = view.scrollbar.index;
      scrollData.handle = $(this);
      $(window).data('disableScrollHandler', true);
      $('body').css('cursor', 'move');
    });

    // When the mouse moves, 
    $(document)
      .on('mousemove', scrollData, this.mouseWasMoved)
      .on('mouseup', scrollData, this.mouseWasReleased);

    // When any part of the whole scrollbar is clicked, we want to jump to
    // that position.
    this.scrollbar.$.click(function(e) {

      // Calculate the index which we want to jump to.
      // @todo document how this complexity works.
      var offsetPixels = e.clientY - view.scrollbar.$.offset().top + $('body').scrollTop();
      var offsetPercent = offsetPixels / view.scrollbar.$.outerHeight() * 100;

      var handleHeight = parseFloat(view.scrollbar.$.find('.scrollbar-slider')[0].style.height);

      var offsetIndex = (offsetPercent - handleHeight / 2) / view.scrollbar.percentPerPost().index;
      var newIndex = Math.max(0, Math.min(view.scrollbar.count - 1, offsetIndex));

      view.get('controller').send('jumpToIndex', Math.floor(newIndex));
    })

    // Exempt the scrollbar handle from this 'jump to' click event.
    this.scrollbar.$.find('.scrollbar-slider').click(function(e) {
      e.stopPropagation();
    });
  
  },

  actions: {
    firstPost: function() {
      this.get('controller').send('jumpToIndex', 0);
    },

    lastPost: function() {
      this.get('controller').send('jumpToIndex', this.scrollbar.count - 1);
    }
  },

  loadingIndex: function(index) {
    this.scrollToIndex(index, true);
  },

  // Instantly scroll to a certain index in the discussion. The index doesn't
  // have to be an integer; any fraction of a post will be scrolled to.
  scrollToIndex: function(index, animate) {
    index = Math.max(0, Math.min(index, this.scrollbar.count - 1));
    var indexFloor = Math.floor(index);

    // Find 
    var nearestItem = this.findNearestToIndex(indexFloor);
    var first = $('.posts .item[data-start=0]');
    var offsetTop = first.length ? first.offset().top : 0;

    var pos = nearestItem.offset().top - offsetTop;
    if (! nearestItem.is('.gap')) {
      pos += nearestItem.outerHeight() * (index - indexFloor);
    } else {
      nearestItem.addClass('active');
    }

    $('.posts .item.gap').not(nearestItem).removeClass('active');

    if (animate) {
      // $('html, body').animate({scrollTop: pos});
    } else {
      $('html, body').scrollTop(pos);
    }
    this.scrollbar.setIndex(index);
    this.scrollbar.update(animate);
  },

  willDestroyElement: function() {
    $(window)
      .off('resize', this.windowWasResized)
      .off('scroll', this.windowWasScrolled);

    $(document)
      .off('mousemove', this.mouseWasMoved)
      .off('mouseup', this.mouseWasReleased);

    this.get('controller').off('loadingIndex', this, this.loadingIndex);
  }
});
