export interface HistoryEntry {
    name: string;
    title: string;
    url: string;
}
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
    /**
     * The stack of routes that have been navigated to.
     */
    protected stack: HistoryEntry[];
    /**
     * Get the item on the top of the stack.
     */
    getCurrent(): HistoryEntry;
    /**
     * Get the previous item on the stack.
     */
    getPrevious(): HistoryEntry;
    /**
     * Push an item to the top of the stack.
     *
     * @param {string} name The name of the route.
     * @param {string} title The title of the route.
     * @param {string} [url] The URL of the route. The current URL will be used if
     *     not provided.
     */
    push(name: string, title: string, url?: string): void;
    /**
     * Check whether or not the history stack is able to be popped.
     */
    canGoBack(): boolean;
    /**
     * Go back to the previous route in the history stack.
     */
    back(): void;
    /**
     * Get the URL of the previous page.
     */
    backUrl(): string;
    /**
     * Go to the first route in the history stack.
     */
    home(): void;
}
