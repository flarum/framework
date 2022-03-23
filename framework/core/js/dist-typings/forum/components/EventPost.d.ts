/**
 * The `EventPost` component displays a post which indicating a discussion
 * event, like a discussion being renamed or stickied. Subclasses must implement
 * the `icon` and `description` methods.
 *
 * ### Attrs
 *
 * - All of the attrs for `Post`
 *
 * @abstract
 */
export default class EventPost extends Post<import("./Post").IPostAttrs> {
    constructor();
    content(): any;
    /**
     * Get the name of the event icon.
     *
     * @return {string}
     */
    icon(): string;
    /**
     * Get the description text for the event.
     *
     * @param {Record<string, unknown>} data
     * @return {import('mithril').Children} The description to render in the DOM
     */
    description(data: Record<string, unknown>): import('mithril').Children;
    /**
     * Get the translation key for the description of the event.
     *
     * @return {string}
     */
    descriptionKey(): string;
    /**
     * Get the translation data for the description of the event.
     *
     * @return {Record<string, unknown>}
     */
    descriptionData(): Record<string, unknown>;
}
import Post from "./Post";
