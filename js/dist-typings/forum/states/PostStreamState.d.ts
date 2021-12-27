export default PostStreamState;
declare class PostStreamState {
    constructor(discussion: any, includedPosts?: any[]);
    /**
     * The discussion to display the post stream for.
     *
     * @type {Discussion}
     */
    discussion: Discussion;
    /**
     * Whether or not the infinite-scrolling auto-load functionality is
     * disabled.
     *
     * @type {Boolean}
     */
    paused: boolean;
    loadPageTimeouts: {};
    pagesLoading: number;
    index: number;
    number: number;
    /**
     * The number of posts that are currently visible in the viewport.
     *
     * @type {Number}
     */
    visible: number;
    /**
     * The description to render on the scrubber.
     *
     * @type {String}
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
     * @type {Boolean}
     */
    forceUpdateScrubber: boolean;
    loadNext: throttle<() => void>;
    loadPrevious: throttle<() => void>;
    /**
     * Update the stream so that it loads and includes the latest posts in the
     * discussion, if the end is being viewed.
     */
    update(): Promise<void>;
    visibleEnd: any;
    /**
     * Load and scroll up to the first post in the discussion.
     *
     * @return {Promise<void>}
     */
    goToFirst(): Promise<void>;
    /**
     * Load and scroll down to the last post in the discussion.
     *
     * @return {Promise<void>}
     */
    goToLast(): Promise<void>;
    /**
     * Load and scroll to a post with a certain number.
     *
     * @param {number | string} number The post number to go to. If 'reply', go to the last post and scroll the reply preview into view.
     * @param {boolean} [noAnimation]
     * @return {Promise<void>}
     */
    goToNumber(number: number | string, noAnimation?: boolean | undefined): Promise<void>;
    loadPromise: Promise<void> | undefined;
    needsScroll: boolean | undefined;
    targetPost: {
        number: string | number;
        index?: undefined;
    } | {
        index: number;
        number?: undefined;
    } | undefined;
    animateScroll: boolean | undefined;
    /**
     * Load and scroll to a certain index within the discussion.
     *
     * @param {number} index
     * @param {boolean} [noAnimation]
     * @return {Promise<void>}
     */
    goToIndex(index: number, noAnimation?: boolean | undefined): Promise<void>;
    /**
     * Clear the stream and load posts near a certain number. Returns a promise.
     * If the post with the given number is already loaded, the promise will be
     * resolved immediately.
     *
     * @param {number} number
     * @return {Promise<void>}
     */
    loadNearNumber(number: number): Promise<void>;
    /**
     * Clear the stream and load posts near a certain index. A page of posts
     * surrounding the given index will be loaded. Returns a promise. If the given
     * index is already loaded, the promise will be resolved immediately.
     *
     * @param {number} index
     * @return {Promise<void>}
     */
    loadNearIndex(index: number): Promise<void>;
    /**
     * Load the next page of posts.
     */
    _loadNext(): void;
    visibleStart: any;
    /**
     * Load the previous page of posts.
     */
    _loadPrevious(): void;
    /**
     * Load a page of posts into the stream and redraw.
     *
     * @param {number} start
     * @param {number} end
     * @param {boolean} backwards
     */
    loadPage(start: number, end: number, backwards?: boolean): void;
    /**
     * Load and inject the specified range of posts into the stream, without
     * clearing it.
     *
     * @param {number} start
     * @param {number} end
     * @return {Promise<void>}
     */
    loadRange(start: number, end: number): Promise<void>;
    /**
     * Set up the stream with the given array of posts.
     *
     * @param {import('../../common/models/Post').default[]} posts
     */
    show(posts: import('../../common/models/Post').default[]): void;
    /**
     * Reset the stream so that a specific range of posts is displayed. If a range
     * is not specified, the first page of posts will be displayed.
     *
     * @param {number} [start]
     * @param {number} [end]
     */
    reset(start?: number | undefined, end?: number | undefined): void;
    /**
     * Get the visible page of posts.
     *
     * @return {Post[]}
     */
    posts(): Post[];
    /**
     * Get the total number of posts in the discussion.
     *
     * @return {number}
     */
    count(): number;
    /**
     * Check whether or not the scrubber should be disabled, i.e. if all of the
     * posts are visible in the viewport.
     *
     * @return {boolean}
     */
    disabled(): boolean;
    /**
     * Are we currently viewing the end of the discussion?
     *
     * @return {boolean}
     */
    viewingEnd(): boolean;
    /**
     * Make sure that the given index is not outside of the possible range of
     * indexes in the discussion.
     *
     * @param {number} index
     */
    sanitizeIndex(index: number): number;
}
declare namespace PostStreamState {
    const loadCount: number;
}
import { throttle } from "throttle-debounce";
