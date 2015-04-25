export default function mixin(Parent, ...mixins) {
  class Mixed extends Parent {}
  for (var i in mixins) {
    var keys = Object.keys(mixins[i]);
    for (var j in keys) {
      var prop = keys[j];
      Mixed.prototype[prop] = mixins[i][prop];
    }
  }
  return Mixed;
}
