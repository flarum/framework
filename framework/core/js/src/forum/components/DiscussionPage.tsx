import type Mithril from 'mithril';

import app from '../../forum/app';
import Page, { IPageAttrs } from '../../common/components/Page';
import ItemList from '../../common/utils/ItemList';
import DiscussionHero from './DiscussionHero';
import DiscussionListPane from './DiscussionListPane';
import SplitDropdown from '../../common/components/SplitDropdown';
import listItems from '../../common/helpers/listItems';
import DiscussionControls from '../utils/DiscussionControls';
import PostStreamState from '../states/PostStreamState';
import Discussion from '../../common/models/Discussion';
import Post from '../../common/models/Post';
import { ApiResponseSingle } from '../../common/Store';
import PageStructure from './PageStructure';

export interface IDiscussionPageAttrs extends IPageAttrs {
  id: string;
  near?: number;
}

/**
 * The `DiscussionPage` component displays a whole discussion page, including
 * the discussion list pane, the hero, the posts, and the sidebar.
 */
export default class DiscussionPage<CustomAttrs extends IDiscussionPageAttrs = IDiscussionPageAttrs> extends Page<CustomAttrs> {
  protected loading: boolean = true;

  protected PostStream: any = null;
  protected PostStreamScrubber: any = null;

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

  /**
   * The window of posts obtained without the post stream having to request
   * one — prefetched in parallel with the discussion, or embedded in the
   * server-preloaded document — pending consumption by show(). Stashed on the
   * instance rather than only threaded through show()'s arguments, so an
   * extension override of show() that forwards just the discussion (a common
   * mistake) cannot silently drop the window and force a redundant request.
   */
  protected pendingPostWindow: Post[] = [];

  protected useBrowserScrollRestoration = true;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.load();

    // If the discussion list has been loaded, then we'll enable the pane (and
    // hide it by default). Also, if we've just come from another discussion
    // page, then we don't want Mithril to redraw the whole page – if it did,
    // then the pane would redraw which would be slow and would cause problems with
    // event handlers.
    // We will also enable the pane if the discussion list is empty but loading,
    // because the DiscussionComposer refreshes the list and redirects to the new discussion at the same time.
    if (app.discussions.hasItems() || app.discussions.isLoading()) {
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

    if (this.discussion && app.composer.composingReplyTo(this.discussion) && !app.composer?.fields?.content()) {
      app.composer.hide();
    } else {
      app.composer.minimize();
    }
  }

  view() {
    // Keep the page shell rendered while the discussion loads. Bailing out to
    // a bare loading indicator here blanks the whole layout on every
    // discussion-to-discussion navigation — including the pinned discussion
    // list pane, whose contents are already cached and need no network.
    // PageStructure renders the spinner inside the main area and skips the
    // hero/sidebar/content closures while `loading` is set, so none of them
    // run without a discussion.
    const loading = this.loading || !this.discussion;

    return (
      <PageStructure
        className="DiscussionPage"
        loading={loading}
        hero={this.hero.bind(this)}
        sidebar={this.sidebar.bind(this)}
        pane={() => <DiscussionListPane state={app.discussions} />}
      >
        {loading || (
          <div className="DiscussionPage-stream">
            <this.PostStream discussion={this.discussion} stream={this.stream} onPositionChange={this.positionChanged.bind(this)} />
          </div>
        )}
      </PageStructure>
    );
  }

  /**
   * Function that renders the `sidebarItems` ItemList.
   */
  sidebar(): Mithril.Children {
    return (
      <nav className="DiscussionPage-nav">
        <ul>{listItems(this.sidebarItems().toArray())}</ul>
      </nav>
    );
  }

  /**
   * Renders the discussion's hero.
   */
  hero(): Mithril.Children {
    return <DiscussionHero discussion={this.discussion} />;
  }

  /**
   * Load the discussion from the API or use the preloaded one.
   */
  load(): void {
    const preloadedDiscussion = app.preloadedApiDocument<Discussion>();

    // Kick off the network requests immediately: they must not wait for the
    // async component chunks below, and they must not wait for each other.
    // The post window is derivable from the route alone, so requesting it
    // serially after the discussion (what PostStreamState would do) wastes a
    // full round-trip on every discussion visited.
    let discussionPromise: Promise<ApiResponseSingle<Discussion>> | null = null;
    let postsPromise: Promise<Post[]> | null = null;

    if (!preloadedDiscussion) {
      discussionPromise = app.store.find<Discussion>('discussions', m.route.param('id'), this.requestParams());
      postsPromise = this.prefetchPosts();
    }

    Promise.all([import('./PostStream'), import('./PostStreamScrubber')]).then(([PostStreamImport, PostStreamScrubberImport]) => {
      this.PostStream = PostStreamImport.default;
      this.PostStreamScrubber = PostStreamScrubberImport.default;

      if (preloadedDiscussion) {
        // We must wrap this in a setTimeout because if we are mounting this
        // component for the first time on page load, then any calls to m.redraw
        // will be ineffective and thus any configs (scroll code) will be run
        // before stuff is drawn to the page.
        setTimeout(() => {
          this.pendingPostWindow = this.preloadedNearPage(preloadedDiscussion);
          this.show(preloadedDiscussion, this.pendingPostWindow);
        }, 0);
      } else {
        Promise.all([discussionPromise!, postsPromise ?? []]).then(([discussion, posts]) => {
          this.pendingPostWindow = posts;
          this.show(discussion, posts);
        });
      }
    });

    m.redraw();
  }

