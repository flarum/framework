/**
 * The `Notification` component abstract displays a single notification.
 * Subclasses should implement the `icon`, `href`, and `content` methods.
 *
 * ### Attrs
 *
 * - `notification`
 *
 * @abstract
 */
export default class Notification extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    /**
     * Get the name of the icon that should be displayed in the notification.
     *
     * @return {string}
     * @abstract
     */
    icon(): string;
    /**
     * Get the URL that the notification should link to.
     *
     * @return {string}
     * @abstract
     */
    href(): string;
    /**
     * Get the content of the notification.
     *
     * @return {import('mithril').Children}
     * @abstract
     */
    content(): import('mithril').Children;
    /**
     * Get the excerpt of the notification.
     *
     * @return {import('mithril').Children}
     * @abstract
     */
    excerpt(): import('mithril').Children;
    /**
     * Mark the notification as read.
     */
    markAsRead(): void;
}
import Component from "../../common/Component";
