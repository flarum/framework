/**
 * The `Modal` component displays a modal dialog, wrapped in a form. Subclasses
 * should implement the `className`, `title`, and `content` methods.
 *
 * @abstract
 */
export default class Modal extends Component<import("../Component").ComponentAttrs> {
    /**
     * Determine whether or not the modal should be dismissible via an 'x' button.
     */
    static isDismissible: boolean;
    constructor();
    /**
     * Attributes for an alert component to show below the header.
     *
     * @type {object}
     */
    alertAttrs: object;
    /**
     * Get the class name to apply to the modal.
     *
     * @return {String}
     * @abstract
     */
    className(): string;
    /**
     * Get the title of the modal dialog.
     *
     * @return {String}
     * @abstract
     */
    title(): string;
    /**
     * Get the content of the modal.
     *
     * @return {VirtualElement}
     * @abstract
     */
    content(): any;
    /**
     * Handle the modal form's submit event.
     *
     * @param {Event} e
     */
    onsubmit(): void;
    /**
     * Focus on the first input when the modal is ready to be used.
     */
    onready(): void;
    /**
     * Hide the modal.
     */
    hide(): void;
    /**
     * Stop loading.
     */
    loaded(): void;
    loading: boolean | undefined;
    /**
     * Show an alert describing an error returned from the API, and give focus to
     * the first relevant field.
     *
     * @param {RequestError} error
     */
    onerror(error: any): void;
}
import Component from "../Component";
