import PostListSource from 'ext:flarum/deck/forum/columns/PostListSource';
import type { DeckRealtimeEvent, DeckRealtimeResult } from 'ext:flarum/deck/forum/columns/DeckColumnType';
import type Post from 'flarum/common/models/Post';
/**
 * Posts with open flags, most recently flagged first, each with Flags' own
 * flag bar to act on. The server decides what's listed, so only flags the
 * member may see ever appear.
 */
export default class FlaggedPostsSource extends PostListSource {
    constructor();
    onRealtime(event: DeckRealtimeEvent): DeckRealtimeResult;
    /** A new reply has no flags yet. */
    protected matches(): boolean;
    protected mayMatchNewDiscussion(): boolean;
    /** A post whose flags were dismissed, here or elsewhere, leaves. */
    prune(): void;
    /** An old post can be flagged at any time, so the queue is reloaded rather than added to. */
    applyNew(): Promise<number | null>;
    checkForNew(): Promise<number>;
    protected shownPosts(): Post[];
}
