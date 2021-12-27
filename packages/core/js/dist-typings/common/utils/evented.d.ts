declare namespace _default {
    const handlers: Record<string, unknown>;
    /**
     * Get all of the registered handlers for an event.
     *
     * @param {string} event The name of the event.
     * @return {Function[]}
     * @protected
     *
     * @deprecated
     */
    function getHandlers(event: string): Function[];
    /**
     * Get all of the registered handlers for an event.
     *
     * @param {string} event The name of the event.
     * @return {Function[]}
     * @protected
     *
     * @deprecated
     */
    function getHandlers(event: string): Function[];
    /**
     * Trigger an event.
     *
     * @param {string} event The name of the event.
     * @param {any[]} args Arguments to pass to event handlers.
     *
     * @deprecated
     */
    function trigger(event: string, ...args: any[]): void;
    /**
     * Trigger an event.
     *
     * @param {string} event The name of the event.
     * @param {any[]} args Arguments to pass to event handlers.
     *
     * @deprecated
     */
    function trigger(event: string, ...args: any[]): void;
    /**
     * Register an event handler.
     *
     * @param {string} event The name of the event.
     * @param {Function} handler The function to handle the event.
     *
     * @deprecated
     */
    function on(event: string, handler: Function): void;
    /**
     * Register an event handler.
     *
     * @param {string} event The name of the event.
     * @param {Function} handler The function to handle the event.
     *
     * @deprecated
     */
    function on(event: string, handler: Function): void;
    /**
     * Register an event handler so that it will run only once, and then
     * unregister itself.
     *
     * @param {string} event The name of the event.
     * @param {Function} handler The function to handle the event.
     *
     * @deprecated
     */
    function one(event: string, handler: Function): void;
    /**
     * Register an event handler so that it will run only once, and then
     * unregister itself.
     *
     * @param {string} event The name of the event.
     * @param {Function} handler The function to handle the event.
     *
     * @deprecated
     */
    function one(event: string, handler: Function): void;
    /**
     * Unregister an event handler.
     *
     * @param {string} event The name of the event.
     * @param {Function} handler The function that handles the event.
     *
     * @deprecated
     */
    function off(event: string, handler: Function): void;
    /**
     * Unregister an event handler.
     *
     * @param {string} event The name of the event.
     * @param {Function} handler The function that handles the event.
     *
     * @deprecated
     */
    function off(event: string, handler: Function): void;
}
export default _default;
