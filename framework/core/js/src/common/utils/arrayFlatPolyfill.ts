// Based off of the polyfill on MDN
// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/flat#reduce_concat_isarray_recursivity
//
// Needed to provide support for Safari on iOS < 12

if (!Array.prototype['flat']) {
  Array.prototype['flat'] = function flat(this: any[], depth: number = 1): any[] {
    return depth > 0
      ? Array.prototype.reduce.call(this, (acc, val): any[] => acc.concat(Array.isArray(val) ? flat.call(val, depth - 1) : val), [])
      : // If no depth is provided, or depth is 0, just return a copy of
        // the array. Spread is supported in all major browsers (iOS 8+)
        [...this];
  };
}
