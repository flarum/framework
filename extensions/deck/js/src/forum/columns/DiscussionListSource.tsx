import app from 'flarum/forum/app';
import DiscussionList from 'flarum/forum/components/DiscussionList';
import type DiscussionListState from 'flarum/forum/states/DiscussionListState';
import type { DiscussionListParams } from 'flarum/forum/states/DiscussionListState';
import { DeckDiscussionListState } from '../states/deckListStates';
import type Discussion from 'flarum/common/models/Discussion';
import type { ApiResponsePlural } from 'flarum/common/Store';
import type { DeckColumnSource, DeckRealtimeEvent, DeckRealtimeResult } from './DeckColumnType';
import { DISCUSSION_RESTORED, POSTED, POST_RESTORED, REMOVED, STARTED, TAGGED, discussionOf, postOf, relatedId } from './realtimeEvents';

/** Enough to say "20 new"; nobody needs an exact count past a screenful. */
const CHECK_LIMIT = 20;

/**
 * A column backed by its own DiscussionListState, so any filter the API
 * understands (including other extensions' filters) works without the client
 * having to decide what belongs in the column.
 */
export default class DiscussionListSource implements DeckColumnSource {
  public readonly state: DiscussionListState;

  /** The newest `lastPostedAt` on screen; checks only ask for activity after it. */
  protected key: Date | null = null;

  /**
   * @param belongs Whether a discussion still belongs here, for columns that can
   *                tell from the store (Unread: whether it's still unread), so
   *                one can leave without asking the server.
   */
  constructor(params: DiscussionListParams, protected belongs?: (discussion: Discussion) => boolean) {
    this.state = new DeckDiscussionListState(params);
  }

  prune(): void {
    if (!this.belongs) return;

    this.state
      .getPages()
      .flatMap((page) => page.items)
      .filter((discussion) => !this.belongs!(discussion))
      .forEach((discussion) => this.removeFromList(discussion));
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

  view() {
    return <DiscussionList state={this.state} />;
  }

  /**
   * The column's own filters plus `lastPostedAfter`: a range on an indexed
   * column that usually matches nothing, rather than re-running the list.
   */
  async checkForNew(): Promise<number> {
    let latest: ApiResponsePlural<Discussion>;

    if (this.key) {
      const params = this.state.requestParams();

      latest = await app.store.find<Discussion[]>('discussions', {
        filter: { ...params.filter, lastPostedAfter: this.key.toISOString() },
        include: 'lastPostedUser',
        page: { limit: CHECK_LIMIT, total: 0 },
      });
    } else {
      // An empty column has no key yet; its query is cheap by definition.
      latest = (await (this.state as any).loadPage(1)) as ApiResponsePlural<Discussion>;
    }

    return latest.filter((discussion) => discussion.lastPostedUser() !== app.session.user).length;
  }

  showNew(): Promise<unknown> {
    return this.state.revalidate().then(() => this.snapshot());
  }

  /**
   * A reply to a discussion already in the column moves it to the top straight
   * from the payload: a new reply doesn't change which column a discussion
   * belongs in. Anything the column doesn't hold yet (a new discussion, a
   * reply elsewhere that may now match) only the server can place.
   */
  onRealtime(event: DeckRealtimeEvent): DeckRealtimeResult {
    const discussion = discussionOf(event);

    if (!discussion) return;

    const shown = this.shows(discussion);

    if (event.name === REMOVED) {
      if (event.payload.data.type !== 'discussions' || !shown) return;

      this.removeFromList(discussion);

      return 'updated';
    }

    // Only the server knows where something brought back belongs.
    if (event.name === DISCUSSION_RESTORED || (event.name === POST_RESTORED && !shown)) return 'check';

    if (event.name === POSTED || event.name === STARTED) {
      // The member's own posts can change membership (e.g. Unread), so ask.
      const subject = postOf(event) ?? discussion;
      const own = relatedId(subject, 'user') === app.session.user?.id();

      if (shown && !own && this.sortsByActivity()) {
        this.moveToTop(discussion);
        this.advanceKey(discussion);

        return 'inserted';
      }

      return 'check';
    }

    // Re-tagging can move a discussion into, or out of, a tag's column.
    if (event.name === TAGGED) return 'check';

    // Edits, renames, likes, locking, stickying: the store already has it.
    return shown ? 'updated' : undefined;
  }

  /** Within this column; realtime's own handler takes it off every other list. */
  protected removeFromList(discussion: Discussion): void {
    const state = this.state as any as { pages: { items: Discussion[] }[]; extraDiscussions: Discussion[] };

    [...state.pages, { items: state.extraDiscussions }].forEach((page) => {
      const index = page.items.findIndex((item) => item.id() === discussion.id());

      if (index !== -1) page.items.splice(index, 1);
    });
  }

  protected shows(discussion: Discussion): boolean {
    return this.state.getPages().some((page) => page.items.some((item) => item.id() === discussion.id()));
  }

  /** "To the top" only means something when the column is ordered by activity. */
  protected sortsByActivity(): boolean {
    const sort = this.state.currentSort();

    return !sort || sort === '-lastPostedAt';
  }

  protected advanceKey(discussion: Discussion): void {
    const lastPostedAt = discussion.lastPostedAt();

    if (lastPostedAt && (!this.key || lastPostedAt > this.key)) this.key = lastPostedAt;
  }

  async applyNew(): Promise<number | null> {
    if (!this.key || !this.sortsByActivity()) return null;

    const params = this.state.requestParams();
    const latest = await app.store.find<Discussion[]>('discussions', {
      filter: { ...params.filter, lastPostedAfter: this.key.toISOString() },
      include: Array.isArray(params.include) ? params.include.join(',') : params.include,
      page: { limit: CHECK_LIMIT, total: 0 },
    });

    // Oldest first, so the most recent activity ends up on top.
    [...latest].sort((a, b) => (a.lastPostedAt()?.getTime() ?? 0) - (b.lastPostedAt()?.getTime() ?? 0)).forEach((d) => this.moveToTop(d));

    latest.forEach((discussion) => {
      const lastPostedAt = discussion.lastPostedAt();

      if (lastPostedAt && lastPostedAt > this.key!) this.key = lastPostedAt;
    });

    return latest.length;
  }

  /**
   * `DiscussionListState.addDiscussion()` can't be used here: it removes the
   * discussion through an emitter shared by every list, so it would vanish
   * from the other columns and the index. This moves it within this list only.
   */
  protected moveToTop(discussion: Discussion): void {
    const state = this.state as any as { pages: { items: Discussion[] }[]; extraDiscussions: Discussion[] };
    const id = discussion.id();

    [...state.pages, { items: state.extraDiscussions }].forEach((page) => {
      const index = page.items.findIndex((item) => item.id() === id);

      if (index !== -1) page.items.splice(index, 1);
    });

    state.extraDiscussions.unshift(discussion);
  }

  protected snapshot(): void {
    this.key = null;

    this.state
      .getPages()
      .flatMap((page) => page.items)
      .forEach((discussion) => {
        const lastPostedAt = discussion.lastPostedAt();

        if (lastPostedAt && (!this.key || lastPostedAt > this.key)) this.key = lastPostedAt;
      });
  }
}
