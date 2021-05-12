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
export default class PostStream extends Component<import("../../common/Component").ComponentAttrs> {
    constructor();
    discussion: any;
    stream: any;
    scrollListener: ScrollListener | undefined;
    /**
     * Start scrolling, if appropriate, to a newly-targeted post.
     */
    triggerScroll(): void;
    /**
     *
     * @param {Integer} top
     */
    onscroll(top?: any): void;
    calculatePositionTimeout: number | undefined;
    /**
     * Check if either extreme of the post stream is in the viewport,
     * and if so, trigger loading the next/previous page.
     *
     * @param {Integer} top
     */
    loadPostsIfNeeded(top?: any): void;
    updateScrubber(top?: number): void;
    /**
     * Work out which posts (by number) are currently visible in the viewport, and
     * fire an event with the information.
     */
    calculatePosition(top?: number): void;
    /**
     * Get the distance from the top of the viewport to the point at which we
     * would consider a post to be the first one visible.
     *
     * @return {Integer}
     */
    getMarginTop(): any;
    /**
     * Scroll down to a certain post by number and 'flash' it.
     *
     * @param {Integer} number
     * @param {Boolean} animate
     * @return {jQuery.Deferred}
     */
    scrollToNumber(number: any, animate: boolean): any;
    /**
     * Scroll down to a certain post by index.
     *
     * @param {Integer} index
     * @param {Boolean} animate
     * @param {Boolean} reply Whether or not to scroll to the reply placeholder.
     * @return {jQuery.Deferred}
     */
    scrollToIndex(index: any, animate: boolean, reply: boolean): any;
    /**
     * Scroll down to the given post.
     *
     * @param {jQuery} $item
     * @param {Boolean} animate
     * @param {Boolean} force Whether or not to force scrolling to the item, even
     *     if it is already in the viewport.
     * @param {Boolean} reply Whether or not to scroll to the reply placeholder.
     * @return {jQuery.Deferred}
     */
    scrollToItem($item: JQueryStatic, animate: boolean, force: boolean, reply: boolean): any;
    /**
     * 'Flash' the given post, drawing the user's attention to it.
     *
     * @param {jQuery} $item
     */
    flashItem($item: JQueryStatic): void;
}
import Component from "../../common/Component";
import ScrollListener from "../../common/utils/ScrollListener";
