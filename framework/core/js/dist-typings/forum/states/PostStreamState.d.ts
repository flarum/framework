/// <reference types="node" />
import type Discussion from '../../common/models/Discussion';
import type Post from '../../common/models/Post';
export default class PostStreamState {
    /**
     * The number of posts to load per page.
     */
    static loadCount: number;
    /**
     * The discussion to display the post stream for.
     */
    discussion: Discussion;
    /**
     * Whether or not the infinite-scrolling auto-load functionality is
     * disabled.
     */
    paused: boolean;
    loadPageTimeouts: Record<number, NodeJS.Timeout>;
    pagesLoading: number;
    index: number;
    number: number;
    /**
     * The number of posts that are currently visible in the viewport.
     */
    visible: number;
    visibleStart: number;
    visibleEnd: number;
    animateScroll: boolean;
    needsScroll: boolean;
    targetPost: {
        number: number;
    } | {
        index: number;
        reply?: boolean;
    } | null;
    /**
     * The description to render on the scrubber.
     */
    description: string;
    /**
     * When the page is scrolled, goToIndex is called, or the page is loaded,
     * various listeners result in the scrubber being updated with a new
     * position and values. However, if goToNumber is called, the scrubber
     * will not be updated. Accordingly, we add logic to the scrubber's
     * onupdate to update itself, but only when needed, as indicated by this
     * property.
     *
     */
    forceUpdateScrubber: boolean;
    loadPromise: Promise<void> | null;
    loadNext: () => void;
    loadPrevious: () => void;
    constructor(discussion: Discussion, includedPosts?: Post[]);
    /**
     * Update the stream so that it loads and includes the latest posts in the
     * discussion, if the end is being viewed.
     */
    update(): Promise<void> | Promise<Post[]>;
    /**
     * Load and scroll up to the first post in the discussion.
     */
    goToFirst(): Promise<void>;
    /**
     * Load and scroll down to the last post in the discussion.
     */
    goToLast(): Promise<void>;
    /**
     * Load and scroll to a post with a certain number.
     *
     * @param number The post number to go to. If 'reply', go to the last post and scroll the reply preview into view.
     */
    goToNumber(number: number | 'reply', noAnimation?: boolean): Promise<void>;
    /**
     * Load and scroll to a certain index within the discussion.
     */
    goToIndex(index: number, noAnimation?: boolean): Promise<void>;
    /**
     * Clear the stream and load posts near a certain number. Returns a promise.
     * If the post with the given number is already loaded, the promise will be
     * resolved immediately.
     */
    loadNearNumber(number: number): Promise<void>;
    /**
     * Clear the stream and load posts near a certain index. A page of posts
     * surrounding the given index will be loaded. Returns a promise. If the given
     * index is already loaded, the promise will be resolved immediately.
     */
    loadNearIndex(index: number): Promise<void>;
    /**
     * Load the next page of posts.
     */
    _loadNext(): void;
    /**
     * Load the previous page of posts.
     */
    _loadPrevious(): void;
    /**
     * Load a page of posts into the stream and redraw.
     */
    loadPage(start: number, end: number, backwards?: boolean): void;
    /**
     * Load and inject the specified range of posts into the stream, without
     * clearing it.
     */
    loadRange(start: number, end: number): Promise<Post[]>;
    /**
     * Set up the stream with the given array of posts.
     */
    show(posts: Post[]): void;
    /**
     * Reset the stream so that a specific range of posts is displayed. If a range
     * is not specified, the first page of posts will be displayed.
     */
    reset(start?: number, end?: number): void;
    /**
     * Get the visible page of posts.
     */
    posts(): (Post | null)[];
    /**
     * Get the total number of posts in the discussion.
     */
    count(): number;
    /**
     * Check whether or not the scrubber should be disabled, i.e. if all of the
     * posts are visible in the viewport.
     */
    disabled(): boolean;
    /**
     * Are we currently viewing the end of the discussion?
     */
    viewingEnd(): boolean;
    /**
     * Make sure that the given index is not outside of the possible range of
     * indexes in the discussion.
     */
    sanitizeIndex(index: number): number;
}
