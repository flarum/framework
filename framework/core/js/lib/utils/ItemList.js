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
    this[key] = new Item(content, priority);
  }

  /**
   * Merge another list's items into this one.
   *
   * @param {ItemList} items
   * @public
   */
  merge(items) {
    for (const i in items) {
      if (items.hasOwnProperty(i) && items[i] instanceof Item) {
        this[i] = items[i];
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

    for (const i in this) {
      if (this.hasOwnProperty(i) && this[i] instanceof Item) {
        this[i].content = Object(this[i].content);

        this[i].content.itemName = i;
        items.push(this[i]);
        this[i].key = items.length;
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

