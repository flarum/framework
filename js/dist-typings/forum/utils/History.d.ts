/**
 * The `History` class keeps track and manages a stack of routes that the user
 * has navigated to in their session.
 *
 * An item can be pushed to the top of the stack using the `push` method. An
 * item in the stack has a name and a URL. The name need not be unique; if it is
 * the same as the item before it, that will be overwritten with the new URL. In
 * this way, if a user visits a discussion, and then visits another discussion,
 * popping the history stack will still take them back to the discussion list
 * rather than the previous discussion.
 */
export default class History {
    constructor(defaultRoute: any);
    /**
     * The stack of routes that have been navigated to.
     *
     * @type {Array}
     * @protected
     */
    protected stack: any[];
    /**
     * Get the item on the top of the stack.
     *
     * @return {Object}
     * @public
     */
    public getCurrent(): Object;
    /**
     * Get the previous item on the stack.
     *
     * @return {Object}
     * @public
     */
    public getPrevious(): Object;
    /**
     * Push an item to the top of the stack.
     *
     * @param {String} name The name of the route.
     * @param {String} title The title of the route.
     * @param {String} [url] The URL of the route. The current URL will be used if
     *     not provided.
     * @public
     */
    public push(name: string, title: string, url?: string | undefined): void;
    /**
     * Check whether or not the history stack is able to be popped.
     *
     * @return {Boolean}
     * @public
     */
    public canGoBack(): boolean;
    /**
     * Go back to the previous route in the history stack.
     *
     * @public
     */
    public back(): void;
    /**
     * Get the URL of the previous page.
     *
     * @public
     */
    public backUrl(): any;
    /**
     * Go to the first route in the history stack.
     *
     * @public
     */
    public home(): void;
}
