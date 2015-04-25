export default function computed() {
  var args = [].slice.apply(arguments);
  var keys = args.slice(0, -1);
  var compute = args.slice(-1)[0];

  var values = {};
  var computed;
  return function() {
    var recompute = false;
    keys.forEach(function(key) {
      var value = typeof this[key] === 'function' ? this[key]() : this[key];
      if (values[key] !== value) {
        recompute = true;
        values[key] = value;
      }
    }.bind(this));
    if (recompute) {
      computed = compute.apply(this, keys.map((key) => values[key]));
    }
    return computed;
  }
};
