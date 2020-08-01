import Component from '../../common/Component';
import icon from '../../common/helpers/icon';
import ScrollListener from '../../common/utils/ScrollListener';
import SubtreeRetainer from '../../common/utils/SubtreeRetainer';
import formatNumber from '../../common/utils/formatNumber';

/**
 * The `PostStreamScrubber` component displays a scrubber which can be used to
 * navigate/scrub through a post stream.
 *
 * ### Props
 *
 * - `stream`
 * - `className`
 * - `onNavigate`
 * - `count`
 * - `paused`
 * - `index`
 * - `visible`
 * - `description`
 */
export default class PostStreamScrubber extends Component {
  init() {
    this.stream = this.props.stream;
    this.handlers = {};

    // Define a handler to update the state of the scrollbar to reflect the
    // current scroll position of the page.
    this.scrollListener = new ScrollListener(this.renderScrollbar.bind(this, { fromScroll: true, forceHeightChange: true }));

    // Create a subtree retainer that will always cache the subtree after the
    // initial draw. We render parts of the scrubber using this because we
    // modify their DOM directly, and do not want Mithril messing around with
    // our changes.
    this.subtree = new SubtreeRetainer(() => true);
  }

  view() {
    const retain = this.subtree.retain();
    const { count, index, visible } = this.props;
    const unreadCount = this.stream.discussion.unreadCount();
    const unreadPercent = count ? Math.min(count - this.props.index, unreadCount) / count : 0;

    const viewing = app.translator.transChoice('core.forum.post_scrubber.viewing_text', count, {
      index: <span className="Scrubber-index">{retain || formatNumber(Math.min(Math.ceil(index + visible), count))}</span>,
      count: <span className="Scrubber-count">{formatNumber(count)}</span>,
    });

    function styleUnread(element, isInitialized, context) {
      const $element = $(element);
      const newStyle = {
        top: 100 - unreadPercent * 100 + '%',
        height: unreadPercent * 100 + '%',
      };

      if (context.oldStyle) {
        $element.stop(true).css(context.oldStyle).animate(newStyle);
      } else {
        $element.css(newStyle);
      }

      context.oldStyle = newStyle;
    }

    return (
      <div className={'PostStreamScrubber Dropdown ' + (this.disabled() ? 'disabled ' : '') + (this.props.className || '')}>
        <button className="Button Dropdown-toggle" data-toggle="dropdown">
          {viewing} {icon('fas fa-sort')}
        </button>

        <div className="Dropdown-menu dropdown-menu">
          <div className="Scrubber">
            <a className="Scrubber-first" onclick={this.goToFirst.bind(this)}>
              {icon('fas fa-angle-double-up')} {app.translator.trans('core.forum.post_scrubber.original_post_link')}
            </a>

            <div className="Scrubber-scrollbar">
              <div className="Scrubber-before" />
              <div className="Scrubber-handle">
                <div className="Scrubber-bar" />
                <div className="Scrubber-info">
                  <strong>{viewing}</strong>
                  <span className="Scrubber-description">{retain || this.props.description}</span>
                </div>
              </div>
              <div className="Scrubber-after" />

              <div className="Scrubber-unread" config={styleUnread}>
                {app.translator.trans('core.forum.post_scrubber.unread_text', { count: unreadCount })}
              </div>
            </div>

            <a className="Scrubber-last" onclick={this.goToLast.bind(this)}>
              {icon('fas fa-angle-double-down')} {app.translator.trans('core.forum.post_scrubber.now_link')}
            </a>
          </div>
        </div>
      </div>
    );
  }

  /**
   * Go to the first post in the discussion.
   */
  goToFirst() {
    this.navigateTo(0);
  }

  /**
   * Go to the last post in the discussion.
   */
  goToLast() {
    this.navigateTo(this.props.count - 1);
  }

  /**
   * Check whether or not the scrubber should be disabled, i.e. if all of the
   * posts are visible in the viewport.
   *
   * @return {Boolean}
   */
  disabled() {
    return this.props.visible >= this.props.count;
  }

