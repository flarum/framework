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
  off(event, handler) {
    var handlers = this.getHandlers(event);
    var index = handlers.indexOf(handler);
    if (index !== -1) {
      handlers.splice(index, 1);
    }
  }
}
