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
  constructor(defaultRoute) {
    /**
     * The stack of routes that have been navigated to.
     *
     * @type {Array}
     * @protected
     */
    this.stack = [];
  }

  /**
   * Get the item on the top of the stack.
   *
   * @return {Object}
   * @protected
   */
  getTop() {
    return this.stack[this.stack.length - 1];
  }

  /**
   * Push an item to the top of the stack.
   *
   * @param {String} name The name of the route.
   * @param {String} [url] The URL of the route. The current URL will be used if
   *     not provided.
   * @public
   */
  push(name, url = m.route()) {
    // If we're pushing an item with the same name as second-to-top item in the
    // stack, we will assume that the user has clicked the 'back' button in
    // their browser. In this case, we don't want to push a new item, so we will
    // pop off the top item, and then the second-to-top item will be overwritten
    // below.
    const secondTop = this.stack[this.stack.length - 2];
    if (secondTop && secondTop.name === name) {
      this.stack.pop();
    }

    // If we're pushing an item with the same name as the top item in the stack,
    // then we'll overwrite it with the new URL.
    const top = this.getTop();
    if (top && top.name === name) {
      top.url = url;
    } else {
      this.stack.push({name, url});
    }
  }

  /**
   * Check whether or not the history stack is able to be popped.
   *
   * @return {Boolean}
   * @public
   */
  canGoBack() {
    return this.stack.length > 1;
  }

  /**
   * Go back to the previous route in the history stack.
   *
   * @public
   */
  back() {
    this.stack.pop();

    m.route(this.getTop().url);
  }

  /**
   * Get the URL of the previous page.
   *
   * @public
   */
  backUrl() {
    const secondTop = this.stack[this.stack.length - 2];

    return secondTop.url;
  }

  /**
   * Go to the first route in the history stack.
   *
   * @public
   */
  home() {
    this.stack.splice(1);

    m.route('/');
  }
}