  config(isInitialized, context) {
    if (isInitialized) return;

    context.onunload = this.ondestroy.bind(this);

    this.scrollListener.start();

    // Whenever the window is resized, adjust the height of the scrollbar
    // so that it fills the height of the sidebar.
    $(window)
      .on('resize', (this.handlers.onresize = this.onresize.bind(this)))
      .resize();

    // When any part of the whole scrollbar is clicked, we want to jump to
    // that position.
    this.$('.Scrubber-scrollbar')
      .bind('click', this.onclick.bind(this))

      // Now we want to make the scrollbar handle draggable. Let's start by
      // preventing default browser events from messing things up.
      .css({ cursor: 'pointer', 'user-select': 'none' })
      .bind('dragstart mousedown touchstart', (e) => e.preventDefault());

    // When the mouse is pressed on the scrollbar handle, we capture some
    // information about its current position. We will store this
    // information in an object and pass it on to the document's
    // mousemove/mouseup events later.
    this.dragging = false;
    this.mouseStart = 0;
    this.indexStart = 0;
    this.dragIndex = null;

    this.$('.Scrubber-handle')
      .css('cursor', 'move')
      .bind('mousedown touchstart', this.onmousedown.bind(this))

      // Exempt the scrollbar handle from the 'jump to' click event.
      .click((e) => e.stopPropagation());

    // When the mouse moves and when it is released, we pass the
    // information that we captured when the mouse was first pressed onto
    // some event handlers. These handlers will move the scrollbar/stream-
    // content as appropriate.
    $(document)
      .on('mousemove touchmove', (this.handlers.onmousemove = this.onmousemove.bind(this)))
      .on('mouseup touchend', (this.handlers.onmouseup = this.onmouseup.bind(this)));
  }

  ondestroy() {
    this.scrollListener.stop();

    $(window).off('resize', this.handlers.onresize);

    $(document).off('mousemove touchmove', this.handlers.onmousemove).off('mouseup touchend', this.handlers.onmouseup);
  }

  /**
   * Update the scrollbar's position to reflect the current values of the
   * index/visible properties.
   *
   * @param {Boolean} animate
   */
  renderScrollbar(options = {}) {
    const { count, visible, description, paused } = this.props;
    const percentPerPost = this.percentPerPost();

    const index = this.dragIndex || this.props.index;

    const $scrubber = this.$();
    $scrubber.find('.Scrubber-index').text(formatNumber(Math.min(Math.ceil(index + visible), count)));
    $scrubber.find('.Scrubber-description').text(description);
    $scrubber.toggleClass('disabled', this.disabled());

    const heights = {};
    heights.before = Math.max(0, percentPerPost.index * Math.min(index - 1, count - visible));
    heights.handle = Math.min(100 - heights.before, percentPerPost.visible * visible);
    heights.after = 100 - heights.before - heights.handle;

    // If the stream is paused, don't change height on scroll, as the viewport is being scrolled by the JS
    // If a height change animation is already in progress, don't adjust height unless overridden
    if ((options.fromScroll && paused) || (this.adjustingHeight && !options.forceHeightChange)) return;

    const func = options.animate ? 'animate' : 'css';
    this.adjustingHeight = true;
    const animationPromises = [];
    for (const part in heights) {
      const $part = $scrubber.find(`.Scrubber-${part}`);
      animationPromises.push(
        $part
          .stop(true, true)
          [func]({ height: heights[part] + '%' }, 'fast')
          .promise()
      );

      // jQuery likes to put overflow:hidden, but because the scrollbar handle
      // has a negative margin-left, we need to override.
      if (func === 'animate') $part.css('overflow', 'visible');
    }
    Promise.all(animationPromises).then(() => (this.adjustingHeight = false));
  }

