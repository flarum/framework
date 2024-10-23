/**
 * The `ScrollListener` class sets up a listener that handles element scroll
 * events.
 */
export default class ScrollListener {
    /**
     * @param {(top: number) => void} callback The callback to run when the scroll position
     *     changes.
     * @param {Window|Element} element The element to listen for scroll events on. Defaults to
     *    `window`.
     */
    constructor(callback: (top: number) => void, element?: Window | Element);
    callback: (top: number) => void;
    ticking: boolean;
    element: Window | Element;
    /**
     * On each animation frame, as long as the listener is active, run the
     * `update` method.
     *
     * @protected
     */
    protected loop(): void;
    /**
     * Run the callback, whether there was a scroll event or not.
     */
    update(): void;
    /**
     * Start listening to and handling the element's scroll position.
     */
    start(): void;
    active: (() => void) | null | undefined;
    /**
     * Stop listening to and handling the element's scroll position.
     */
    stop(): void;
}
