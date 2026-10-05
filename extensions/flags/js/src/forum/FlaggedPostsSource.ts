import app from 'flarum/forum/app';
import PostListSource from 'ext:flarum/deck/forum/columns/PostListSource';
import { asQuery } from 'ext:flarum/deck/forum/states/deckListStates';
import type { DeckRealtimeEvent, DeckRealtimeResult } from 'ext:flarum/deck/forum/columns/DeckColumnType';
import type Post from 'flarum/common/models/Post';

/** Realtime's event for a flag raised or cleared, sent only to those who can see it. */
const FLAGGED = 'flagged';

/** Enough to say "10 new"; the column's own page shows the rest. */
const CHECK_LIMIT = 10;

const openFlags = (post: Post): unknown[] => (post as any).flags?.() || [];

/**
 * Posts with open flags, most recently flagged first, each with Flags' own
 * flag bar to act on. The server decides what's listed, so only flags the
 * member may see ever appear.
 */
export default class FlaggedPostsSource extends PostListSource {
  constructor() {
    // No sort: the flagged filter's own order, by when posts were flagged.
    super({ filter: { flagged: true }, sort: '' });
  }

  onRealtime(event: DeckRealtimeEvent): DeckRealtimeResult {
    if (event.name === FLAGGED) return 'check';

    return super.onRealtime(event);
  }

  /** A new reply has no flags yet. */
  protected matches(): boolean {
    return false;
  }

  protected mayMatchNewDiscussion(): boolean {
    return false;
  }

  /** A post whose flags were dismissed, here or elsewhere, leaves. */
  prune(): void {
    this.shownPosts()
      .filter((post) => !openFlags(post).length)
      .forEach((post) => this.removePost(post.id()!));

    this.refill();
  }

  /** An old post can be flagged at any time, so the queue is reloaded rather than added to. */
  async applyNew(): Promise<number | null> {
    const before = new Set(this.shownPosts().map((post) => post.id()));

    await this.state.revalidate();

    return this.shownPosts().filter((post) => !before.has(post.id())).length;
  }

  async checkForNew(): Promise<number> {
    const shown = new Set(this.shownPosts().map((post) => post.id()));
    // The column's own request, so the posts come with their flags.
    const latest = await app.store.find<Post[]>('posts', { ...asQuery(this.state.requestParams()), page: { limit: CHECK_LIMIT, total: 0 } });

    return latest.filter((post) => !shown.has(post.id())).length;
  }

  protected shownPosts(): Post[] {
    return this.state.getPages().flatMap((page) => page.items);
  }
}
