export default class Outer {
  static filter() {
    return import('./Filter');
  }

  static picker() {
    return import('./Picker');
  }
}
