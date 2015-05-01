export function extend(object, func, extension) {
  var oldFunc = object[func];
  object[func] = function() {
    var args = [].slice.apply(arguments);
    var value = oldFunc.apply(this, args);
    return extension.apply(this, [value].concat(args));
  }
};

export function override(object, func, override) {
  var parent = object[func];
  object[func] = function() {
    var args = [].slice.apply(arguments);
    return override.apply(this, [parent].concat(args));
  }
}
