import app from 'flarum/forum/app';
import PostList from 'flarum/forum/components/PostList';
import type PostListState from 'flarum/forum/states/PostListState';
import type { PostListParams } from 'flarum/forum/states/PostListState';
import { DeckPostListState } from '../states/deckListStates';
import type Post from 'flarum/common/models/Post';
import type Mithril from 'mithril';
import type { DeckColumnSource, DeckRealtimeEvent, DeckRealtimeResult } from './DeckColumnType';
import type Discussion from 'flarum/common/models/Discussion';
import { POSTED, POST_RESTORED, REMOVED, STARTED, discussionOf, postOf } from './realtimeEvents';

const CHECK_LIMIT = 10;

/**
 * A newest-first column of comments, backed by its own PostListState, so any
 * post filter the API understands works. Post ids only grow, so the newest id
 * on screen is the key for asking "anything newer?".
 */
export default class PostListSource implements DeckColumnSource {
  public readonly state: PostListState;
  protected key = 0;

  constructor(params: PostListParams) {
    this.state = new DeckPostListState({ sort: 'newest', ...params });
  }

  load(): Promise<unknown> {
    if (this.state.hasItems()) return Promise.resolve();

    return this.prepare()
      .then(() => this.state.refresh())
      .then(() => this.snapshot());
  }

  /** Runs once before the first load, for columns whose params need looking up. */
  protected prepare(): Promise<unknown> {
    return Promise.resolve();
  }

  view(): Mithril.Children {
    return <PostList state={this.state} />;
  }

  async checkForNew(): Promise<number> {
    return (await this.newerPosts()).filter((post) => post.user() !== app.session.user).length;
  }

  async applyNew(): Promise<number | null> {
    const posts = await this.newerPosts();
    const extra = (this.state as any as { extraPosts: Post[] }).extraPosts;

    // The list is newest first; insert oldest first so the newest ends on top.
    posts.sort((a, b) => Number(a.id()) - Number(b.id())).forEach((post) => extra.unshift(post));

    this.key = Math.max(this.key, ...posts.map((post) => Number(post.id())));

    return posts.length;
  }

  showNew(): Promise<unknown> {
    return this.state.revalidate().then(() => this.snapshot());
  }

  /**
   * A new reply that certainly belongs here is inserted from the payload; one
   * that certainly doesn't is ignored; only undecidable ones ask the server.
   * A new discussion's first post isn't in its Started payload, so a column
   * that may want it asks.
   */
  onRealtime(event: DeckRealtimeEvent): DeckRealtimeResult {
    const post = postOf(event);

    if (event.name === REMOVED) {
      return event.payload.data.type === 'posts' && this.removePost(String(event.payload.data.id)) ? 'updated' : undefined;
    }

    // A restored post goes back where it was in time, which only the server can place.
    if (event.name === POST_RESTORED && post) {
      return this.shows(post) ? 'updated' : this.matches(post) === false ? undefined : 'check';
    }

    if (event.name === POSTED && post) {
      const match = this.matches(post);

      if (match === false || post.contentType() !== 'comment') return;
      if (match === undefined) return 'check';

      return this.insert(post) ? 'inserted' : undefined;
    }

    if (event.name === STARTED) {
      const discussion = discussionOf(event);

      return discussion && this.mayMatchNewDiscussion(discussion) !== false ? 'check' : undefined;
    }

    // Edits and likes: the store already has the change.
    if (post && this.shows(post)) return 'updated';
  }

  /** Whether a post belongs in this column: undefined when only the server can tell. */
  protected matches(post: Post): boolean | undefined {
    return undefined;
  }

  /** Whether a new discussion's first post might belong here: false rules it out. */
  protected mayMatchNewDiscussion(discussion: Discussion): boolean | undefined {
    return undefined;
  }

  protected shows(post: Post): boolean {
    return this.state.getPages().some((page) => page.items.some((item) => item.id() === post.id()));
  }

  protected removePost(id: string): boolean {
    const state = this.state as any as { pages: { items: Post[] }[]; extraPosts: Post[] };
    let removed = false;

    [...state.pages, { items: state.extraPosts }].forEach((page) => {
      const index = page.items.findIndex((item) => item.id() === id);

      if (index !== -1) {
        page.items.splice(index, 1);
        removed = true;
      }
    });

    return removed;
  }

  protected insert(post: Post): boolean {
    if (this.shows(post)) return false;

    (this.state as any as { extraPosts: Post[] }).extraPosts.unshift(post);
    this.key = Math.max(this.key, Number(post.id()));

    return true;
  }

  protected async newerPosts(): Promise<Post[]> {
    const latest = await app.store.find<Post[]>('posts', {
      filter: { ...(this.state.getParams().filter || {}), type: 'comment' },
      sort: '-createdAt',
      include: 'user,discussion',
      page: { limit: CHECK_LIMIT, total: 0 },
    });

    return latest.filter((post) => Number(post.id()) > this.key);
  }

  protected snapshot(): void {
    this.key = Math.max(
      0,
      ...this.state
        .getPages()
        .flatMap((page) => page.items)
        .map((post) => Number(post.id()))
    );
  }
}
