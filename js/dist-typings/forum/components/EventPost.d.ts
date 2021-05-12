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
export default class EventPost extends Post {
    /**
     * Get the name of the event icon.
     *
     * @return {String}
     */
    icon(): string;
    /**
     * Get the description text for the event.
     *
     * @param {Object} data
     * @return {String|Object} The description to render in the DOM
     */
    description(data: Object): string | Object;
    /**
     * Get the translation key for the description of the event.
     *
     * @return {String}
     */
    descriptionKey(): string;
    /**
     * Get the translation data for the description of the event.
     *
     * @return {Object}
     */
    descriptionData(): Object;
}
import Post from "./Post";
