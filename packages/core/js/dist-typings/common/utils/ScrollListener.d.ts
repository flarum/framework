/**
 * The `ScrollListener` class sets up a listener that handles window scroll
 * events.
 */
export default class ScrollListener {
    /**
     * @param {Function} callback The callback to run when the scroll position
     *     changes.
     * @public
     */
    constructor(callback: Function);
    callback: Function;
    ticking: boolean;
    /**
     * On each animation frame, as long as the listener is active, run the
     * `update` method.
     *
     * @protected
     */
    protected loop(): void;
    /**
     * Run the callback, whether there was a scroll event or not.
     *
     * @public
     */
    public update(): void;
    /**
     * Start listening to and handling the window's scroll position.
     *
     * @public
     */
    public start(): void;
    active: (() => void) | null | undefined;
    /**
     * Stop listening to and handling the window's scroll position.
     *
     * @public
     */
    public stop(): void;
}
