import subclassOf from '../../common/utils/subclassOf';

export default class PageState {
  public type: Function | null;
  public data: {
    routeName?: string | null;
  } & Record<string, any>;

  constructor(type: Function | null, data: any = {}) {
    this.type = type;
    this.data = data;
  }

  /**
   * Determine whether the page matches the given class and data.
   *
   * @param {object} type The page class to check against. Subclasses are accepted as well.
   * @param {Record<string, unknown>} data
   * @return {boolean}
   */
  matches(type: Function, data: any = {}) {
    // Fail early when the page is of a different type
    if (!subclassOf(this.type, type)) return false;

    // Now that the type is known to be correct, we loop through the provided
    // data to see whether it matches the data in our state.
    return Object.keys(data).every((key) => this.data[key] === data[key]);
  }

  get(key: string): any {
    return this.data[key];
  }

  set(key: string, value: any) {
    this.data[key] = value;
  }
}
