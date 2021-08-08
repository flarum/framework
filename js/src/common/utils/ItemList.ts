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

  constructor(content: T, priority: number) {
    this.content = content;
    this.priority = priority;
  }
}

/**
 * Options for new `replace` syntax.
 *
 * Removes the need for passing `null` to parameters.
 */
interface IItemListReplaceOptions<T> {
  _useNewSyntax: true;
  content?: T;
  priority?: number;
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
   * Only replaces values which are present in the object.
   *
   * The `_useNewSyntax` object property will be required until Flarum 2.0.
   *
   * @example <caption>Replace priority and not content.</caption>
   * items.replace('myItem', { _useNewSyntax: true, priority: 10 });
   *
   * @example <caption>Replace content and not priority.</caption>
   * items.replace('myItem', { _useNewSyntax: true, content: <p>My new value.</p> });
   *
   * @example <caption>Replace content and priority.</caption>
   * items.replace('myItem', { _useNewSyntax: true, content: <p>My new value.</p>, priority: 10 });
   */
  replace(key: string, options: IItemListReplaceOptions<T>): this;

  // TODO: [Flarum 2.0] Remove deprecated `.replace()` syntax.
  /**
   * Replace an item in the list, only if it is already present.
   *
   * If `content` or `priority` are `null`, these values will not be replaced.
   *
   * @deprecated Please use the new object-based syntax. This syntax will be removed in Flarum 2.0.
   *
   * @example <caption>Replace priority and not content.</caption>
   * items.replace('myItem', null, 10);
   *
   * @example <caption>Replace content and not priority.</caption>
   * items.replace('myItem', <p>My new value.</p>);
   *
   * @example <caption>Replace content and priority.</caption>
   * items.replace('myItem', <p>My new value.</p>, 10);
   */
  replace(key: string, contentOrOptions?: T | null, priority?: number | null): this;

  replace(key: string, contentOrOptions: T | null | IItemListReplaceOptions<T> = null, priority: number | null = null): this {
    if (this.items[key]) {
      if (contentOrOptions !== null) {
        // Being called with new object-based syntax.
        if ('_useNewSyntax' in contentOrOptions) {
          if (contentOrOptions.content !== undefined) this.items[key].content = contentOrOptions.content;
          if (contentOrOptions.priority !== undefined) this.items[key].priority = contentOrOptions.priority;

          return this;
        }

        this.items[key].content = contentOrOptions;
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
