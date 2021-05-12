declare class Item {
    content: any;
    priority: number;
    key?: number;
    constructor(content: any, priority?: number);
}
/**
 * The `ItemList` class collects items and then arranges them into an array
 * by priority.
 */
export default class ItemList {
    /**
     * The items in the list
     */
    items: {
        [key: string]: Item;
    };
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
    get(key: string): any;
    /**
     * Add an item to the list.
     *
     * @param key A unique key for the item.
     * @param content The item's content.
     * @param [priority] The priority of the item. Items with a higher
     *     priority will be positioned before items with a lower priority.
     */
    add(key: string, content: any, priority?: number): this;
    /**
     * Replace an item in the list, only if it is already present.
     */
    replace(key: string, content?: any, priority?: number): this;
    /**
     * Remove an item from the list.
     */
    remove(key: string): this;
    /**
     * Merge another list's items into this one.
     */
    merge(items: this): this;
    /**
     * Convert the list into an array of item content arranged by priority. Each
     * item's content will be assigned an `itemName` property equal to the item's
     * unique key.
     */
    toArray(): any[];
}
export {};
