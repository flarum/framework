import type PostListState from 'flarum/forum/states/PostListState';
import type { PostListParams } from 'flarum/forum/states/PostListState';
import type Post from 'flarum/common/models/Post';
import type Mithril from 'mithril';
import type { DeckColumnSource, DeckRealtimeEvent, DeckRealtimeResult } from './DeckColumnType';
import type Discussion from 'flarum/common/models/Discussion';
/**
 * A newest-first column of comments, backed by its own PostListState, so any
 * post filter the API understands works. Post ids only grow, so the newest id
 * on screen is the key for asking "anything newer?".
 */
export default class PostListSource implements DeckColumnSource {
    readonly state: PostListState;
    protected key: number;
    constructor(params: PostListParams);
    load(): Promise<unknown>;
    /** Runs once before the first load, for columns whose params need looking up. */
    protected prepare(): Promise<unknown>;
    view(): Mithril.Children;
    checkForNew(): Promise<number>;
    applyNew(): Promise<number | null>;
    showNew(): Promise<unknown>;
    /**
     * A new reply that certainly belongs here is inserted from the payload; one
     * that certainly doesn't is ignored; only undecidable ones ask the server.
     * A new discussion's first post isn't in its Started payload, so a column
     * that may want it asks.
     */
    onRealtime(event: DeckRealtimeEvent): DeckRealtimeResult;
    /** For a prune() that takes posts out: the next page fills an emptied column. */
    protected refill(): Promise<void>;
    /** Whether a post belongs in this column: undefined when only the server can tell. */
    protected matches(post: Post): boolean | undefined;
    /** Whether a new discussion's first post might belong here: false rules it out. */
    protected mayMatchNewDiscussion(discussion: Discussion): boolean | undefined;
    protected shows(post: Post): boolean;
    protected removePost(id: string): boolean;
    protected insert(post: Post): boolean;
    /** The column's own request, newest first: whatever it asks for, includes and all, the new posts have too. */
    protected newerPosts(): Promise<Post[]>;
    protected snapshot(): void;
}
