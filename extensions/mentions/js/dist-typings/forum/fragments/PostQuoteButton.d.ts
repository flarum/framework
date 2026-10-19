export default class PostQuoteButton extends Fragment {
    constructor(post: any);
    post: any;
    hideHandler: () => void;
    view(): JSX.Element;
    show(left: any, top: any): void;
    showStart(left: any, top: any): void;
    showEnd(right: any, bottom: any): void;
    /**
     * Show the button for a selection made by touch: well below its end, or
     * well above its start when there is no room below. A selection taller
     * than the screen leaves room for neither, so the button is kept inside
     * the visible part of the page either way.
     *
     * @param {DOMRect} firstRect The client rect of the selection's first line.
     * @param {DOMRect} lastRect The client rect of the selection's last line.
     */
    showTouch(firstRect: DOMRect, lastRect: DOMRect): void;
    hide(): void;
}
import Fragment from "flarum/common/Fragment";
