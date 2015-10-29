class Item {
  constructor(content, priority) {
    this.content = content;
    this.priority = priority;
  }
}

/**
 * The `ItemList` class collects items and then arranges them into an array
 * by priority.
 */
export default class ItemList {
  constructor() {
    this.list = {};
  }

  /**
   * Get an item.
   *
   * @param {String} key
   * @return {Item}
   * @public
   */
  get(key) {
    return this.list[key];
  }

  /**
   * Add an item to the list.
   *
   * @param {String} key A unique key for the item.
   * @param {*} content The item's content.
   * @param {Integer} [priority] The priority of the item. Items with a higher
   *     priority will be positioned before items with a lower priority.
   * @public
   */
  add(key, content, priority = 0) {
    this.list[key] = new Item(content, priority);
  }

  /**
   * Replace an item in the list, only if it is already present.
   *
   * @param {String} key
   * @param {*} [content]
   * @param {Integer} [priority]
   * @public
   */
  replace(key, content = null, priority = null) {
    if (this.list[key]) {
      if (content !== null) {
        this.list[key].content = content;
      }

      if (priority !== null) {
        this.list[key].priority = priority;
      }
    }
  }

  /**
   * Remove an item from the list.
   *
   * @param {String} key
   * @public
   */
  remove(key) {
    delete this.list[key];
  }

  /**
   * Merge another list's items into this one.
   *
   * @param {ItemList} items
   * @public
   */
  merge(items) {
    for (const i in items.list) {
      if (items.list.hasOwnProperty(i) && items.list[i] instanceof Item) {
        this.list[i] = items.list[i];
      }
    }
  }

  /**
   * Convert the list into an array of item content arranged by priority. Each
   * item's content will be assigned an `itemName` property equal to the item's
   * unique key.
   *
   * @return {Array}
   * @public
   */
  toArray() {
    const items = [];

    for (const i in this.list) {
      if (this.list.hasOwnProperty(i) && this.list[i] instanceof Item) {
        this.list[i].content = Object(this.list[i].content);

        this.list[i].content.itemName = i;
        items.push(this.list[i]);
        this.list[i].key = items.length;
      }
    }

    return items.sort((a, b) => {
      if (a.priority === b.priority) {
        return a.key - b.key;
      } else if (a.priority > b.priority) {
        return -1;
      }
      return 1;
    }).map(item => item.content);
  }
}

