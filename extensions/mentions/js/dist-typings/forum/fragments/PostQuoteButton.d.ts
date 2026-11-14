export default class PostQuoteButton extends Fragment {
    constructor(post: any);
    post: any;
    hideHandler: () => void;
    view(): JSX.Element;
    show(left: any, top: any): void;
    showStart(left: any, top: any): void;
    showEnd(right: any, bottom: any): void;
    /**
     * Show the button beside the selected line, clear of its end handle.
     * A single line can also use the space just below its middle. Otherwise,
     * keep the larger clearance above or below for the native menu.
     *
     * A long selection, which can run past the edges of the screen, leaves
     * room for neither. The button then goes in the middle of the screen,
     * away from the handles and text menu at the selection's ends. Pinning
     * it to the top of the screen put it under the header, where a tap
     * landed on the header instead.
     *
     * @param {DOMRect} firstRect The client rect of the selection's first line.
     * @param {DOMRect} lastRect The client rect of the selection's last line.
     * @param {ReadonlyArray<DOMRect>} [rects] Complete selection rectangles for side placement.
     */
    showTouch(firstRect: DOMRect, lastRect: DOMRect, rects?: ReadonlyArray<DOMRect>): void;
    hide(): void;
}
import Fragment from 'flarum/common/Fragment';
