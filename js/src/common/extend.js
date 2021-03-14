/**
 * Extend an object's method by running its output through a mutating callback
 * every time it is called.
 *
 * The callback accepts the method's return value and should perform any
 * mutations directly on this value. For this reason, this function will not be
 * effective on methods which return scalar values (numbers, strings, booleans).
 *
 * Care should be taken to extend the correct object – in most cases, a class'
 * prototype will be the desired target of extension, not the class itself.
 *
 * @example <caption>Example usage of extending one method.</caption>
 * extend(Discussion.prototype, 'badges', function(badges) {
 *   // do something with `badges`
 * });
 *
 * @example <caption>Example usage of extending multiple methods.</caption>
 * extend(IndexPage.prototype, ['oncreate', 'onupdate'], function(vnode) {
 *   // something that needs to be run on creation and update
 * });
 *
 * @param {object} object The object that owns the method
 * @param {string|string[]} methods The name or names of the method(s) to extend
 * @param {function} callback A callback which mutates the method's output
 */
export function extend(object, methods, callback) {
  const allMethods = Array.isArray(methods) ? methods : [methods];

  allMethods.forEach((method) => {
    const original = object[method];

    object[method] = function (...args) {
      const value = original ? original.apply(this, args) : undefined;

      callback.apply(this, [value].concat(args));

      return value;
    };

    Object.assign(object[method], original);
  });
}

/**
 * Override an object's method by replacing it with a new function, so that the
 * new function will be run every time the object's method is called.
 *
 * The replacement function accepts the original method as its first argument,
 * which is like a call to `super`. Any arguments passed to the original method
 * are also passed to the replacement.
 *
 * Care should be taken to extend the correct object – in most cases, a class'
 * prototype will be the desired target of extension, not the class itself.
 *
 * @example <caption>Example usage of overriding one method.</caption>
 * override(Discussion.prototype, 'badges', function(original) {
 *   const badges = original();
 *   // do something with badges
 *   return badges;
 * });
 *
 * @example <caption>Example usage of overriding multiple methods.</caption>
 * extend(Discussion.prototype, ['oncreate', 'onupdate'], function(original, vnode) {
 *   // something that needs to be run on creation and update
 * });
 *
 * @param {object} object The object that owns the method
 * @param {string|string[]} method The name or names of the method(s) to override
 * @param {function} newMethod The method to replace it with
 */
export function override(object, methods, newMethod) {
  const allMethods = Array.isArray(methods) ? methods : [methods];

  allMethods.forEach((method) => {
    const original = object[method];

    object[method] = function (...args) {
      return newMethod.apply(this, [original.bind(this)].concat(args));
    };

    Object.assign(object[method], original);
  });
}
