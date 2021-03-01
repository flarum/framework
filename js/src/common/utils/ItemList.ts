class Item {
  content: any;
  priority: number;
  key?: number;

  constructor(content: any, priority?: number) {
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
   * The items in the list
   */
  items: { [key: string]: Item } = {};

  /**
   * Check whether the list is empty.
   */
  isEmpty(): boolean {
    for (const i in this.items) {
      if (this.items.hasOwnProperty(i)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Check whether an item is present in the list.
   */
  has(key: string): boolean {
    return !!this.items[key];
  }

  /**
   * Get the content of an item.
   */
  get(key: string): any {
    return this.items[key].content;
  }

  /**
   * Add an item to the list.
   *
   * @param key A unique key for the item.
   * @param content The item's content.
   * @param [priority] The priority of the item. Items with a higher
   *     priority will be positioned before items with a lower priority.
   */
  add(key: string, content: any, priority: number = 0): this {
    this.items[key] = new Item(content, priority);

    return this;
  }

  /**
   * Replace an item in the list, only if it is already present.
   */
  replace(key: string, content: any = null, priority: number = null): this {
    if (this.items[key]) {
      if (content !== null) {
        this.items[key].content = content;
      }

      if (priority !== null) {
        this.items[key].priority = priority;
      }
    }

    return this;
  }

  /**
   * Remove an item from the list.
   */
  remove(key: string): this {
    delete this.items[key];

    return this;
  }

  /**
   * Merge another list's items into this one.
   */
  merge(items: this): this {
    for (const i in items.items) {
      if (items.items.hasOwnProperty(i) && items.items[i] instanceof Item) {
        this.items[i] = items.items[i];
      }
    }

    return this;
  }

  /**
   * Convert the list into an array of item content arranged by priority. Each
   * item's content will be assigned an `itemName` property equal to the item's
   * unique key.
   */
  toArray(): any[] {
    const items: Item[] = [];

    for (const i in this.items) {
      if (this.items.hasOwnProperty(i) && this.items[i] instanceof Item) {
        this.items[i].content = Object(this.items[i].content);

        this.items[i].content.itemName = i;
        items.push(this.items[i]);
        this.items[i].key = items.length;
      }
    }

    return items
      .sort((a, b) => {
        if (a.priority === b.priority) {
          return a.key - b.key;
        } else if (a.priority > b.priority) {
          return -1;
        }
        return 1;
      })
      .map((item) => item.content);
  }
}
