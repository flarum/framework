import Component from 'flarum/component';
import ItemList from 'flarum/utils/item-list';
import PostStream from 'flarum/utils/post-stream';
import DiscussionList from 'flarum/components/discussion-list';
import DiscussionHero from 'flarum/components/discussion-hero';
import StreamContent from 'flarum/components/stream-content';
import StreamScrubber from 'flarum/components/stream-scrubber';
import ComposerReply from 'flarum/components/composer-reply';
import ActionButton from 'flarum/components/action-button';
import LoadingIndicator from 'flarum/components/loading-indicator';
import DropdownSplit from 'flarum/components/dropdown-split';
import Separator from 'flarum/components/separator';
import listItems from 'flarum/helpers/list-items';

export default class DiscussionPage extends Component {
  /**

   */
  constructor(props) {
    super(props);

    this.discussion = m.prop();

    // Set up the stream. The stream is an object that represents the posts in
    // a discussion, as they're displayed on the screen (i.e. missing posts
    // are condensed into "load more" gaps).
    this.stream = m.prop();

    // Get the discussion. We may already have a copy of it in our store, so
    // we'll start off with that. If we do have a copy of the discussion, and
    // its posts relationship has been loaded (i.e. we've viewed this
    // discussion before), then we can proceed with displaying it immediately.
    // If not, we'll make an API request first.
    this.currentNear = m.route.param('near') || 0;
    var params = this.params();
    params.include = params.include.join(',');
    app.store.find('discussions', m.route.param('id'), params).then(this.setupDiscussion.bind(this));

    if (app.cache.discussionList) {
      if (!(app.current instanceof DiscussionPage)) {
        app.cache.discussionList.subtrees.map(subtree => subtree.invalidate());
      }
      app.pane.enable();
      app.pane.hide();
      m.redraw.strategy('diff'); // otherwise pane redraws and mouseenter even is triggered so it doesn't hide
    }

    app.history.push('discussion');
    app.current = this;
  }

  params() {
    return {
      near: this.currentNear,
      include: ['posts', 'posts.user']
    };
  }

  /*

   */
  setupDiscussion(discussion) {
    this.discussion(discussion);

    var includedPosts = [];
    discussion.payload.included && discussion.payload.included.forEach(record => {
      if (record.type === 'posts' && (record.contentType !== 'comment' || record.contentHtml)) {
        includedPosts.push(record.id);
      }
    });

    // Set up the post stream for this discussion, and add all of the posts we
    // have loaded so far.
    this.stream(new PostStream(discussion));
    this.stream().addPosts(discussion.posts().filter(value => value && includedPosts.indexOf(value.id()) !== -1));
    this.streamContent = new StreamContent({
      stream: this.stream(),
      className: 'discussion-posts posts',
      positionChanged: this.positionChanged.bind(this)
    });

    // Hold up there skippy! If the slug in the URL doesn't match up, we'll
    // redirect so we have the correct one.
    // Waiting on https://github.com/lhorie/mithril.js/issues/539
    // if (m.route.param('id') === discussion.id() && m.route.param('slug') !== discussion.slug()) {
    //   var params = m.route.param();
    //   params.slug = discussion.slug();
    //   params.near = params.near || '';
    //   m.route(app.route('discussion.near', params), null, true);
    //   return;
    // }

    this.streamContent.goToNumber(this.currentNear, true);
  }

  onload(element, isInitialized, context) {
    if (isInitialized) { return; }

    context.retain = true;

    $('body').addClass('discussion-page');
    context.onunload = function() {
      $('body').removeClass('discussion-page');
    }
  }

  /**

   */
  onunload(e) {
    // If we have routed to the same discussion as we were viewing previously,
    // cancel the unloading of this controller and instead prompt the post
    // stream to jump to the new 'near' param.
    var discussion = this.discussion();
    if (discussion) {
      if (m.route.param('id') == discussion.id()) {
        e.preventDefault();
        if (m.route.param('near') != this.currentNear) {
          this.streamContent.goToNumber(m.route.param('near'));
        }
        this.currentNear = null;
        return;
      }
    }

    app.pane.disable();
    app.composer.minimize();
  }

  /**

   */
  view() {
    var discussion = this.discussion();

    return m('div', {config: this.onload.bind(this)}, [
      app.cache.discussionList ? m('div.index-area.paned', {config: this.configIndex.bind(this)}, app.cache.discussionList.view()) : '',
      m('div.discussion-area', discussion ? [
        DiscussionHero.component({discussion}),
        m('div.container', [
          m('nav.discussion-nav', [
            m('ul', listItems(this.sidebarItems().toArray()))
          ]),
          this.streamContent.view()
        ])
      ] : LoadingIndicator.component({className: 'loading-indicator-block'}))
    ]);
  }

  /**

   */
  configIndex(element, isInitialized, context) {
    if (isInitialized) { return; }

    context.retain = true;

    var $index = $(element);

    // When viewing a discussion (for which the discussions route is the
    // parent,) the discussion list is still rendered but it becomes a
    // pane hidden on the side of the screen. When the mouse enters and
    // leaves the discussions pane, we want to show and hide the pane
    // respectively. We also create a 10px 'hot edge' on the left of the
    // screen to activate the pane.
    var pane = app.pane;
    $index.hover(pane.show.bind(pane), pane.onmouseleave.bind(pane));

    var hotEdge = function(e) {
      if (e.pageX < 10) { pane.show(); }
    };
    $(document).on('mousemove', hotEdge);
    context.onunload = function() {
      $(document).off('mousemove', hotEdge);
    };

    var $discussion = $index.find('.discussion-summary.active');
    if ($discussion.length) {
      var indexTop = $index.offset().top;
      var discussionTop = $discussion.offset().top;
      if (discussionTop < indexTop || discussionTop + $discussion.outerHeight() > indexTop + $index.outerHeight()) {
        $index.scrollTop($index.scrollTop() - indexTop + discussionTop);
      }
    }
  }

  /**

   */
  sidebarItems() {
    var items = new ItemList();

    items.add('controls',
      DropdownSplit.component({
        items: this.discussion().controls(this).toArray(),
        icon: 'reply',
        buttonClass: 'btn btn-primary',
        wrapperClass: 'primary-control'
      })
    );

    items.add('scrubber',
      StreamScrubber.component({
        streamContent: this.streamContent,
        wrapperClass: 'title-control'
      })
    );

    return items;
  }

  /**

   */
  positionChanged(startNumber, endNumber) {
    var discussion = this.discussion();

    var url = app.route('discussion.near', {
      id: discussion.id(),
      slug: discussion.slug(),
      near: this.currentNear = startNumber
    });

    // https://github.com/lhorie/mithril.js/issues/559
    m.route(url, true);
    window.history.replaceState(null, document.title, (m.route.mode === 'hash' ? '#' : '')+url);

    app.history.push('discussion');

    if (app.session.user() && endNumber > discussion.readNumber()) {
      discussion.save({readNumber: endNumber});
      m.redraw();
    }
  }
}
