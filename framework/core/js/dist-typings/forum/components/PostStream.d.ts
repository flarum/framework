/**
 * The `PostStream` component displays an infinitely-scrollable wall of posts in
 * a discussion. Posts that have not loaded will be displayed as placeholders.
 *
 * ### Attrs
 *
 * - `discussion`
 * - `stream`
 * - `targetPost`
 * - `onPositionChange`
 */
export default class PostStream extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    oninit(vnode: any): void;
    discussion: any;
    stream: any;
    scrollListener: ScrollListener | undefined;
    view(): JSX.Element;
    /**
     * @returns {ItemList<import('mithril').Children>}
     */
    afterFirstPostItems(): ItemList<import('mithril').Children>;
    /**
     * @returns {ItemList<import('mithril').Children>}
     */
    endItems(): ItemList<import('mithril').Children>;
    onupdate(vnode: any): void;
    oncreate(vnode: any): void;
    onremove(vnode: any): void;
    /**
     * Start scrolling, if appropriate, to a newly-targeted post.
     */
    triggerScroll(): void;
    /**
     *
     * @param {number} top
     */
    onscroll(top?: number): void;
    calculatePositionTimeout: NodeJS.Timeout | undefined;
    /**
     * Check if either extreme of the post stream is in the viewport,
     * and if so, trigger loading the next/previous page.
     *
     * @param {number} top
     */
    loadPostsIfNeeded(top?: number): void;
    updateScrubber(top?: number): void;
    /**
     * Compute which post numbers are currently visible in the viewport, without
     * emitting anything. Pure calculation.
     *
     * @param {number} top
     * @returns {{ startNumber: number|undefined, endNumber: number|undefined }}
     */
    computeVisiblePosition(top?: number): {
        startNumber: number | undefined;
        endNumber: number | undefined;
    };
    /**
     * Resolve a definitive post number from the current settling target, for use
     * as an immediate-emit fallback when the DOM scan finds no loaded post.
     *
     * Returns undefined when no number can be determined (placeholder targets,
     * reply targets) — callers should skip the emit in that case.
     *
     * @returns {number|undefined}
     */
    resolveTargetStartNumber(): number | undefined;
    /**
     * Emit a position update to the host page. Single path to call onPositionChange.
     *
     * @param {number} startNumber
     * @param {number|undefined} endNumber
     */
    emitPosition(startNumber: number, endNumber: number | undefined): void;
    /**
     * Work out which posts (by number) are currently visible in the viewport, and
     * fire an event with the information.
     *
     * @param {number} top
     * @param {{ fallbackToTarget?: boolean }} options
     *   When `fallbackToTarget` is true and the DOM scan finds no loaded post with
     *   a `data-number`, the settling target's post number is used as the start.
     *   This allows an immediate URL emit right after a programmatic scroll lands,
     *   before async content has finished loading.
     */
    calculatePosition(top?: number, { fallbackToTarget }?: {
        fallbackToTarget?: boolean;
    }): void;
    /**
     * Get the distance from the top of the viewport to the point at which we
     * would consider a post to be the first one visible.
     *
     * @return {number}
     */
    getMarginTop(): number;
    /**
     * Scroll down to a certain post by number and 'flash' it.
     *
     * @param {number} number
     * @param {boolean} animate
     * @return {JQueryDeferred}
     */
    scrollToNumber(number: number, animate: boolean): JQueryDeferred<any>;
    /**
     * Scroll down to a certain post by index.
     *
     * @param {number} index
     * @param {boolean} animate
     * @param {boolean} reply Whether or not to scroll to the reply placeholder.
     * @return {JQueryDeferred}
     */
    scrollToIndex(index: number, animate: boolean, reply: boolean): JQueryDeferred<any>;
    /**
     * Scroll down to the given post.
     *
     * @param {JQuery} $item
     * @param {boolean} animate
     * @param {boolean} force Whether or not to force scrolling to the item, even
     *     if it is already in the viewport.
     * @param {boolean} reply Whether or not to scroll to the reply placeholder.
     * @return {JQueryDeferred}
     */
    scrollToItem($item: JQuery, animate: boolean, force: boolean, reply: boolean): JQueryDeferred<any>;
    /**
     * Set up a ResizeObserver that watches `.PostStream-item` elements for size
     * changes. During the "settling" phase (after a programmatic scroll), any
     * observed resize triggers an immediate re-scroll to the target post so the
     * viewport stays locked on it while asynchronous content loads.
     */
    setupScrollAnchoring(): void;
    settling: boolean | undefined;
    settlingTarget: {
        number: number;
    } | {
        index: number;
        reply?: boolean | undefined;
    } | null | undefined;
    settlingStabilityTimer: NodeJS.Timeout | null | undefined;
    settlingHardTimer: NodeJS.Timeout | null | undefined;
    observedElements: Set<any> | null | undefined;
    resizeObserver: ResizeObserver | null | undefined;
    /**
     * Re-scroll to the locked target post. Uses the same offset logic as
     * scrollToItem's post-load adjustment. Called synchronously from the
     * ResizeObserver callback so the correction is applied before the browser
     * paints, preventing visible flicker.
     */
    reScrollToTarget(): void;
    /**
     * Begin settling mode for the given target. Fully tears down any in-progress
     * settling first to avoid listener and timer leaks on consecutive jumps, then
     * arms fresh listeners and timers.
     *
     * @param {{ number: number }|{ index: number, reply?: boolean }} target
     */
    beginSettling(target: {
        number: number;
    } | {
        index: number;
        reply?: boolean;
    }): void;
    /**
     * Register global event listeners that end settling when the user takes
     * control (scrolling, touching, or pressing a key).
     */
    startSettlingListeners(): void;
    _onUserInteraction: (() => void) | null | undefined;
    /**
     * Remove the user-interaction listeners registered by startSettlingListeners.
     */
    stopSettlingListeners(): void;
    /**
     * Reset (or start) the stability timer. If no ResizeObserver callback fires
     * within 3 seconds, we consider the layout stable and exit settling.
     */
    resetStabilityTimer(): void;
    /**
     * Exit settling mode: stop watching for layout shifts, remove listeners,
     * clear timers, and optionally update the URL/scrubber to match the final viewport.
     *
     * @param {{ updatePosition?: boolean }} options
     *   `updatePosition` defaults to `true` (normal end-of-settling reconciliation).
     *   Pass `false` when called from the unmount path so no stale history entry is
     *   written after the user has already navigated away.
     */
    endSettling({ updatePosition }?: {
        updatePosition?: boolean;
    }): void;
    /**
     * Observe any newly-rendered `.PostStream-item` elements that are not yet
     * tracked. Called on every `onupdate` to pick up items added by infinite
     * scroll or lazy loading.
     */
    observeNewPostItems(): void;
    /**
     * Disconnect the ResizeObserver, end settling mode, and release tracked data.
     * Called from `onremove` to prevent memory leaks.
     *
     * Passes `updatePosition: false` to endSettling so that no history entry is
     * written after the user has already navigated away from this discussion.
     */
    cleanupScrollAnchoring(): void;
    /**
     * 'Flash' the given post, drawing the user's attention to it.
     *
     * @param {JQuery} $item
     */
    flashItem($item: JQuery): void;
}
import Component from "../../common/Component";
import ScrollListener from "../../common/utils/ScrollListener";
import ItemList from "../../common/utils/ItemList";
