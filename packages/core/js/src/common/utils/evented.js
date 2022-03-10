import { fireDeprecationWarning } from '../helpers/fireDebugWarning';

const deprecatedNotice = 'The `evented` util is deprecated and no longer supported.';
const deprecationIssueId = '2547';

/**
 * The `evented` mixin provides methods allowing an object to trigger events,
 * running externally registered event handlers.
 *
 * @deprecated v1.2, to be removed in v2.0
 */
export default {
  /**
   * Arrays of registered event handlers, grouped by the event name.
   *
   * @type {Record<string, unknown>}
   * @protected
   *
   * @deprecated
   */
  handlers: null,

  /**
   * Get all of the registered handlers for an event.
   *
   * @param {string} event The name of the event.
   * @return {Function[]}
   * @protected
   *
   * @deprecated
   */
  getHandlers(event) {
    fireDeprecationWarning(deprecatedNotice, deprecationIssueId);

    this.handlers = this.handlers || {};

    this.handlers[event] = this.handlers[event] || [];

    return this.handlers[event];
  },

  /**
   * Trigger an event.
   *
   * @param {string} event The name of the event.
   * @param {any[]} args Arguments to pass to event handlers.
   *
   * @deprecated
   */
  trigger(event, ...args) {
    fireDeprecationWarning(deprecatedNotice, deprecationIssueId);

    this.getHandlers(event).forEach((handler) => handler.apply(this, args));
  },

  /**
   * Register an event handler.
   *
   * @param {string} event The name of the event.
   * @param {Function} handler The function to handle the event.
   *
   * @deprecated
   */
  on(event, handler) {
    fireDeprecationWarning(deprecatedNotice, deprecationIssueId);

    this.getHandlers(event).push(handler);
  },

  /**
   * Register an event handler so that it will run only once, and then
   * unregister itself.
   *
   * @param {string} event The name of the event.
   * @param {Function} handler The function to handle the event.
   *
   * @deprecated
   */
  one(event, handler) {
    fireDeprecationWarning(deprecatedNotice, deprecationIssueId);

    const wrapper = function () {
      handler.apply(this, arguments);

      this.off(event, wrapper);
    };

    this.getHandlers(event).push(wrapper);
  },

  /**
   * Unregister an event handler.
   *
   * @param {string} event The name of the event.
   * @param {Function} handler The function that handles the event.
   *
   * @deprecated
   */
  off(event, handler) {
    fireDeprecationWarning(deprecatedNotice, deprecationIssueId);

    const handlers = this.getHandlers(event);
    const index = handlers.indexOf(handler);

    if (index !== -1) {
      handlers.splice(index, 1);
    }
  },
};