  /**
   * Fetch the near-window of posts in parallel with the discussion request.
   *
   * This mirrors the request PostStreamState.loadNearNumber() would make
   * serially after the discussion arrives; with the window already loaded,
   * the stream finds its target post and makes no request of its own. Only
   * possible when the discussion is already in the store (in-app navigation —
   * exactly the case users notice), because the posts filter needs the ID and
   * the route only carries a slug. Returns null to keep the serial flow when
   * the discussion is unknown or the target is the reply placeholder. Failures
   * are swallowed: the discussion request owns error handling, and the stream
   * falls back to loading its own window.
   */
  protected prefetchPosts(): Promise<Post[]> | null {
    const idParam = m.route.param('id');
    const discussion = app.store.all<Discussion>('discussions').find((d) => d.slug() === idParam || d.id() === idParam);

    if (!discussion) return null;

    const nearParam = m.route.param('near');
    if (nearParam === 'reply') return null;

    const near = parseInt(nearParam);

    return app.store
      .find<Post[]>(
        'posts',
        {
          filter: { discussion: discussion.id() as string },
          page: { near: !isNaN(near) ? near : 1 },
        },
        undefined,
        { errorHandler: () => {} }
      )
      .catch(() => []);
  }

  /**
   * Get the parameters that should be passed in the API request to get the
   * discussion.
   */
  requestParams(): Record<string, unknown> {
    return {
      bySlug: true,
      page: { near: this.near },
    };
  }

  /**
   * Initialize the component to display the given discussion.
   */
  show(discussion: ApiResponseSingle<Discussion>, preloadedPosts: Post[] = []): void {
    this.loading = false;

    // Recover the stashed window if an extension override of show() didn't
    // forward the posts argument — without it the stream would immediately
    // re-request posts we already have.
    if (!preloadedPosts.length && this.pendingPostWindow.length) {
      preloadedPosts = this.pendingPostWindow;
    }
    this.pendingPostWindow = [];

    app.history.push('discussion', discussion.title());
    app.setTitle(discussion.title());
    app.setTitleCount(0);

    // Set up the post stream for this discussion, along with the first page of
    // posts we want to display. Tell the stream to scroll down and highlight
    // the specific post that was routed to.
    this.stream = new PostStreamState(discussion, preloadedPosts);
    const rawNearParam = m.route.param('near');
    const nearParam = rawNearParam === 'reply' ? 'reply' : parseInt(rawNearParam);
    // Post numbers start at 1; use the first preloaded post (or 1) when near is
    // missing/invalid so the first post gets the pop-in.
    const targetPost = nearParam === 'reply' ? 'reply' : nearParam && !isNaN(nearParam) ? nearParam : preloadedPosts[0]?.number() ?? 1;
    this.stream.goToNumber(targetPost, true).then(() => {
      this.discussion = discussion;

      app.current.set('discussion', discussion);
      app.current.set('stream', this.stream);
    });
  }

  /**
   * Extract the page of posts that was embedded in the server-preloaded
   * discussion document, so the post stream can render without re-fetching
   * the same posts via the API.
   *
   * On the initial page load, Content\Discussion embeds the `page[near]`
   * window of posts in the preloaded document (it needs them for the noscript
   * content anyway), and `preloadedApiDocument()` has already pushed them into
   * the store by the time this runs. Returns the longest run of posts that is
   * contiguous in stream order: stray posts pulled in by other relationships
   * (e.g. extension includes) must not corrupt the visible window — that
   * non-contiguity is what caused #4137. API show responses (post-#4067)
   * include no posts page, so for in-app navigation this returns an empty
   * array and the stream fetches as before.
   */
  preloadedNearPage(discussion: ApiResponseSingle<Discussion>): Post[] {
    let best: Post[] = [];
    let current: Post[] = [];

    for (const id of discussion.postIds()) {
      const post = id ? app.store.getById<Post>('posts', id) : null;

      if (post) {
        current.push(post);
        if (current.length > best.length) best = current;
      } else {
        current = [];
      }
    }

    return best;
  }

  /**
   * Build an item list for the contents of the sidebar.
   */
  sidebarItems() {
    const items = new ItemList<Mithril.Children>();

    if (this.discussion) {
      items.add(
        'controls',
        <SplitDropdown
          icon="fas fa-ellipsis-v"
          className="App-primaryControl"
          buttonClassName="Button--primary"
          accessibleToggleLabel={app.translator.trans('core.forum.discussion_controls.toggle_dropdown_accessible_label')}
        >
          {DiscussionControls.controls(this.discussion, this).toArray()}
        </SplitDropdown>,
        100
      );
    }

    items.add('scrubber', <this.PostStreamScrubber stream={this.stream} className="App-titleControl" />, -100);

    return items;
  }

  /**
   * When the posts that are visible in the post stream change (i.e. the user
   * scrolls up or down), then we update the URL and mark the posts as read.
   *
   * URL and history writes are skipped when startNumber hasn't changed from the
   * last known position — this prevents double history churn from the immediate
   * post-scroll emit and the subsequent settle-end reconciliation both resolving
   * to the same post. Read-state saves are intentionally independent of this
   * guard and use their own endNumber condition.
   */
  positionChanged(startNumber: number, endNumber: number): void {
    const discussion = this.discussion;

    if (!discussion) return;

    // Only write history when the visible start post actually changed.
    if (startNumber !== this.near) {
      // Construct a URL to this discussion with the updated position, then
      // replace it into the window's history and our own history stack.
      const url = app.route.discussion(discussion, startNumber);
      this.near = startNumber;

      window.history.replaceState(null, document.title, url);
      app.history.push('discussion', discussion.title());
    }

    // If the user hasn't read past here before, then we'll update their read
    // state and redraw.
    if (app.session.user && endNumber > (discussion.lastReadPostNumber() || 0)) {
      discussion.save({ lastReadPostNumber: endNumber });
      m.redraw();
    }
  }
}
