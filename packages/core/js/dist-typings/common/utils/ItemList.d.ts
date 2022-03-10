export interface IItemObject<T> {
    content: T;
    itemName: string;
    priority: number;
}
declare class Item<T> {
    content: T;
    priority: number;
    constructor(content: T, priority: number);
}
/**
 * The `ItemList` class collects items and then arranges them into an array
 * by priority.
 */
export default class ItemList<T> {
    /**
     * The items in the list.
     */
    protected _items: Record<string, Item<T>>;
    /**
     * A **read-only copy** of items in the list.
     *
     * We don't allow adding new items to the ItemList via setting new properties,
     * nor do we allow modifying existing items directly.
     *
     * @deprecated Use {@link ItemList.toObject} instead.
     */
    get items(): DeepReadonly<Record<string, Item<T>>>;
    /**
     * Check whether the list is empty.
     */
    isEmpty(): boolean;
    /**
     * Check whether an item is present in the list.
     */
    has(key: string): boolean;
    /**
     * Get the content of an item.
     */
    get(key: string): T;
    /**
     * Get the priority of an item.
     */
    getPriority(key: string): number;
    /**
     * Add an item to the list.
     *
     * @param key A unique key for the item.
     * @param content The item's content.
     * @param priority The priority of the item. Items with a higher priority
     * will be positioned before items with a lower priority.
     */
    add(key: string, content: T, priority?: number): this;
    /**
     * Replace an item and/or priority in the list, only if it is already present.
     *
     * If `content` or `priority` are `null`, these values will not be replaced.
     *
     * If the provided `key` is not present, nothing will happen.
     *
     * @deprecated Please use the {@link ItemList.setContent} and {@link ItemList.setPriority}
     * methods to replace items and their priorities. This method will be removed in Flarum 2.0.
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
    replace(key: string, content?: T | null, priority?: number | null): this;
    /**
     * Replaces an item's content, if the provided item key exists.
     *
     * If the provided `key` is not present, nothing will happen.
     *
     * @param key The key of the item in the list
     * @param content The item's new content
     *
     * @example <caption>Replace item content.</caption>
     * items.setContent('myItem', <p>My new value.</p>);
     *
     * @example <caption>Replace item content and priority.</caption>
     *          items
     *            .setContent('myItem', <p>My new value.</p>)
     *            .setPriority('myItem', 10);
     *
     * @throws If the provided `key` is not present in the ItemList.
     */
    setContent(key: string, content: T): this;
    /**
     * Replaces an item's priority, if the provided item key exists.
     *
     * If the provided `key` is not present, nothing will happen.
     *
     * @param key The key of the item in the list
     * @param priority The item's new priority
     *
     * @example <caption>Replace item priority.</caption>
     * items.setPriority('myItem', 10);
     *
     * @example <caption>Replace item priority and content.</caption>
     *          items
     *            .setPriority('myItem', 10)
     *            .setContent('myItem', <p>My new value.</p>);
     *
     * @throws If the provided `key` is not present in the ItemList.
     */
    setPriority(key: string, priority: number): this;
    /**
     * Remove an item from the list.
     */
    remove(key: string): this;
    /**
     * Merge another list's items into this one.
     *
     * The list passed to this function will overwrite items which already exist
     * with the same key.
     */
    merge(otherList: ItemList<T>): ItemList<T>;
    /**
     * Convert the list into an array of item content arranged by priority.
     *
     * This **does not** preserve the original types of primitives and proxies
     * all content values to make `itemName` accessible on them.
     *
     * **NOTE:** If your ItemList holds primitive types (such as numbers, booleans
     * or strings), these will be converted to their object counterparts if you do
     * not provide `true` to this function.
     *
     * **NOTE:** Modifying any objects in the final array may also update the
     * content of the original ItemList.
     *
     * @param keepPrimitives Converts item content to objects and sets the
     * `itemName` property on them.
     *
     * @see https://github.com/flarum/core/issues/3030
     */
    toArray(keepPrimitives?: false): (T & {
        itemName: string;
    })[];
    /**
     * Convert the list into an array of item content arranged by priority.
     *
     * Content values that are already objects will be proxied and have
     * `itemName` accessible on them. Primitive values will not have the
     * `itemName` property accessible.
     *
     * **NOTE:** Modifying any objects in the final array may also update the
     * content of the original ItemList.
     *
     * @param keepPrimitives Converts item content to objects and sets the
     * `itemName` property on them.
     */
    toArray(keepPrimitives: true): (T extends object ? T & Readonly<{
        itemName: string;
    }> : T)[];
    /**
     * A read-only map of all keys to their respective items in no particular order.
     *
     * We don't allow adding new items to the ItemList via setting new properties,
     * nor do we allow modifying existing items directly. You should use the
     * {@link ItemList.add}, {@link ItemList.setContent} and
     * {@link ItemList.setPriority} methods instead.
     *
     * To match the old behaviour of the `ItemList.items` property, call
     * `Object.values(ItemList.toObject())`.
     *
     * @example
     * const items = new ItemList();
     * items.add('b', 'My cool value', 20);
     * items.add('a', 'My value', 10);
     * items.toObject();
     * // {
     * //   a: { content: 'My value', priority: 10, itemName: 'a' },
     * //   b: { content: 'My cool value', priority: 20, itemName: 'b' },
     * // }
     */
    toObject(): DeepReadonly<Record<string, IItemObject<T>>>;
    /**
     * Proxies an item's content, adding the `itemName` readonly property to it.
     *
     * @example
     * createItemContentProxy({ foo: 'bar' }, 'myItem');
     * // { foo: 'bar', itemName: 'myItem' }
     *
     * @param content The item's content (objects only)
     * @param key The item's key
     * @return Proxied content
     *
     * @internal
     */
    private createItemContentProxy;
}
export {};
