/**
 * The `Composer` component displays the composer. It can be loaded with a
 * content component with `load` and then its position/state can be altered with
 * `show`, `hide`, `close`, `minimize`, `fullScreen`, and `exitFullScreen`.
 */
export default class Composer extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    oninit(vnode: any): void;
    /**
     * The composer's "state".
     *
     * @type {ComposerState}
     */
    state: ComposerState | undefined;
    /**
     * Whether or not the composer currently has focus.
     *
     * @type {Boolean}
     */
    active: boolean | undefined;
    prevPosition: any;
    view(): JSX.Element;
    onupdate(vnode: any): void;
    oncreate(vnode: any): void;
    handlers: {} | undefined;
    onremove(vnode: any): void;
    /**
     * Add the necessary event handlers to the composer's handle so that it can
     * be used to resize the composer.
     */
    configHandle(vnode: any): void;
    /**
     * Resize the composer according to mouse movement.
     *
     * @param {MouseEvent} e
     */
    onmousemove(e: MouseEvent): void;
    /**
     * Finish resizing the composer when the mouse is released.
     */
    onmouseup(): void;
    handle: any;
    /**
     * Draw focus to the first focusable content element (the text editor).
     */
    focus(): void;
    /**
     * Update the DOM to reflect the composer's current height. This involves
     * setting the height of the composer's root element, and adjusting the height
     * of any flexible elements inside the composer's body.
     */
    updateHeight(): void;
    /**
     * Update the amount of padding-bottom on the body so that the page's
     * content will still be visible above the composer when the page is
     * scrolled right to the bottom.
     */
    updateBodyPadding(): void;
    /**
     * Trigger the right animation depending on the desired new position.
     */
    animatePositionChange(): void;
    /**
     * Animate the Composer into the new position by changing the height.
     */
    animateHeightChange(): JQuery.Promise<JQuery<HTMLElement>, any, any>;
    /**
     * Show the Composer backdrop.
     */
    showBackdrop(): void;
    $backdrop: JQuery<HTMLElement> | undefined;
    /**
     * Hide the Composer backdrop.
     */
    hideBackdrop(): void;
    /**
     * Animate the composer sliding up from the bottom to take its normal height.
     *
     * @private
     */
    private show;
    /**
     * Animate closing the composer.
     *
     * @private
     */
    private hide;
    /**
     * Shrink the composer until only its title is visible.
     *
     * @private
     */
    private minimize;
    /**
     * Build an item list for the composer's controls.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    controlItems(): ItemList<import('mithril').Children>;
    /**
     * Initialize default Composer height.
     */
    initializeHeight(): void;
    /**
     * Default height of the Composer in case none is saved.
     * @returns {number}
     */
    defaultHeight(): number;
    /**
     * Save a new Composer height and update the DOM.
     * @param {number} height
     */
    changeHeight(height: number): void;
}
import Component from "../../common/Component";
import ComposerState from "../states/ComposerState";
import ItemList from "../../common/utils/ItemList";
