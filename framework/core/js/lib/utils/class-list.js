export default function classList(classes) {
  var classNames = [];
  for (var i in classes) {
    var value = classes[i];
    if (value === true) {
      classNames.push(i);
    } else if (value) {
      classNames.push(value);
    }
  }
  return classNames.join(' ');
}
