import Component from 'flarum/component';
import icon from 'flarum/helpers/icon';
import ScrollListener from 'flarum/utils/scroll-listener';
import SubtreeRetainer from 'flarum/utils/subtree-retainer';
import computed from 'flarum/utils/computed';
import formatNumber from 'flarum/utils/format-number';

/**

 */
export default class PostScrubber extends Component {
  /**

   */
  constructor(props) {
    super(props);

    var stream = this.props.stream;
    this.handlers = {};

    // When the stream-content component begins loading posts at a certain
    // index, we want our scrubber scrollbar to jump to that position.
    stream.on('unpaused', this.handlers.unpaused = this.unpaused.bind(this));

    /**
      The integer index of the last item that is visible in the viewport. This
      is display on the scrubber (i.e. X of 100 posts).
     */
    this.visibleIndex = computed('index', 'visible', 'count', function(index, visible, count) {
      return Math.min(count, Math.ceil(Math.max(0, index) + visible));
    });

    this.count = () => this.props.stream.count();
    this.index = m.prop(-1);
    this.visible = m.prop(1);
    this.description = m.prop();

    // Define a handler to update the state of the scrollbar to reflect the
    // current scroll position of the page.
    this.scrollListener = new ScrollListener(this.onscroll.bind(this));

    this.subtree = new SubtreeRetainer(() => true);
  }

  unpaused() {
    this.update(window.pageYOffset);
    this.renderScrollbar(true);
  }

  /**
    Disable the scrubber if the stream's initial content isn't loaded, or
    if all of the posts in the discussion are visible in the viewport.
   */
  disabled() {
    return this.visible() >= this.count();
  }

  /**

   */
  view() {
    var retain = this.subtree.retain();
    var stream = this.props.stream;
    var unreadCount = this.props.stream.discussion.unreadCount();
    var unreadPercent = unreadCount / this.count();

    // @todo clean up duplication
    return m('div.stream-scrubber.dropdown'+(this.disabled() ? '.disabled' : ''), {config: this.onload.bind(this)}, [
      m('a.btn.btn-default.dropdown-toggle[href=javascript:;][data-toggle=dropdown]', [
        m('span.index', retain || formatNumber(this.visibleIndex())), ' of ', m('span.count', formatNumber(this.count())), ' posts ',
        icon('sort icon-glyph')
      ]),
      m('div.dropdown-menu', [
        m('div.scrubber', [
          m('a.scrubber-first[href=javascript:;]', {onclick: () => {
            stream.goToFirst();
            this.index(0);
            this.renderScrollbar(true);
          }}, [icon('angle-double-up'), ' Original Post']),
          m('div.scrubber-scrollbar', [
            m('div.scrubber-before'),
            m('div.scrubber-slider', [
              m('div.scrubber-handle'),
              m('div.scrubber-info', [
                m('strong', [m('span.index', retain || formatNumber(this.visibleIndex())), ' of ', m('span.count', formatNumber(this.count())), ' posts']),
                m('span.description', retain || this.description())
              ])
            ]),
            m('div.scrubber-after'),
            (app.session.user() && unreadPercent) ? m('div.scrubber-unread', {
              style: {top: (100 - unreadPercent * 100)+'%', height: (unreadPercent * 100)+'%'},
              config: function(element, isInitialized, context) {
                var $element = $(element);
                var newStyle = {top: (100 - unreadPercent * 100)+'%', height: (unreadPercent * 100)+'%'};
                if (context.oldStyle) {
                  $element.stop(true).css(context.oldStyle).animate(newStyle);
                }
                context.oldStyle = newStyle;
              }
            }, formatNumber(unreadCount)+' unread') : ''
          ]),
          m('a.scrubber-last[href=javascript:;]', {onclick: () => {
            stream.goToLast();
            this.index(stream.count());
            this.renderScrollbar(true);
          }}, [icon('angle-double-down'), ' Now'])
        ])
      ])
    ])
  }

  onscroll(top) {
    var stream = this.props.stream;

    if (stream.paused() || !stream.$()) { return; }

    this.update(top);
    this.renderScrollbar();
  }

