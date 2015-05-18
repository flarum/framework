export default {
  handlers: null,

  /**

   */
  getHandlers(event) {
    this.handlers = this.handlers || {};
    return this.handlers[event] = this.handlers[event] || [];
  },

  /**

   */
  trigger(event, ...args) {
    this.getHandlers(event).forEach((handler) => handler.apply(this, args));
  },

  /**

   */
  on(event, handler) {
    this.getHandlers(event).push(handler);
  },

  /**

   */
  one(event, handler) {
    var wrapper = function() {
      handler.apply(this, arguments);
      this.off(event, wrapper);
    };
    this.getHandlers(event).push(wrapper);
  },

  /**

   */
  off(event, handler) {
    var handlers = this.getHandlers(event);
    var index = handlers.indexOf(handler);
    if (index !== -1) {
      handlers.splice(index, 1);
    }
  }
}
