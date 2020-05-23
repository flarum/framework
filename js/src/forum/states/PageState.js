import subclassOf from '../../common/utils/subclassOf';

export default class PageState {
  constructor(type, data = {}) {
    this.type = type;
    this.data = data;
  }

  subclassOf(B) {
    return subclassOf(this.type, B);
  }

  getData() {
    return this.data;
  }

  setData(data) {
    this.data = data;
  }
}
