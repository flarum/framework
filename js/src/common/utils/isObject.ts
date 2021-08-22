/**
 * Returns if the passed value is an object.
 *
 * In this context, "object" refers to **any non-primitive value**, including
 * arrays, function, maps, dates, and more.
 *
 * @example
 * isObject({}); // true
 * @example
 * isObject([]); // true
 * @example
 * isObject(function () {}); // true
 * @example
 * isObject(Object(1)); // true
 * @example
 * isObject(null); // false
 * @example
 * isObject(1); // false
 * @example
 * isObject("hello world"); // false
 *
 * @see https://github.com/jashkenas/underscore/blob/943977e34e2279503528a71ddcc2dd5f96483945/underscore.js#L87-L91
 */
export default function isObject(obj: unknown): obj is object {
  const type = typeof obj;
  return type === 'function' || (type === 'object' && !!obj);
}
