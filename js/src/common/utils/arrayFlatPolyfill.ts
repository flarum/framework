// Based off of the CC-0 polyfill at https://github.com/jonathantneal/array-flat-polyfill
//
// Needed to provide support for Safari on iOS < 12

if (!Array.prototype['flat']) {
  Array.prototype['flat'] = function flat(this: any, depth?: number) {
    const realDepth = isNaN(depth as any) ? 1 : depth;

    return realDepth
      ? Array.prototype.reduce.call(
          this,
          function (acc, cur) {
            if (Array.isArray(cur)) {
              (acc as Array<any>).push.apply(acc, flat.call(cur, realDepth - 1));
            } else {
              (acc as Array<any>).push(cur);
            }

            return acc;
          },
          [] as Array<any>
        )
      : // If no depth is provided, or depth is 0, just return a copy of
        // the array. Spread is supported in all major browsers (iOS 8+)
        [...this];
  };
}
