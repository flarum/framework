// Based of the CC-0 polyfill at https://github.com/jonathantneal/array-flat-polyfill
//
// Needed to provide support for Safari on iOS < 12

if (!Array.prototype['flat']) {
  Array.prototype['flat'] = function flat(depth?: number) {
    const realDepth = isNaN(depth as any) ? 1 : depth;

    return realDepth
      ? Array.prototype.reduce.call(
          this,
          function (acc, cur) {
            if (Array.isArray(cur)) {
              acc.push.apply(acc, flat.call(cur, realDepth - 1));
            } else {
              acc.push(cur);
            }

            return acc;
          },
          []
        )
      : Array.prototype.slice.call(this);
  };
}
