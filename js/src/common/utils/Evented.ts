export type EventHandler = (...args: any) => any;

export default class Evented {
    /**
     * Arrays of registered event handlers, grouped by the event name.
     */
    protected handlers: { [key: string]: EventHandler[] } = {};

    /**
     * Get all of the registered handlers for an event.
     *
     * @param event The name of the event.
     */
    protected getHandlers(event: string): EventHandler[] {
        this.handlers = this.handlers || {};

        this.handlers[event] = this.handlers[event] || [];

        return this.handlers[event];
    }

    /**
     * Trigger an event.
     *
     * @param event The name of the event.
     * @param args Arguments to pass to event handlers.
     */
    public trigger(event: string, ...args: any): this {
        this.getHandlers(event).forEach(handler => handler.apply(this, args));

        return this;
    }

    /**
     * Register an event handler.
     *
     * @param event The name of the event.
     * @param handler The function to handle the event.
     */
    on(event: string, handler: EventHandler): this {
        this.getHandlers(event).push(handler);

        return this;
    }

    /**
     * Register an event handler so that it will run only once, and then
     * unregister itself.
     *
     * @param event The name of the event.
     * @param handler The function to handle the event.
     */
    one(event: string, handler: EventHandler): this {
        const wrapper = function(this: Evented) {
            handler.apply(this, Array.from(arguments));

            this.off(event, wrapper);
        };

        this.getHandlers(event).push(wrapper);

        return this;
    }

    /**
     * Unregister an event handler.
     *
     * @param event The name of the event.
     * @param handler The function that handles the event.
     */
    off(event: string, handler: EventHandler): this {
        const handlers = this.getHandlers(event);
        const index = handlers.indexOf(handler);

        if (index !== -1) {
            handlers.splice(index, 1);
        }

        return this;
    }
}
