import Page from '../../common/components/Page';
import ItemList from '../../common/utils/ItemList';
import DiscussionHero from './DiscussionHero';
import PostStream from './PostStream';
import PostStreamScrubber from './PostStreamScrubber';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import SplitDropdown from '../../common/components/SplitDropdown';
import listItems from '../../common/helpers/listItems';
import DiscussionControls from '../utils/DiscussionControls';
import DiscussionList from './DiscussionList';
import PostStreamState from '../states/PostStreamState';
import ScrollListener from '../../common/utils/ScrollListener';

/**
 * The `DiscussionPage` component displays a whole discussion page, including
 * the discussion list pane, the hero, the posts, and the sidebar.
 */
export default class DiscussionPage extends Page {
  init() {
    super.init();

    /**
     * The discussion that is being viewed.
     *
     * @type {Discussion}
     */
    this.discussion = null;

    /**
     * The number of the first post that is currently visible in the viewport.
     *
     * @type {number}
     */
    this.near = m.route.param('near') || 0;

    this.scrollListener = new ScrollListener(this.onscroll.bind(this));

    this.load();

    // If the discussion list has been loaded, then we'll enable the pane (and
    // hide it by default). Also, if we've just come from another discussion
    // page, then we don't want Mithril to redraw the whole page – if it did,
    // then the pane would redraw which would be slow and would cause problems with
    // event handlers.
    if (app.discussions.hasDiscussions()) {
      app.pane.enable();
      app.pane.hide();

      if (app.previous.matches(DiscussionPage)) {
        m.redraw.strategy('diff');
      }
    }

    app.history.push('discussion');

    this.bodyClass = 'App--discussion';
  }

  onunload(e) {
    // If we have routed to the same discussion as we were viewing previously,
    // cancel the unloading of this controller and instead prompt the post
    // stream to jump to the new 'near' param.
    if (this.discussion) {
      const idParam = m.route.param('id');

      if (idParam && idParam.split('-')[0] === this.discussion.id()) {
        e.preventDefault();

        const near = m.route.param('near') || '1';

        if (near !== String(this.near)) {
          this.stream.goToNumber(near);
        }

        this.near = null;
        return;
      }
    }

    // If we are indeed navigating away from this discussion, then disable the
    // discussion list pane. Also, if we're composing a reply to this
    // discussion, minimize the composer – unless it's empty, in which case
    // we'll just close it.
    app.pane.disable();

    if (app.composer.composingReplyTo(this.discussion) && !app.composer.fields.content()) {
      app.composer.hide();
    } else {
      app.composer.minimize();
    }
  }

  view() {
    const discussion = this.discussion;

    return (
      <div className="DiscussionPage">
        {app.discussions.hasDiscussions() ? (
          <div className="DiscussionPage-list" config={this.configPane.bind(this)}>
            {!$('.App-navigation').is(':visible') && <DiscussionList state={app.discussions} />}
          </div>
        ) : (
          ''
        )}

        <div className="DiscussionPage-discussion">
          {discussion
            ? [
                DiscussionHero.component({ discussion }),
                <div className="container">
                  <nav className="DiscussionPage-nav">
                    <ul>{listItems(this.sidebarItems().toArray())}</ul>
                  </nav>
                  <div className="DiscussionPage-stream">
                    {PostStream.component({
                      discussion,
                      stream: this.stream,
                      targetPost: this.stream.targetPost,
                    })}
                  </div>
                </div>,
              ]
            : LoadingIndicator.component({ className: 'LoadingIndicator--block' })}
        </div>
      </div>
    );
  }

  config(isInitialized, context) {
    super.config(isInitialized, context);

    if (this.discussion) {
      app.setTitle(this.discussion.title());
    }

    context.onunload = () => {
      this.scrollListener.stop();

      clearTimeout(this.calculatePositionTimeout);
    };
  }

  /**
   * Load the discussion from the API or use the preloaded one.
   */
  load() {
    const preloadedDiscussion = app.preloadedApiDocument();
    if (preloadedDiscussion) {
      // We must wrap this in a setTimeout because if we are mounting this
      // component for the first time on page load, then any calls to m.redraw
      // will be ineffective and thus any configs (scroll code) will be run
      // before stuff is drawn to the page.
      setTimeout(this.show.bind(this, preloadedDiscussion), 0);
    } else {
      const params = this.requestParams();

      app.store.find('discussions', m.route.param('id').split('-')[0], params).then(this.show.bind(this));
    }

    m.lazyRedraw();
  }

  /**
   * Get the parameters that should be passed in the API request to get the
   * discussion.
   *
   * @return {Object}
   */
  requestParams() {
    return {
      page: { near: this.near },
    };
  }

