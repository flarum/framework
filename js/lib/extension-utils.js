export function extend(object, func, extension) {
  var original = object[func];
  object[func] = function() {
    var args = [].slice.apply(arguments);
    var value = original.apply(this, args);
    extension.apply(this, [value].concat(args));
    return value;
  }
};

export function override(object, func, override) {
  var original = object[func];
  object[func] = function() {
    var args = [].slice.apply(arguments);
    return override.apply(this, [original.bind(this)].concat(args));
  }
};
