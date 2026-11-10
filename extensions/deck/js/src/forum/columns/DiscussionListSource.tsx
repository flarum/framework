import app from 'flarum/forum/app';
import DiscussionList from 'flarum/forum/components/DiscussionList';
import type DiscussionListState from 'flarum/forum/states/DiscussionListState';
import type { DiscussionListParams } from 'flarum/forum/states/DiscussionListState';
import { DeckDiscussionListState, asQuery, refillIfEmpty } from '../states/deckListStates';
import type Discussion from 'flarum/common/models/Discussion';
import type { ApiResponsePlural } from 'flarum/common/Store';
import type { DeckColumnSource, DeckRealtimeEvent, DeckRealtimeResult } from './DeckColumnType';
import {
  DISCUSSION_RESTORED,
  POSTED,
  POST_RESTORED,
  REMOVED,
  STARTED,
  TAGGED,
  discussionOf,
  membershipEvents,
  postOf,
  relatedId,
} from './realtimeEvents';

/** Enough to say "20 new"; nobody needs an exact count past a screenful. */
const CHECK_LIMIT = 20;
/** The most discussions the API returns in one request. */
const RECHECK_LIMIT = 50;

/**
 * A column backed by its own DiscussionListState, so any filter the API
 * understands (including other extensions' filters) works without the client
 * having to decide what belongs in the column.
 */
export default class DiscussionListSource implements DeckColumnSource {
  public readonly state: DiscussionListState;

  /** The newest `lastPostedAt` on screen; checks only ask for activity after it. */
  protected key: Date | null = null;

  /** Discussions to ask the server about again, by id; see `recheck()`. */
  protected rechecking = new Set<string>();

  /**
   * @param belongs Whether a discussion still belongs here, for columns that can
   *                tell from the store (Unread: whether it's still unread), so
   *                one can leave without asking the server.
   */
  constructor(
    params: DiscussionListParams,
    protected belongs?: (discussion: Discussion) => boolean
  ) {
    this.state = new DeckDiscussionListState(params);
  }