  /**
   * Get the percentage of the height of the scrubber that should be allocated
   * to each post.
   *
   * @return {Object}
   * @property {Number} index The percent per post for posts on either side of
   *     the visible part of the scrubber.
   * @property {Number} visible The percent per post for the visible part of the
   *     scrubber.
   */
  percentPerPost() {
    const count = this.props.count || 1;
    const visible = this.props.visible || 1;

    // To stop the handle of the scrollbar from getting too small when there
    // are many posts, we define a minimum percentage height for the handle
    // calculated from a 50 pixel limit. From this, we can calculate the
    // minimum percentage per visible post. If this is greater than the actual
    // percentage per post, then we need to adjust the 'before' percentage to
    // account for it.
    const minPercentVisible = (50 / this.$('.Scrubber-scrollbar').outerHeight()) * 100;
    const percentPerVisiblePost = Math.max(100 / count, minPercentVisible / visible);
    const percentPerPost = count === visible ? 0 : (100 - percentPerVisiblePost * visible) / (count - visible);

    return {
      index: percentPerPost,
      visible: percentPerVisiblePost,
    };
  }

  onresize() {
    this.scrollListener.update();

    // Adjust the height of the scrollbar so that it fills the height of
    // the sidebar and doesn't overlap the footer.
    const scrubber = this.$();
    const scrollbar = this.$('.Scrubber-scrollbar');

    scrollbar.css(
      'max-height',
      $(window).height() -
        scrubber.offset().top +
        $(window).scrollTop() -
        parseInt($('#app').css('padding-bottom'), 10) -
        (scrubber.outerHeight() - scrollbar.outerHeight())
    );
  }

  onmousedown(e) {
    e.redraw = false;
    this.mouseStart = e.clientY || e.originalEvent.touches[0].clientY;
    this.indexStart = this.props.index;
    this.dragging = true;
    this.dragIndex = null;
    $('body').css('cursor', 'move');
    this.$().toggleClass('dragging', this.dragging);
  }

  onmousemove(e) {
    if (!this.dragging) return;

    // Work out how much the mouse has moved by - first in pixels, then
    // convert it to a percentage of the scrollbar's height, and then
    // finally convert it into an index. Add this delta index onto
    // the index at which the drag was started, and then scroll there.
    const deltaPixels = (e.clientY || e.originalEvent.touches[0].clientY) - this.mouseStart;
    const deltaPercent = (deltaPixels / this.$('.Scrubber-scrollbar').outerHeight()) * 100;
    const deltaIndex = deltaPercent / this.percentPerPost().index || 0;
    const newIndex = Math.min(this.indexStart + deltaIndex, this.props.count - 1);

    this.dragIndex = Math.max(0, newIndex);
    this.renderScrollbar();
  }

  onmouseup() {
    this.$().toggleClass('dragging', this.dragging);
    if (!this.dragging) return;

    this.mouseStart = 0;
    this.indexStart = 0;
    this.dragging = false;
    $('body').css('cursor', '');

    this.$().removeClass('open');

    // If the index we've landed on is in a gap, then tell the stream-
    // content that we want to load those posts.
    this.navigateTo(this.dragIndex);

    this.dragIndex = null;
  }

  onclick(e) {
    // Calculate the index which we want to jump to based on the click position.

    // 1. Get the offset of the click from the top of the scrollbar, as a
    //    percentage of the scrollbar's height.
    const $scrollbar = this.$('.Scrubber-scrollbar');
    const offsetPixels = (e.pageY || e.originalEvent.touches[0].pageY) - $scrollbar.offset().top + $('body').scrollTop();
    let offsetPercent = (offsetPixels / $scrollbar.outerHeight()) * 100;

    // 2. We want the handle of the scrollbar to end up centered on the click
    //    position. Thus, we calculate the height of the handle in percent and
    //    use that to find a new offset percentage.
    offsetPercent = offsetPercent - parseFloat($scrollbar.find('.Scrubber-handle')[0].style.height) / 2;

    // 3. Now we can convert the percentage into an index, and tell the stream-
    //    content component to jump to that index.
    let offsetIndex = offsetPercent / this.percentPerPost().index;
    offsetIndex = Math.max(0, Math.min(this.props.count - 1, offsetIndex));

    this.navigateTo(offsetIndex);

    this.$().removeClass('open');
  }

  /**
   * Trigger post stream navigation, but also animate the scrollbar according
   * to the expected result.
   *
   * @param {number} index
   */
  navigateTo(index) {
    this.props.onNavigate(Math.floor(index));
    this.renderScrollbar({ animate: true });
  }
}
