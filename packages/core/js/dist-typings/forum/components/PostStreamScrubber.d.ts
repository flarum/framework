/**
 * The `PostStreamScrubber` component displays a scrubber which can be used to
 * navigate/scrub through a post stream.
 *
 * ### Attrs
 *
 * - `stream`
 * - `className`
 */
export default class PostStreamScrubber extends Component<import("../../common/Component").ComponentAttrs> {
    constructor();
    stream: any;
    handlers: {} | undefined;
    scrollListener: ScrollListener | undefined;
    dragging: boolean | undefined;
    mouseStart: any;
    indexStart: any;
    /**
     * Update the scrollbar's position to reflect the current values of the
     * index/visible properties.
     *
     * @param {Boolean} animate
     */
    updateScrubberValues(options?: {}): void;
    adjustingHeight: boolean | undefined;
    /**
     * Go to the first post in the discussion.
     */
    goToFirst(): void;
    /**
     * Go to the last post in the discussion.
     */
    goToLast(): void;
    onresize(): void;
    onmousedown(e: any): void;
    onmousemove(e: any): void;
    onmouseup(): void;
    onclick(e: any): void;
    /**
     * Get the percentage of the height of the scrubber that should be allocated
     * to each post.
     *
     * @return {Object}
     * @property {Number} index The percent per post for posts on either side of
     *     the visible part of the scrubber.
     * @property {Number} visible The percent per post for the visible part of the
     *     scrubber.
     */
    percentPerPost(): Object;
}
import Component from "../../common/Component";
import ScrollListener from "../../common/utils/ScrollListener";
