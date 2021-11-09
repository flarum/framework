import type Mithril from 'mithril';

import app from '../../forum/app';
import Page, { IPageAttrs } from '../../common/components/Page';
import ItemList from '../../common/utils/ItemList';
import DiscussionHero from './DiscussionHero';
import DiscussionListPane from './DiscussionListPane';
import PostStream from './PostStream';
import PostStreamScrubber from './PostStreamScrubber';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import SplitDropdown from '../../common/components/SplitDropdown';
import listItems from '../../common/helpers/listItems';
import DiscussionControls from '../utils/DiscussionControls';
import PostStreamState from '../states/PostStreamState';
import Discussion from '../../common/models/Discussion';
import Post from '../../common/models/Post';

export interface IDiscussionPageAttrs extends IPageAttrs {
  id: string;
  near?: number;
}

/**
 * The `DiscussionPage` component displays a whole discussion page, including
 * the discussion list pane, the hero, the posts, and the sidebar.
 */
export default class DiscussionPage<CustomAttrs extends IDiscussionPageAttrs = IDiscussionPageAttrs> extends Page<CustomAttrs> {
  /**
   * The discussion that is being viewed.
   */
  protected discussion: Discussion | null = null;

  /**
   * A public API for interacting with the post stream.
   */
  protected stream: PostStreamState | null = null;

  /**
   * The number of the first post that is currently visible in the viewport.
   */
  protected near: number = 0;

  protected useBrowserScrollRestoration = true;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.load();

    // If the discussion list has been loaded, then we'll enable the pane (and
    // hide it by default). Also, if we've just come from another discussion
    // page, then we don't want Mithril to redraw the whole page – if it did,
    // then the pane would redraw which would be slow and would cause problems with
    // event handlers.
    if (app.discussions.hasItems()) {
      app.pane?.enable();
      app.pane?.hide();
    }

    this.bodyClass = 'App--discussion';
  }

  onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onremove(vnode);

    // If we are indeed navigating away from this discussion, then disable the
    // discussion list pane. Also, if we're composing a reply to this
    // discussion, minimize the composer – unless it's empty, in which case
    // we'll just close it.
    app.pane?.disable();

    if (app.composer.composingReplyTo(this.discussion) && !app.composer?.fields?.content()) {
      app.composer.hide();
    } else {
      app.composer.minimize();
    }
  }

  view() {
    return (
      <div className="DiscussionPage">
        <DiscussionListPane state={app.discussions} />
        <div className="DiscussionPage-discussion">{this.discussion ? this.pageContent().toArray() : this.loadingItems().toArray()}</div>
      </div>
    );
  }

  /**
   * List of components shown while the discussion is loading.
   *
   * @returns {ItemList}
   */
  loadingItems() {
    const items = new ItemList();

    items.add('spinner', <LoadingIndicator />, 100);

    return items;
  }

  /**
   * Function that renders the `sidebarItems` ItemList.
   *
   * @returns {import('mithril').Children}
   */
  sidebar() {
    return (
      <nav className="DiscussionPage-nav">
        <ul>{listItems(this.sidebarItems().toArray())}</ul>
      </nav>
    );
  }

  /**
   * Renders the discussion's hero.
   *
   * @returns {import('mithril').Children}
   */
  hero() {
    return <DiscussionHero discussion={this.discussion} />;
  }

  /**
   * List of items rendered as the main page content.
   *
   * @returns {ItemList}
   */
  pageContent() {
    const items = new ItemList();

    items.add('hero', this.hero(), 100);
    items.add('main', <div className="container">{this.mainContent().toArray()}</div>, 10);

    return items;
  }

  /**
   * List of items rendered inside the main page content container.
   *
   * @returns {ItemList}
   */
  mainContent() {
    const items = new ItemList();

    items.add('sidebar', this.sidebar(), 100);

    items.add(
      'poststream',
      <div className="DiscussionPage-stream">
        {PostStream.component({
          discussion: this.discussion,
          stream: this.stream,
          onPositionChange: this.positionChanged.bind(this),
        })}
      </div>,
      10
    );

    return items;
  }

  /**
   * Load the discussion from the API or use the preloaded one.
   */
  load() {
    const preloadedDiscussion = app.preloadedApiDocument() as Discussion | null;
    if (preloadedDiscussion) {
      // We must wrap this in a setTimeout because if we are mounting this
      // component for the first time on page load, then any calls to m.redraw
      // will be ineffective and thus any configs (scroll code) will be run
      // before stuff is drawn to the page.
      setTimeout(this.show.bind(this, preloadedDiscussion), 0);
    } else {
      const params = this.requestParams();

      app.store.find('discussions', m.route.param('id'), params).then(this.show.bind(this));
    }

    m.redraw();
  }

  /**
   * Get the parameters that should be passed in the API request to get the
   * discussion.
   *
   * @return {Object}
   */
  requestParams() {
    return {
      bySlug: true,
      page: { near: this.near },
    };
  }

  /**
   * Initialize the component to display the given discussion.
   */
  show(discussion: Discussion) {
    app.history.push('discussion', discussion.title());
    app.setTitle(discussion.title());
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
        .sort((a: Post, b: Post) => a.createdAt() - b.createdAt())
        .slice(0, 20);
    }

    // Set up the post stream for this discussion, along with the first page of
    // posts we want to display. Tell the stream to scroll down and highlight
    // the specific post that was routed to.
    this.stream = new PostStreamState(discussion, includedPosts);
    this.stream.goToNumber(m.route.param('near') || (includedPosts[0] && includedPosts[0].number()), true).then(() => {
      this.discussion = discussion;

      app.current.set('discussion', discussion);
      app.current.set('stream', this.stream);
    });
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
      SplitDropdown.component(
        {
          icon: 'fas fa-ellipsis-v',
          className: 'App-primaryControl',
          buttonClassName: 'Button--primary',
          accessibleToggleLabel: app.translator.trans('core.forum.discussion_controls.toggle_dropdown_accessible_label'),
        },
        DiscussionControls.controls(this.discussion, this).toArray()
      )
    );

    items.add(
      'scrubber',
      PostStreamScrubber.component({
        stream: this.stream,
        className: 'App-titleControl',
      }),
      -100
    );

    return items;
  }

  /**
   * When the posts that are visible in the post stream change (i.e. the user
   * scrolls up or down), then we update the URL and mark the posts as read.
   */
  positionChanged(startNumber: number, endNumber: number): void {
    const discussion = this.discussion;

    if (!discussion) return;

    // Construct a URL to this discussion with the updated position, then
    // replace it into the window's history and our own history stack.
    const url = app.route.discussion(discussion, (this.near = startNumber));

    window.history.replaceState(null, document.title, url);
    app.history.push('discussion', discussion.title());

    // If the user hasn't read past here before, then we'll update their read
    // state and redraw.
    if (app.session.user && endNumber > (discussion.lastReadPostNumber() || 0)) {
      discussion.save({ lastReadPostNumber: endNumber });
      m.redraw();
    }
  }
}
