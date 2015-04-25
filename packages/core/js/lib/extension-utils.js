export function extend(object, func, extension) {
  var oldFunc = object[func];
  object[func] = function() {
    var value = oldFunc.apply(this, arguments);
    var args = [].slice.apply(arguments);
    return extension.apply(this, [value].concat(args));
  }
};
