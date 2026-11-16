import type DiscussionListState from 'flarum/forum/states/DiscussionListState';
import type { DiscussionListParams } from 'flarum/forum/states/DiscussionListState';
import type Discussion from 'flarum/common/models/Discussion';
import type { ApiResponsePlural } from 'flarum/common/Store';
import type { DeckColumnSource, DeckRealtimeEvent, DeckRealtimeResult } from './DeckColumnType';
/**
 * A column backed by its own DiscussionListState, so any filter the API
 * understands (including other extensions' filters) works without the client
 * having to decide what belongs in the column.
 */
export default class DiscussionListSource implements DeckColumnSource {
    protected belongs?: ((discussion: Discussion) => boolean) | undefined;
    readonly state: DiscussionListState;
    /** The newest `lastPostedAt` on screen; checks only ask for activity after it. */
    protected key: Date | null;
    /** Discussions to ask the server about again, by id; see `recheck()`. */
    protected rechecking: Set<string>;
    /**
     * @param belongs Whether a discussion still belongs here, for columns that can
     *                tell from the store (Unread: whether it's still unread), so
     *                one can leave without asking the server.
     */
    constructor(params: DiscussionListParams, belongs?: ((discussion: Discussion) => boolean) | undefined);
    prune(): void;
    load(): Promise<unknown>;
    /** Runs once before the first load, for columns whose params need looking up. */
    protected prepare(): Promise<unknown>;
    view(): JSX.Element;
    /**
     * The column's own filters plus `lastPostedAfter`: a range on an indexed
     * column that usually matches nothing, rather than re-running the list.
     */
    checkForNew(): Promise<number>;
    showNew(): Promise<unknown>;
    /**
     * A reply to a discussion already in the column moves it to the top straight
     * from the payload: a new reply doesn't change which column a discussion
     * belongs in. Anything the column doesn't hold yet (a new discussion, a
     * reply elsewhere that may now match) only the server can place.
     */
    onRealtime(event: DeckRealtimeEvent): DeckRealtimeResult;
    /**
     * Whether the column's request has a filter. An unfiltered list leaves out
     * ignored discussions and hidden tags only while no filter is active, so it
     * can't be narrowed to ids; and locking or stickying doesn't change it.
     */
    protected filtered(): boolean;
    /**
     * Asks whether the discussions noted by `onRealtime()` match the column,
     * with the column's own request narrowed to their ids: a discussion that
     * matches joins, one that doesn't leaves. Filters are evaluated on the
     * server, so there's no telling from the store.
     */
    recheck(): Promise<boolean>;
    /**
     * Puts a discussion that has started to match where the server lists it, by
     * last activity, then id, as the API breaks ties. Not when it's older than
     * everything held and the server has more (loading more will bring it), nor
     * in any other order, which can't be told from what's held: anywhere else
     * would throw off where the next page starts. Refreshing brings it then.
     *
     * @returns Whether it went in at the very top.
     */
    protected insert(discussion: Discussion): boolean;
    /** Within this column; realtime's own handler takes it off every other list. */
    protected removeFromList(discussion: Discussion): void;
    protected shows(discussion: Discussion): boolean;
    /**
     * flarum/sticky pins stickied discussions above the rest of a list filtered
     * by one tag alone on the default sort, as on a tag's page.
     */
    protected pinsSticky(): boolean;
    /** "To the top" only means something when the column is ordered by activity. */
    protected sortsByActivity(): boolean;
    protected advanceKey(discussion: Discussion): void;
    applyNew(): Promise<number | null>;
    /**
     * The column's own request, includes and all, narrowed to activity since the
     * key: a range on an indexed column that usually matches nothing.
     */
    protected activeSinceKey(): Promise<ApiResponsePlural<Discussion>>;
    /**
     * `DiscussionListState.addDiscussion()` can't be used here: it removes the
     * discussion through an emitter shared by every list, so it would vanish
     * from the other columns and the index. This moves it within this list only.
     */
    protected moveToTop(discussion: Discussion): void;
    protected snapshot(): void;
}
