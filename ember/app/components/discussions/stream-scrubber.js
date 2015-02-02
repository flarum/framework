import Ember from 'ember';

var $ = Ember.$;

export default Ember.Component.extend({
	layoutName: 'components/discussions/stream-scrubber',
	classNames: ['scrubber', 'stream-scrubber'],
	classNameBindings: ['disabled'],

	// The stream-content component to which this scrubber is linked.
	streamContent: null,
	stream: Ember.computed.alias('streamContent.stream'),
	loaded: Ember.computed.alias('streamContent.loaded'),
	count: Ember.computed.alias('stream.count'),

	// The current index of the stream visible at the top of the viewport, and
	// the number of items visible within the viewport. These aren't
	// necessarily integers.
	index: -1,
	visible: 1,

	// The integer index of the last item that is visible in the viewport. This
	// is display on the scrubber (i.e. X of 100 posts).
	visibleIndex: function() {
		return Math.min(this.get('count'), Math.ceil(Math.max(0, this.get('index')) + this.get('visible')));
	}.property('index', 'visible'),

	// The description displayed alongside the index in the scrubber. This is
	// set to the date of the first visible post in the scroll event.
	description: '',

	// Disable the scrubber if the stream's initial content isn't loaded, or
	// if all of the posts in the discussion are visible in the viewport.
	disabled: function() {
		return ! this.get('loaded') || this.get('visible') >= this.get('count');
	}.property('loaded', 'visible', 'count'),

	// Whenever the stream object changes to a new one (i.e. when
	// transitioning to a different discussion,) reset some properties and
	// update the scrollbar to a neutral state.
	refresh: function() {
		this.set('index', -1);
		this.set('visible', 1);
		this.updateScrollbar();
	}.observes('stream'),

	didInsertElement: function() {
		var view = this;

		// When the stream-content component begins loading posts at a certain
		// index, we want our scrubber scrollbar to jump to that position.
		this.get('streamContent').on('loadingIndex', this, this.loadingIndex);

		// Whenever the window is resized, adjust the height of the scrollbar
		// so that it fills the height of the sidebar.
		$(window).on('resize', {view: this}, this.windowWasResized).resize();

		// Define a handler to update the state of the scrollbar to reflect the
		// current scroll position of the page.
		$(window).on('scroll', {view: this}, this.windowWasScrolled);		

		// When any part of the whole scrollbar is clicked, we want to jump to
		// that position.
		this.$('.scrubber-scrollbar')
			.click(function(e) {
				if (! view.get('streamContent.active')) {
					return;
				}

				// Calculate the index which we want to jump to based on the
				// click position.
				// 1. Get the offset of the click from the top of the
				//    scrollbar, as a percentage of the scrollbar's height.
				var $this = $(this),
				    offsetPixels = e.clientY - $this.offset().top + $('body').scrollTop(),
				    offsetPercent = offsetPixels / $this.outerHeight() * 100;

				// 2. We want the handle of the scrollbar to end up centered
				//    on the click position. Thus, we calculate the height of
				//    the handle in percent and use that to find a new
				//    offset percentage.
				offsetPercent = offsetPercent - parseFloat($this.find('.scrubber-slider')[0].style.height) / 2;

				// 3. Now we can convert the percentage into an index, and
				//    tell the stream-content component to jump to that index.
				var offsetIndex = offsetPercent / view.percentPerPost().index;
				offsetIndex = Math.max(0, Math.min(view.get('count') - 1, offsetIndex));
				view.get('streamContent').send('goToIndex', Math.floor(offsetIndex));
			});

		// Now we want to make the scrollbar handle draggable. Let's start by
		// preventing default browser events from messing things up.
		this.$('.scrubber-scrollbar')
			.css({
		    	cursor: 'pointer',
		    	'user-select': 'none'
		    })
		    .bind('dragstart mousedown', function(e) {
				e.preventDefault();
			});
			
		// When the mouse is pressed on the scrollbar handle, we capture some
		// information about its current position. We will store this
		// information in an object and pass it on to the document's
		// mousemove/mouseup events later.
		var dragData = {
			view: this,
			mouseStart: 0,
			indexStart: 0,
			handle: null
		};
		this.$('.scrubber-slider')
			.css('cursor', 'move')
			.mousedown(function(e) {
				dragData.mouseStart = e.clientY;
				dragData.indexStart = view.get('index');
				dragData.handle = $(this);
				view.set('streamContent.paused', true);
				$('body').css('cursor', 'move');
			})
			// Exempt the scrollbar handle from the 'jump to' click event.
			.click(function(e) {
				e.stopPropagation();
			});

		// When the mouse moves and when it is released, we pass the
		// information that we captured when the mouse was first pressed onto
		// some event handlers. These handlers will move the scrollbar/stream-
		// content as appropriate.
		$(document)
			.on('mousemove', dragData, this.mouseWasMoved)
			.on('mouseup', dragData, this.mouseWasReleased);

		// Finally, we'll just make sure the scrollbar is in the correct
		// position according to the values of this.index/visible.
		this.updateScrollbar(true);
	},

	willDestroyElement: function() {
		this.get('streamContent').off('loadingIndex', this, this.loadingIndex);

		$(window)
			.off('resize', this.windowWasResized)
			.off('scroll', this.windowWasScrolled);

		$(document)
			.off('mousemove', this.mouseWasMoved)
			.off('mouseup', this.mouseWasReleased);
	},

	// When the stream-content component begins loading posts at a certain
	// index, we want our scrubber scrollbar to jump to that position.
	loadingIndex: function(index) {
		this.set('index', index);
		this.updateScrollbar(true);
	},

	windowWasResized: function(event) {
		var view = event.data.view;
		view.windowWasScrolled(event);

		// Adjust the height of the scrollbar so that it fills the height of
		// the sidebar and doesn't overlap the footer.
		var scrollbar = view.$('.scrubber-scrollbar');
		scrollbar.css('max-height', $(window).height() - scrollbar.offset().top + $(window).scrollTop() - $('#footer').outerHeight(true));
	},

	windowWasScrolled: function(event) {
		var view = event.data.view;
		if (view.get('streamContent.active')) {
			view.update();
			view.updateScrollbar();
		}
	},

	mouseWasMoved: function(event) {
		if (! event.data.handle) {
			return;
		}
		var view = event.data.view;

		// Work out how much the mouse has moved by - first in pixels, then
		// convert it to a percentage of the scrollbar's height, and then
		// finally convert it into an index. Add this delta index onto
		// the index at which the drag was started, and then scroll there.
		var deltaPixels = event.clientY - event.data.mouseStart,
		    deltaPercent = deltaPixels / view.$('.scrubber-scrollbar').outerHeight() * 100,
		    deltaIndex = deltaPercent / view.percentPerPost().index,
		    newIndex = Math.min(event.data.indexStart + deltaIndex, view.get('count') - 1);

		view.set('index', Math.max(0, newIndex));
		view.updateScrollbar();
		view.scrollToIndex(newIndex);
	},

	mouseWasReleased: function(event) {
		if (! event.data.handle) {
			return;
		}
		event.data.mouseStart = 0;
		event.data.indexStart = 0;
		event.data.handle = null;
		$('body').css('cursor', '');

		var view = event.data.view;

		// If the index we've landed on is in a gap, then tell the stream-
		// content that we want to load those posts.
		var intIndex = Math.floor(view.get('index'));
		if (! view.get('stream').findNearestToIndex(intIndex).content) {
			view.get('streamContent').send('goToIndex', intIndex);
		} else {
			view.set('streamContent.paused', false);
		}
	},

	// When the stream-content component resumes being 'active' (for example,
	// after a bunch of posts have been loaded), then we want to update the
	// scrubber scrollbar according to the window's current scroll position.
	resume: function() {
		if (this.get('streamContent.active')) {
			this.update();
			this.updateScrollbar(true);
		}
	}.observes('streamContent.active'),

	// Update the index/visible/description properties according to the
	// window's current scroll position.
	update: function() {
		if (! this.get('streamContent.active')) {
			return;
		}

		var $window = $(window),
		    marginTop = this.get('streamContent').getMarginTop(),
		    scrollTop = $window.scrollTop() + marginTop,
		    windowHeight = $window.height() - marginTop;

		// Before looping through all of the posts, we reset the scrollbar
		// properties to a 'default' state. These values reflect what would be
		// seen if the browser were scrolled right up to the top of the page,
		// and the viewport had a height of 0.
		var $items = this.get('streamContent').$().find('.item');
		var index = $items.first().data('end') - 1;
		var visible = 0;
		var period = '';

		// Now loop through each of the items in the discussion. An 'item' is
		// either a single post or a 'gap' of one or more posts that haven't
		// been loaded yet.
		$items.each(function() {
			var $this  = $(this),
				top    = $this.offset().top,
				height = $this.outerHeight(true);

			// If this item is above the top of the viewport, skip to the next
			// post. If it's below the bottom of the viewport, break out of the
			// loop.
			if (top + height < scrollTop) {
				visible = (top + height - scrollTop) / height;
				index = parseFloat($this.data('end')) + 1 - visible;
				return;
			}
			if (top > scrollTop + windowHeight) {
				return false;
			}

			// If the bottom half of this item is visible at the top of the
			// viewport, then add the visible proportion to the visible
			// counter, and set the scrollbar index to whatever the visible
			// proportion represents. For example, if a gap represents indexes
			// 0-9, and the bottom 50% of the gap is visible in the viewport,
			// then the scrollbar index will be 5.
			if (top <= scrollTop && top + height > scrollTop) {
				visible = (top + height - scrollTop) / height;
				index = parseFloat($this.data('end')) + 1 - visible;
			}

			// If the top half of this item is visible at the bottom of the
			// viewport, then add the visible proportion to the visible
			// counter.
			else if (top + height >= scrollTop + windowHeight) {
				visible += (scrollTop + windowHeight - top) / height;
			}

			// If the whole item is visible in the viewport, then increment the 
			// visible counter.
			else {
				visible++;
			}

			// If this item has a time associated with it, then set the
			// scrollbar's current period to a formatted version of this time.
			if ($this.data('time')) {
				period = $this.data('time');
			}
		});

		this.set('index', index);
		this.set('visible', visible);
		this.set('description', period ? moment(period).format('MMMM YYYY') : '');
	},

	// Update the scrollbar's position to reflect the current values of the
    // index/visible properties.
    updateScrollbar: function(animate) {
        var percentPerPost = this.percentPerPost(),
            index = this.get('index'),
            count = this.get('count'),
            visible = this.get('visible');

        var heights = {};
        heights.before = Math.max(0, percentPerPost.index * Math.min(index, count - visible));
        heights.slider = Math.min(100 - heights.before, percentPerPost.visible * visible);
        heights.after = 100 - heights.before - heights.slider;

        var $scrubber = this.$();
    	var func = animate ? 'animate' : 'css';
        for (var part in heights) {
        	var $part = $scrubber.find('.scrubber-'+part);
        	$part.stop(true, true)[func]({height: heights[part]+'%'});

        	// jQuery likes to put overflow:hidden, but because the scrollbar
        	// handle has a negative margin-left, we need to override.
        	if (func === 'animate') {
        		$part.css('overflow', 'visible');
        	}
        }
    },

	// Instantly scroll to a certain index in the discussion. The index doesn't
	// have to be an integer; any fraction of a post will be scrolled to.
	scrollToIndex: function(index) {
		index = Math.min(index, this.get('count') - 1);

		// Find the item for this index, whether it's a post corresponding to
		// the index, or a gap which the index is within.
		var indexFloor = Math.max(0, Math.floor(index)),
		    $nearestItem = this.get('streamContent').findNearestToIndex(indexFloor);

		// Calculate the position of this item so that we can scroll to it. If
		// the item is a gap, then we will mark it as 'active' to indicate to
		// the user that it will expand if they release their mouse.
		// Otherwise, we will add a proportion of the item's height onto the
		// scroll position.
		var pos = $nearestItem.offset().top - this.get('streamContent').getMarginTop();
		if ($nearestItem.is('.gap')) {
			$nearestItem.addClass('active');
		} else {
			if (index >= 0) {
				pos += $nearestItem.outerHeight(true) * (index - indexFloor);
			} else {
				pos += $nearestItem.offset().top * index;
			}
		}

		// Remove the 'active' class from other gaps. 
		this.get('streamContent').$().find('.gap').not($nearestItem).removeClass('active');

		$('html, body').scrollTop(pos);		
	},

	percentPerPost: function() {
		var count = this.get('count') || 1,
		    visible = this.get('visible');

        // To stop the slider of the scrollbar from getting too small when there
        // are many posts, we define a minimum percentage height for the slider
        // calculated from a 50 pixel limit. From this, we can calculate the
        // minimum percentage per visible post. If this is greater than the
        // actual percentage per post, then we need to adjust the 'before'
        // percentage to account for it.
        var minPercentVisible = 50 / this.$('.scrubber-scrollbar').outerHeight() * 100;
        var percentPerVisiblePost = Math.max(100 / count, minPercentVisible / visible);
        var percentPerPost = count === visible ? 0 : (100 - percentPerVisiblePost * visible) / (count - visible);

        return {
            index: percentPerPost,
            visible: percentPerVisiblePost
        };
    },

    actions: {
		first: function() {
			this.get('streamContent').send('goToFirst');
		},

		last: function() {
			this.get('streamContent').send('goToLast');
		}
	}
});
