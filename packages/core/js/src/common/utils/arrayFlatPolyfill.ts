// Based off of the polyfill on MDN
// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/flat#reduce_concat_isarray_recursivity
//
// Needed to provide support for Safari on iOS < 12

// ts-ignored because we can afford to encapsulate some messy logic behind the clean typings.

if (!Array.prototype['flat']) {
  Array.prototype['flat'] = function flat<A, D extends number = 1>(this: A, depth?: D | unknown): any[] {
    // @ts-ignore
    return (depth ?? 1) > 0
      ? // @ts-ignore
        Array.prototype.reduce.call(this, (acc, val): any[] => acc.concat(Array.isArray(val) ? flat.call(val, depth - 1) : val), [])
      : // If no depth is provided, or depth is 0, just return a copy of
        // the array. Spread is supported in all major browsers (iOS 8+)
        // @ts-ignore
        [...this];
  };
}
