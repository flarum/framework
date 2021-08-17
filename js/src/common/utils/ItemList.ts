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
   * The index of this item in the latest generated array.
   *
   * Set when calling `.toArray()`.
   *
   * **NOTE:** If you modify the item list after `.toArray()` is called,
   * this value may not be correct.
   */
  key?: number;

  constructor(content: T, priority: number) {
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
   * The items in the list.
   */
  protected _items: Record<string, Item<T>> = {};

  /**
   * A read-only copy of items in the list.
   *
   * We don't allow adding new items to the ItemList via setting new properties,
   * nor do we allow modifying existing items directly.
   *
   * Attempting to write to this **will** throw an error.
   *
   * @deprecated Use `ItemList.toObject()` instead.
   */
  get items(): Readonly<Record<string, Item<T>>> {
    return this.toObject();
  }

  /**
   * Check whether the list is empty.
   */
  isEmpty(): boolean {
    return !!Object.keys(this._items).length;
  }

  /**
   * Check whether an item is present in the list.
   */
  has(key: string): boolean {
    return Object.keys(this._items).includes(key);
  }

  /**
   * Get the content of an item.
   */
  get(key: string): T {
    return this._items[key].content;
  }

  /**
   * Add an item to the list.
   *
   * @param key A unique key for the item.
   * @param content The item's content.
   * @param priority The priority of the item. Items with a higher priority
   * will be positioned before items with a lower priority.
   */
  add(key: string, content: T, priority: number = 0): this {
    this._items[key] = new Item(content, priority);

    return this;
  }

  // TODO: [Flarum 2.0] Remove deprecated `.replace()` syntax.

  //? This is the deprecated syntax.
  /**
   * Replace an item and/or priority in the list, only if it is already present.
   *
   * If `content` or `priority` are `null`, these values will not be replaced.
   *
   * If the provided `key` is not present, nothing will happen.
   *
   * @deprecated Please use the `changePriority` method to set the priority for
   * an item. This will be removed in Flarum 2.0.
   *
   * @param key The key of the item in the list
   * @param content The item's new content
   * @param priority The item's new priority
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
  replace(key: string, content?: T | null, priority?: number): this;

  //? This is the new syntax.
  /**
   * Replaces an item's content, if the provided item key exists.
   *
   * If the provided `key` is not present, nothing will happen.
   *
   * @param key The key of the item in the list
   * @param content The item's new content
   *
   * @example <caption>Replace item content.</caption>
   * items.replace('myItem', <p>My new value.</p>);
   *
   * @example <caption>Replace item content and priority.</caption>
   *          items
   *            .replace('myItem', <p>My new value.</p>)
   *            .changePriority('myItem', 10);
   */
  replace(key: string, content: T): this;

  replace(key: string, content: T | null = null, priority: number | null = null): this {
    if (!this.has(key)) return this;

    if (content !== null) {
      this._items[key].content = content;
    }

    if (priority !== null) {
      this._items[key].priority = priority;
    }

    return this;
  }

  /**
   * Replaces an item's priority, if the provided item key exists.
   *
   * If the provided `key` is not present, nothing will happen.
   *
   * @param key The key of the item in the list
   * @param priority The item's new priority
   *
   * @example <caption>Replace item priority.</caption>
   * items.changePriority('myItem', 10);
   *
   * @example <caption>Replace item priority and content.</caption>
   *          items
   *            .changePriority('myItem', 10)
   *            .replace('myItem', <p>My new value.</p>);
   */
  changePriority(key: string, priority: number): this {
    if (this.has(key)) {
      this._items[key].priority = priority;
    }

    return this;
  }

  /**
   * Remove an item from the list.
   */
  remove(key: string): this {
    delete this._items[key];

    return this;
  }

  /**
   * Merge another list's items into this one.
   *
   * The list passed to this function will overwrite items which already exist
   * with the same key.
   */
  merge<K>(otherList: ItemList<K>): ItemList<T | K> {
    Object.keys(otherList._items).forEach((key) => {
      const val = otherList._items[key];

      if (val instanceof Item) {
        (this as ItemList<T | K>)._items[key] = otherList._items[key];
      }
    });

    return this;
  }

  /**
   * Convert the list into an array of item content arranged by priority.
   *
   * This **does not** preserve the original types of primitives and proxies
   * all content values to make `itemName` accessible on them.
   *
   * **NOTE:** Modifying any objects in the final array may also update the
   * content of the original ItemList.
   *
   * @see https://github.com/flarum/core/issues/3030
   */
  toArray(objectifyContent?: true): (T & { itemName: string })[];
  /**
   * Convert the list into an array of item content arranged by priority.
   *
   * Content values that are already objects will be proxied and have
   * `itemName` accessible on them.
   *
   * **NOTE:** Modifying any objects in the final array may also update the
   * content of the original ItemList.
   */
  toArray(objectifyContent: false): (T extends Object ? T & { itemName: string } : T)[];

  /**
   * Convert the list into an array of item content arranged by priority.
   *
   * Each item's content will be assigned an `itemName` property equal to the
   * item's unique key.
   *
   * **NOTE:** If your ItemList holds primitive types (such as numbers, booleans
   * or strings), these will be converted to their object counterparts if you do
   * not provide `false` to this function.
   *
   * **NOTE:** Modifying any objects in the final array may also update the
   * content of the original ItemList.
   *
   * @param objectifyContent Converts item content to objects and sets the
   * `itemName` property on them.
   */
  toArray(objectifyContent: boolean = true): T[] | (T & { itemName: string })[] {
    const items: Item<T>[] = Object.keys(this._items).map((key, i) => {
      const item = this._items[key];
      item.key = i;

      if (objectifyContent || typeof item.content === 'object') {
        // Convert content to object, then proxy it
        return {
          ...item,
          content: this.createItemContentProxy(typeof item.content === 'object' ? item.content : Object(item.content), key),
        };
      } else {
        // ...otherwise just return a clone of the item.
        return { ...item };
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

  private createItemContentProxy<C extends Object>(content: C, key: string) {
    return new Proxy(content, {
      get(target, property, receiver) {
        if (property === 'itemName') return key;

        return Reflect.get(target, property, receiver);
      },
    });
  }

  /**
   * A read-only map of all keys to their respective items.
   *
   * We don't allow adding new items to the ItemList via setting new properties,
   * nor do we allow modifying existing items directly. You should use the
   * `.add()` and `.replace()` methods respectively instead.
   *
   * Attempting to write to this **will** throw an error.
   *
   * @example
   * const items = new ItemList();
   * items.add('myKey', 'My cool value', 10);
   * items.toObject();
   * // { myKey: { content: 'My cool value', priority: 10 } }
   */
  toObject(): Readonly<Record<string, Item<T>>> {
    return new Proxy(this._items, {
      get(target, property, receiver) {
        if (typeof property === 'string' && target[property] instanceof Item) {
          return new Proxy(target[property], {
            set() {
              // Disallow directly modifying Item.content, Item.priority, Item.key.
              throw Error(
                'Modifying Items in an ItemList directly is not allowed. You must use `ItemList.replace()` or `ItemList.changePriority()` instead.'
              );
            },
          });
        }

        return Reflect.get(target, property, receiver);
      },
      set() {
        // Disallow overwriting items
        throw Error('Setting values of `ItemList.items` is not allowed. You must use `ItemList.add()` instead.');
      },
    });
  }
}

window.ItemList = ItemList;