  prune(): void {
    if (!this.belongs) return;

    this.state
      .getPages()
      .flatMap((page) => page.items)
      .filter((discussion) => !this.belongs!(discussion))
      .forEach((discussion) => this.removeFromList(discussion));

    refillIfEmpty(this.state);
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
      latest = await this.activeSinceKey();
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

    // Restoring, re-tagging, locking, stickying: the discussion may now match
    // the column's filters, or stop matching them, with no new activity.
    if (membershipEvents.has(event.name) && this.filtered()) {
      this.rechecking.add(discussion.id()!);

      return 'recheck';
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

    // As before membership events: an unfiltered column asks for activity,
    // which brings in nothing a re-tag changed, and takes nothing out.
    if (event.name === TAGGED) return 'check';

    // Edits, renames, likes, and in an unfiltered column locking and
    // stickying: the store already has it.
    return shown ? 'updated' : undefined;
  }

  /**
   * Whether the column's request has a filter. An unfiltered list leaves out
   * ignored discussions and hidden tags only while no filter is active, so it
   * can't be narrowed to ids; and locking or stickying doesn't change it.
   */
  protected filtered(): boolean {
    return Object.keys(this.state.requestParams().filter ?? {}).length > 0;
  }

  /**
   * Asks whether the discussions noted by `onRealtime()` match the column,
   * with the column's own request narrowed to their ids: a discussion that
   * matches joins, one that doesn't leaves. Filters are evaluated on the
   * server, so there's no telling from the store.
   */
  async recheck(): Promise<boolean> {
    if (!this.rechecking.size) return false;

    const ids = [...this.rechecking];
    const params = this.state.requestParams();
    const chunks = Array.from({ length: Math.ceil(ids.length / RECHECK_LIMIT) }, (_, i) => ids.slice(i * RECHECK_LIMIT, (i + 1) * RECHECK_LIMIT));

    this.rechecking.clear();

    let found: Discussion[];

    try {
      const pages = await Promise.all(
        chunks.map((chunk) =>
          app.store.find<Discussion[]>('discussions', {
            ...asQuery(params),
            filter: { ...params.filter, id: chunk.join(',') },
            page: { limit: chunk.length, total: 0 },
          })
        )
      );

      found = pages.flat();
    } catch (error) {
      ids.forEach((id) => this.rechecking.add(id));

      throw error;
    }

    const matching = new Set(found.map((discussion) => discussion.id()));
    let atTop = false;

    ids.forEach((id) => {
      const discussion = app.store.getById<Discussion>('discussions', id);

      if (!discussion) return;

      const shown = this.shows(discussion);

      if (shown && !matching.has(id)) this.removeFromList(discussion);
      if (!shown && matching.has(id)) atTop = this.insert(discussion) || atTop;
    });

    refillIfEmpty(this.state);

    return atTop;
  }

  /**
   * Puts a discussion that has started to match where the server lists it, by
   * last activity, then id, as the API breaks ties. Not when it's older than
   * everything held and the server has more (loading more will bring it), nor
   * in any other order, which can't be told from what's held: anywhere else
   * would throw off where the next page starts. Refreshing brings it then.
   *
   * @returns Whether it went in at the very top.
   */
  protected insert(discussion: Discussion): boolean {
    const state = this.state as any as { pages: { items: Discussion[] }[]; extraDiscussions: Discussion[] };

    if (!this.sortsByActivity() || !state.pages.length) return false;

    const pins = this.pinsSticky();
    const rank = (item: Discussion) => [pins && item.attribute<boolean>('isSticky') ? 1 : 0, item.lastPostedAt()?.getTime() ?? 0, Number(item.id())];
    const own = rank(discussion);
    // Whether an item comes after the discussion, comparing ranks in order.
    const after = (item: Discussion) => {
      const theirs = rank(item);
      const differs = theirs.findIndex((value, i) => value !== own[i]);

      return differs !== -1 && theirs[differs] < own[differs];
    };
    let before = 0;

    for (const items of [state.extraDiscussions, ...state.pages.map((page) => page.items)]) {
      const index = items.findIndex(after);

      if (index !== -1) {
        items.splice(index, 0, discussion);
        this.advanceKey(discussion);

        return before + index === 0;
      }

      before += items.length;
    }

    if (this.state.hasNext()) return false;

    state.pages[state.pages.length - 1].items.push(discussion);
    this.advanceKey(discussion);

    return before === 0;
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

  /**
   * flarum/sticky pins stickied discussions above the rest of a list filtered
   * by one tag alone on the default sort, as on a tag's page.
   */
  protected pinsSticky(): boolean {
    const filters = Object.keys(this.state.requestParams().filter ?? {});

    return filters.length === 1 && filters[0] === 'tag' && !this.state.currentSort();
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

    const latest = await this.activeSinceKey();

    // Oldest first, so the most recent activity ends up on top.
    [...latest].sort((a, b) => (a.lastPostedAt()?.getTime() ?? 0) - (b.lastPostedAt()?.getTime() ?? 0)).forEach((d) => this.moveToTop(d));

    latest.forEach((discussion) => {
      const lastPostedAt = discussion.lastPostedAt();

      if (lastPostedAt && lastPostedAt > this.key!) this.key = lastPostedAt;
    });

    return latest.length;
  }

  /**
   * The column's own request, includes and all, narrowed to activity since the
   * key: a range on an indexed column that usually matches nothing.
   */
  protected activeSinceKey(): Promise<ApiResponsePlural<Discussion>> {
    const params = this.state.requestParams();

    return app.store.find<Discussion[]>('discussions', {
      ...asQuery(params),
      filter: { ...params.filter, lastPostedAfter: this.key!.toISOString() },
      page: { limit: CHECK_LIMIT, total: 0 },
    });
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
