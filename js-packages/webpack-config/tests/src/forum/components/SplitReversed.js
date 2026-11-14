import openPicker from './openPicker';

export default class SplitReversed {
  static picker() {
    return openPicker();
  }

  static filter() {
    return import('./Filter');
  }
}
