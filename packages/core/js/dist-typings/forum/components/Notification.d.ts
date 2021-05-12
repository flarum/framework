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
export default class Notification extends Component<import("../../common/Component").ComponentAttrs> {
    constructor();
    /**
     * Get the name of the icon that should be displayed in the notification.
     *
     * @return {String}
     * @abstract
     */
    icon(): string;
    /**
     * Get the URL that the notification should link to.
     *
     * @return {String}
     * @abstract
     */
    href(): string;
    /**
     * Get the content of the notification.
     *
     * @return {VirtualElement}
     * @abstract
     */
    content(): any;
    /**
     * Get the excerpt of the notification.
     *
     * @return {VirtualElement}
     * @abstract
     */
    excerpt(): any;
    /**
     * Mark the notification as read.
     */
    markAsRead(): void;
}
import Component from "../../common/Component";
