/**
 * The `Pane` class manages the page's discussion list sidepane. The pane is a
 * part of the content view (DiscussionPage component), but its visibility is
 * determined by CSS classes applied to the outer page element. This class
 * manages the application of those CSS classes.
 */
export default class Pane {
    constructor(element: any);
    /**
     * The localStorage key to store the pane's pinned state with.
     *
     * @type {String}
     * @protected
     */
    protected pinnedKey: string;
    /**
     * The page element.
     *
     * @type {jQuery}
     * @protected
     */
    protected $element: JQueryStatic;
    /**
     * Whether or not the pane is currently pinned.
     *
     * @type {Boolean}
     * @protected
     */
    protected pinned: boolean;
    /**
     * Whether or not the pane is currently exists.
     *
     * @type {Boolean}
     * @protected
     */
    protected active: boolean;
    /**
     * Whether or not the pane is currently showing, or is hidden off the edge
     * of the screen.
     *
     * @type {Boolean}
     * @protected
     */
    protected showing: boolean;
    /**
     * Enable the pane.
     */
    enable(): void;
    /**
     * Disable the pane.
     */
    disable(): void;
    /**
     * Show the pane.
     */
    show(): void;
    /**
     * Hide the pane.
     */
    hide(): void;
    /**
     * Begin a timeout to hide the pane, which can be cancelled by showing the
     * pane.
     */
    onmouseleave(): void;
    hideTimeout: NodeJS.Timeout | undefined;
    /**
     * Toggle whether or not the pane is pinned.
     */
    togglePinned(): void;
    /**
     * Apply the appropriate CSS classes to the page element.
     *
     * @protected
     */
    protected render(): void;
}
