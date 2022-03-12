/**
 * The `NotificationGrid` component displays a table of notification types and
 * methods, allowing the user to toggle each combination.
 *
 * ### Attrs
 *
 * - `user`
 */
export default class NotificationGrid extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    oninit(vnode: any): void;
    /**
     * Information about the available notification methods.
     *
     * @type {({ name: string, icon: string, label: import('mithril').Children })[]}
     */
    methods: {
        name: string;
        icon: string;
        label: import('mithril').Children;
    }[] | undefined;
    /**
     * A map of which notification checkboxes are loading.
     *
     * @type {Record<string, boolean>}
     */
    loading: Record<string, boolean> | undefined;
    /**
     * Information about the available notification types.
     *
     * @type {({ name: string, icon: string, label: import('mithril').Children })[]}
     */
    types: {
        name: string;
        icon: string;
        label: import('mithril').Children;
    }[] | undefined;
    view(): JSX.Element;
    oncreate(vnode: any): void;
    /**
     * Toggle the state of the given preferences, based on the value of the first
     * one.
     *
     * @param {string[]} keys
     */
    toggle(keys: string[]): void;
    /**
     * Toggle all notification types for the given method.
     *
     * @param {string} method
     */
    toggleMethod(method: string): void;
    /**
     * Toggle all notification methods for the given type.
     *
     * @param {string} type
     */
    toggleType(type: string): void;
    /**
     * Get the name of the preference key for the given notification type-method
     * combination.
     *
     * @param {string} type
     * @param {string} method
     * @return {string}
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
     * @return {ItemList<{ name: string, icon: string, label: import('mithril').Children }>}
     */
    notificationMethods(): ItemList<{
        name: string;
        icon: string;
        label: import('mithril').Children;
    }>;
    /**
     * Build an item list for the notification types to display in the grid.
     *
     * Each notification type is an object which has the following properties:
     *
     * - `name` The name of the notification type.
     * - `icon` The icon to display in the notification grid row.
     * - `label` The label to display in the notification grid row.
     *
     * @return {ItemList<{ name: string, icon: string, label: import('mithril').Children}>}
     */
    notificationTypes(): ItemList<{
        name: string;
        icon: string;
        label: import('mithril').Children;
    }>;
}
import Component from "../../common/Component";
import ItemList from "../../common/utils/ItemList";
import icon from "../../common/helpers/icon";
