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
 * @param object The object that owns the method
 * @param methods The name or names of the method(s) to extend
 * @param callback A callback which mutates the method's output
 */
export declare function extend<T extends Record<string, any>, K extends KeyOfType<T, Function>>(object: T, methods: K | K[], callback: (this: T, val: ReturnType<T[K]>, ...args: Parameters<T[K]>) => void): void;
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
 * @param object The object that owns the method
 * @param methods The name or names of the method(s) to override
 * @param newMethod The method to replace it with
 */
export declare function override<T extends Record<any, any>, K extends KeyOfType<T, Function>>(object: T, methods: K | K[], newMethod: (this: T, orig: T[K], ...args: Parameters<T[K]>) => void): void;