  /**
   * Initialize the component to display the given discussion.
   *
   * @param {Discussion} discussion
   */
  show(discussion) {
    this.discussion = discussion;

    app.history.push('discussion', discussion.title());
    app.setTitleCount(0);

    // When the API responds with a discussion, it will also include a number of
    // posts. Some of these posts are included because they are on the first
    // page of posts we want to display (determined by the `near` parameter) –
    // others may be included because due to other relationships introduced by
    // extensions. We need to distinguish the two so we don't end up displaying
    // the wrong posts. We do so by filtering out the posts that don't have
    // the 'discussion' relationship linked, then sorting and splicing.
    let includedPosts = [];
    if (discussion.payload && discussion.payload.included) {
      const discussionId = discussion.id();

      includedPosts = discussion.payload.included
        .filter(
          (record) =>
            record.type === 'posts' &&
            record.relationships &&
            record.relationships.discussion &&
            record.relationships.discussion.data.id === discussionId
        )
        .map((record) => app.store.getById('posts', record.id))
        .sort((a, b) => a.id() - b.id())
        .slice(0, 20);
    }

    // Set up the post stream for this discussion, along with the first page of
    // posts we want to display. Tell the stream to scroll down and highlight
    // the specific post that was routed to.
    this.stream = new PostStreamState(discussion, includedPosts);
    this.stream.goToNumber(m.route.param('near') || (includedPosts[0] && includedPosts[0].number()), true);

    app.current.set('discussion', discussion);
    app.current.set('stream', this.stream);

    this.scrollListener.start();
  }

  /**
   * Configure the discussion list pane.
   *
   * @param {DOMElement} element
   * @param {Boolean} isInitialized
   * @param {Object} context
   */
  configPane(element, isInitialized, context) {
    if (isInitialized) return;

    context.retain = true;

    const $list = $(element);

    // When the mouse enters and leaves the discussions pane, we want to show
    // and hide the pane respectively. We also create a 10px 'hot edge' on the
    // left of the screen to activate the pane.
    const pane = app.pane;
    $list.hover(pane.show.bind(pane), pane.onmouseleave.bind(pane));

    const hotEdge = (e) => {
      if (e.pageX < 10) pane.show();
    };
    $(document).on('mousemove', hotEdge);
    context.onunload = () => $(document).off('mousemove', hotEdge);

    // If the discussion we are viewing is listed in the discussion list, then
    // we will make sure it is visible in the viewport – if it is not we will
    // scroll the list down to it.
    const $discussion = $list.find('.DiscussionListItem.active');
    if ($discussion.length) {
      const listTop = $list.offset().top;
      const listBottom = listTop + $list.outerHeight();
      const discussionTop = $discussion.offset().top;
      const discussionBottom = discussionTop + $discussion.outerHeight();

      if (discussionTop < listTop || discussionBottom > listBottom) {
        $list.scrollTop($list.scrollTop() - listTop + discussionTop);
      }
    }
  }

  /**
   * Build an item list for the contents of the sidebar.
   *
   * @return {ItemList}
   */
  sidebarItems() {
    const items = new ItemList();

    items.add(
      'controls',
      SplitDropdown.component({
        children: DiscussionControls.controls(this.discussion, this).toArray(),
        icon: 'fas fa-ellipsis-v',
        className: 'App-primaryControl',
        buttonClassName: 'Button--primary',
      })
    );

    items.add(
      'scrubber',
      PostStreamScrubber.component({
        discussion: this.discussion,
        className: 'App-titleControl',
        onNavigate: this.stream.goToIndex.bind(this.stream),
        count: this.stream.count(),
        paused: this.stream.paused,
        ...this.scrubberProps(),
      }),
      -100
    );

    return items;
  }

  /**
   * When the window is scrolled, check if either extreme of the post stream is
   * in the viewport, and if so, trigger loading the next/previous page.
   *
   * @param {number} top
   */
  onscroll(top = window.pageYOffset) {
    if (this.stream.paused) return;
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

    // Throttle calculation of our position (start/end numbers of posts in the
    // viewport) to 100ms.
    clearTimeout(this.calculatePositionTimeout);
    this.calculatePositionTimeout = setTimeout(this.calculatePosition.bind(this, top), 100);

    // Update numbers for the scrubber if necessary
    m.redraw();
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
      this.positionChanged(startNumber || 1, endNumber);
    }
  }

  /**
   * When the posts that are visible in the post stream change (i.e. the user
   * scrolls up or down), then we update the URL and mark the posts as read.
   *
   * @param {Integer} startNumber
   * @param {Integer} endNumber
   */
  positionChanged(startNumber, endNumber) {
    const discussion = this.discussion;

    // Construct a URL to this discussion with the updated position, then
    // replace it into the window's history and our own history stack.
    const url = app.route.discussion(discussion, (this.near = startNumber));

    m.route(url, true);
    window.history.replaceState(null, document.title, url);

    app.history.push('discussion', discussion.title());

    // If the user hasn't read past here before, then we'll update their read
    // state and redraw.
    if (app.session.user && endNumber > (discussion.lastReadPostNumber() || 0)) {
      discussion.save({ lastReadPostNumber: endNumber });
      m.redraw();
    }
  }

  scrubberProps(top = window.pageYOffset) {
    const marginTop = this.getMarginTop();
    const viewportHeight = $(window).height() - marginTop;
    const viewportTop = top + marginTop;

    // Before looping through all of the posts, we reset the scrollbar
    // properties to a 'default' state. These values reflect what would be
    // seen if the browser were scrolled right up to the top of the page,
    // and the viewport had a height of 0.
    const $items = this.$('.PostStream-item[data-index]');
    let index = $items.first().data('index') || 0;
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

      if (top <= viewportTop) {
        index = parseFloat($this.data('index')) + visibleTop / height;
      }

      if (visiblePost > 0) {
        visible += visiblePost / height;
      }

      // If this item has a time associated with it, then set the
      // scrollbar's current period to a formatted version of this time.
      const time = $this.data('time');
      if (time) period = time;
    });

    return {
      index: index + 1,
      visible: visible || 1,
      description: period && dayjs(period).format('MMMM YYYY'),
    };
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
}
