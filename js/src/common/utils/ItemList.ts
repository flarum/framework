class Item<T> {
  content:
    | T
    | (T & {
        /**
         * Set when calling `.toArray()`
         */
        itemName: string;
      });
  priority: number;

  /**
   * Set when calling `.toArray()`
   */
  key?: number;

  constructor(content: any, priority: number = 0) {
    this.content = content;
    this.priority = priority;
  }
}

/**
 * The `ItemList` class collects items and then arranges them into an array
 * by priority.
 */
export default class ItemList<T> {
  /**
   * The items in the list
   */
  items: { [key: string]: Item<T> } = {};

  /**
   * Check whether the list is empty.
   */
  isEmpty(): boolean {
    return !!Object.keys(this.items).length;
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
  get(key: string): T {
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
  add(key: string, content: T, priority: number = 0): this {
    this.items[key] = new Item(content, priority);

    return this;
  }

  /**
   * Replace an item in the list, only if it is already present.
   *
   * If `content` or `priority` are `null`, these values will not be replaced.
   */
  replace(key: string, content: T | null = null, priority: number | null = null): this {
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
   *
   * The list passed to this function will overwrite items whichalready exist
   * with the same key.
   */
  merge<K>(otherList: ItemList<K>): ItemList<T | K> {
    Object.keys(otherList.items).forEach((key) => {
      const val = otherList.items[key];

      if (val instanceof Item) {
        (this as ItemList<T | K>).items[key] = otherList.items[key];
      }
    });

    return this;
  }

  /**
   * Convert the list into an array of item content arranged by priority. Each
   * item's content will be assigned an `itemName` property equal to the item's
   * unique key.
   */
  toArray(): T[] {
    const items: Item<T>[] = [];

    Object.keys(this.items).forEach((key) => {
      const val = this.items[key];

      if (val instanceof Item) {
        val.content = Object(val.content);

        (val.content as T & { itemName: string }).itemName = key;
        val.key = items.length;

        items.push(val);
      }
    });

    return items
      .sort((a, b) => {
        if (a.priority === b.priority) {
          return a.key! - b.key!;
        }

        return b.priority - a.priority;
      })
      .map((item) => item.content);
  }
}
