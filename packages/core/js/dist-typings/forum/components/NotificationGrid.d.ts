/**
 * The `NotificationGrid` component displays a table of notification types and
 * methods, allowing the user to toggle each combination.
 *
 * ### Attrs
 *
 * - `user`
 */
export default class NotificationGrid extends Component<import("../../common/Component").ComponentAttrs> {
    constructor();
    /**
     * Information about the available notification methods.
     *
     * @type {Array}
     */
    methods: any[] | undefined;
    /**
     * A map of which notification checkboxes are loading.
     *
     * @type {Object}
     */
    loading: Object | undefined;
    /**
     * Information about the available notification types.
     *
     * @type {Array}
     */
    types: any[] | undefined;
    /**
     * Toggle the state of the given preferences, based on the value of the first
     * one.
     *
     * @param {Array} keys
     */
    toggle(keys: any[]): void;
    /**
     * Toggle all notification types for the given method.
     *
     * @param {String} method
     */
    toggleMethod(method: string): void;
    /**
     * Toggle all notification methods for the given type.
     *
     * @param {String} type
     */
    toggleType(type: string): void;
    /**
     * Get the name of the preference key for the given notification type-method
     * combination.
     *
     * @param {String} type
     * @param {String} method
     * @return {String}
     */
    preferenceKey(type: string, method: string): string;
    /**
     * Build an item list for the notification methods to display in the grid.
     *
     * Each notification method is an object which has the following properties:
     *
     * - `name` The name of the notification method.
     * - `icon` The icon to display in the column header.
     * - `label` The label to display in the column header.
     *
     * @return {ItemList}
     */
    notificationMethods(): ItemList;
    /**
     * Build an item list for the notification types to display in the grid.
     *
     * Each notification type is an object which has the following properties:
     *
     * - `name` The name of the notification type.
     * - `icon` The icon to display in the notification grid row.
     * - `label` The label to display in the notification grid row.
     *
     * @return {ItemList}
     */
    notificationTypes(): ItemList;
}
import Component from "../../common/Component";
import ItemList from "../../common/utils/ItemList";
