/**
 * The `SubtreeRetainer` class keeps track of a number of pieces of data,
 * comparing the values of these pieces at every iteration.
 *
 * This is useful for preventing redraws to relatively static (or huge)
 * components whose VDOM only depends on very few values, when none of them
 * have changed.
 *
 * @example
 * // Check two callbacks for changes on each update
 * this.subtree = new SubtreeRetainer(
 *   () => this.attrs.post.freshness,
 *   () => this.showing
 * );
 *
 * // Add more callbacks to be checked for updates
 * this.subtree.check(() => this.attrs.user.freshness);
 *
 * // In a component's onbeforeupdate() method:
 * return this.subtree.needsRebuild()
 *
 * @see https://mithril.js.org/lifecycle-methods.html#onbeforeupdate
 */
export default class SubtreeRetainer {
  /**
   * @param {...callbacks} callbacks Functions returning data to keep track of.
   */
  constructor(...callbacks) {
    this.callbacks = callbacks;
    this.data = {};
    // Build the initial data, so it is available when calling
    // needsRebuild from the onbeforeupdate hook.
    this.needsRebuild();
  }

  /**
   * Return whether any data has changed since the last check.
   * If so, Mithril needs to re-diff the vnode and its children.
   *
   * @return {boolean}
   * @public
   */
  needsRebuild() {
    let needsRebuild = false;

    this.callbacks.forEach((callback, i) => {
      const result = callback();

      if (result !== this.data[i]) {
        this.data[i] = result;
        needsRebuild = true;
      }
    });

    return needsRebuild;
  }

  /**
   * Add another callback to be checked.
   *
   * @param {...Function} callbacks
   * @public
   */
  check(...callbacks) {
    this.callbacks = this.callbacks.concat(callbacks);
    // Update the data cache when new checks are added.
    this.needsRebuild();
  }

  /**
   * Invalidate the subtree, forcing it to be rerendered.
   *
   * @public
   */
  invalidate() {
    this.data = {};
  }
}
