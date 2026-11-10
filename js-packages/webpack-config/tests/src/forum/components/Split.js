import openFilter from './openFilter';

export default class Split {
  static picker() {
    return import('./Picker');
  }

  static filter() {
    return openFilter();
  }
}