  /**
    Update the index/visible/description properties according to the window's
    current scroll position.
   */
  update(top) {
    var stream = this.props.stream;

    var $window = $(window);
    var marginTop = stream.getMarginTop();
    var scrollTop = $window.scrollTop() + marginTop;
    var windowHeight = $window.height() - marginTop;

    // Before looping through all of the posts, we reset the scrollbar
    // properties to a 'default' state. These values reflect what would be
    // seen if the browser were scrolled right up to the top of the page,
    // and the viewport had a height of 0.
    var $items = stream.$('> .item[data-index]');
    var index = $items.first().data('index') || 0;
    var visible = 0;
    var period = '';

    // Now loop through each of the items in the discussion. An 'item' is
    // either a single post or a 'gap' of one or more posts that haven't
    // been loaded yet.
    $items.each(function() {
      var $this = $(this);
      var top = $this.offset().top;
      var height = $this.outerHeight(true);

      // If this item is above the top of the viewport, skip to the next
      // post. If it's below the bottom of the viewport, break out of the
      // loop.
      if (top + height < scrollTop) {
        visible = (top + height - scrollTop) / height;
        index = parseFloat($this.data('index')) + 1 - visible;
        return;
      }
      if (top > scrollTop + windowHeight) {
        return false;
      }

      // If the bottom half of this item is visible at the top of the
      // viewport
      if (top <= scrollTop && top + height > scrollTop) {
        visible = (top + height - scrollTop) / height;
        index = parseFloat($this.data('index')) + 1 - visible;
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

    this.index(index);
    this.visible(visible);
    this.description(period ? moment(period).format('MMMM YYYY') : '');
  }

  /**

   */
  onload(element, isInitialized, context) {
    this.element(element);

    if (isInitialized) { return; }

    context.onunload = this.ondestroy.bind(this);

    this.scrollListener.start();

    // Whenever the window is resized, adjust the height of the scrollbar
    // so that it fills the height of the sidebar.
    $(window).on('resize', this.handlers.onresize = this.onresize.bind(this)).resize();

    // When any part of the whole scrollbar is clicked, we want to jump to
    // that position.
    this.$('.scrubber-scrollbar')
      .bind('click', this.onclick.bind(this))

      // Now we want to make the scrollbar handle draggable. Let's start by
      // preventing default browser events from messing things up.
      .css({ cursor: 'pointer', 'user-select': 'none' })
      .bind('dragstart mousedown touchstart', e => e.preventDefault());

    // When the mouse is pressed on the scrollbar handle, we capture some
    // information about its current position. We will store this
    // information in an object and pass it on to the document's
    // mousemove/mouseup events later.
    this.dragging = false;
    this.mouseStart = 0;
    this.indexStart = 0;

    this.$('.scrubber-slider')
      .css('cursor', 'move')
      .bind('mousedown touchstart', this.onmousedown.bind(this))

      // Exempt the scrollbar handle from the 'jump to' click event.
      .click(e => e.stopPropagation());

    // When the mouse moves and when it is released, we pass the
    // information that we captured when the mouse was first pressed onto
    // some event handlers. These handlers will move the scrollbar/stream-
    // content as appropriate.
    $(document)
      .on('mousemove touchmove', this.handlers.onmousemove = this.onmousemove.bind(this))
      .on('mouseup touchend', this.handlers.onmouseup = this.onmouseup.bind(this));
  }

  ondestroy() {
    this.scrollListener.stop();

    this.props.stream.off('unpaused', this.handlers.unpaused);

    $(window)
      .off('resize', this.handlers.onresize);

    $(document)
      .off('mousemove touchmove', this.handlers.onmousemove)
      .off('mouseup touchend', this.handlers.onmouseup);
  }

  /**
    Update the scrollbar's position to reflect the current values of the
    index/visible properties.
   */
  renderScrollbar(animate) {
    var percentPerPost = this.percentPerPost();
    var index = this.index();
    var count = this.count();
    var visible = this.visible() || 1;

    var $scrubber = this.$();
    $scrubber.find('.index').text(formatNumber(this.visibleIndex()));
    $scrubber.find('.description').text(this.description());
    $scrubber.toggleClass('disabled', this.disabled());

    var heights = {};
    heights.before = Math.max(0, percentPerPost.index * Math.min(index, count - visible));
    heights.slider = Math.min(100 - heights.before, percentPerPost.visible * visible);
    heights.after = 100 - heights.before - heights.slider;

    var func = animate ? 'animate' : 'css';
    for (var part in heights) {
      var $part = $scrubber.find('.scrubber-'+part);
      $part.stop(true, true)[func]({height: heights[part]+'%'}, 'fast');

      // jQuery likes to put overflow:hidden, but because the scrollbar handle
      // has a negative margin-left, we need to override.
      if (func === 'animate') {
        $part.css('overflow', 'visible');
      }
    }
  }

  /**

   */
  percentPerPost() {
    var count = this.count() || 1;
    var visible = this.visible() || 1;

    // To stop the slider of the scrollbar from getting too small when there
    // are many posts, we define a minimum percentage height for the slider
    // calculated from a 50 pixel limit. From this, we can calculate the
    // minimum percentage per visible post. If this is greater than the actual
    // percentage per post, then we need to adjust the 'before' percentage to
    // account for it.
    var minPercentVisible = 50 / this.$('.scrubber-scrollbar').outerHeight() * 100;
    var percentPerVisiblePost = Math.max(100 / count, minPercentVisible / visible);
    var percentPerPost = count === visible ? 0 : (100 - percentPerVisiblePost * visible) / (count - visible);

    return {
      index: percentPerPost,
      visible: percentPerVisiblePost
    };
  }

  onresize() {
    this.scrollListener.update(true);

    // Adjust the height of the scrollbar so that it fills the height of
    // the sidebar and doesn't overlap the footer.
    var scrollbar = this.$('.scrubber-scrollbar');
    scrollbar.css('max-height', $(window).height() - scrollbar.offset().top + $(window).scrollTop() - parseInt($('.global-page').css('padding-bottom')));
  }

  onmousedown(e) {
    this.mouseStart = e.clientY || e.originalEvent.touches[0].clientY;
    this.indexStart = this.index();
    this.dragging = true;
    this.props.stream.paused(true);
    $('body').css('cursor', 'move');
  }

  onmousemove(e) {
    if (! this.dragging) { return; }

    // Work out how much the mouse has moved by - first in pixels, then
    // convert it to a percentage of the scrollbar's height, and then
    // finally convert it into an index. Add this delta index onto
    // the index at which the drag was started, and then scroll there.
    var deltaPixels = (e.clientY || e.originalEvent.touches[0].clientY) - this.mouseStart;
    var deltaPercent = deltaPixels / this.$('.scrubber-scrollbar').outerHeight() * 100;
    var deltaIndex = deltaPercent / this.percentPerPost().index;
    var newIndex = Math.min(this.indexStart + deltaIndex, this.count() - 1);

    this.index(Math.max(0, newIndex));
    this.renderScrollbar();
  }

  onmouseup(e) {
    if (!this.dragging) { return; }
    this.mouseStart = 0;
    this.indexStart = 0;
    this.dragging = false;
    $('body').css('cursor', '');

    this.$().removeClass('open');

    // If the index we've landed on is in a gap, then tell the stream-
    // content that we want to load those posts.
    var intIndex = Math.floor(this.index());
    this.props.stream.goToIndex(intIndex);
    this.renderScrollbar(true);
  }

  onclick(e) {
    // Calculate the index which we want to jump to based on the click position.

    // 1. Get the offset of the click from the top of the scrollbar, as a
    //    percentage of the scrollbar's height.
    var $scrollbar = this.$('.scrubber-scrollbar');
    var offsetPixels = (e.clientY || e.originalEvent.touches[0].clientY) - $scrollbar.offset().top + $('body').scrollTop();
    var offsetPercent = offsetPixels / $scrollbar.outerHeight() * 100;

    // 2. We want the handle of the scrollbar to end up centered on the click
    //    position. Thus, we calculate the height of the handle in percent and
    //    use that to find a new offset percentage.
    offsetPercent = offsetPercent - parseFloat($scrollbar.find('.scrubber-slider')[0].style.height) / 2;

    // 3. Now we can convert the percentage into an index, and tell the stream-
    //    content component to jump to that index.
    var offsetIndex = offsetPercent / this.percentPerPost().index;
    offsetIndex = Math.max(0, Math.min(this.count() - 1, offsetIndex));
    this.props.stream.goToIndex(Math.floor(offsetIndex));
    this.index(offsetIndex);
    this.renderScrollbar(true);

    this.$().removeClass('open');
  }
}
