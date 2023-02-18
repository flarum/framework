export default ComposerState;
declare class ComposerState {
    /**
     * The composer's current position.
     *
     * @type {ComposerState.Position}
     */
    position: {
        HIDDEN: string;
        NORMAL: string;
        MINIMIZED: string;
        FULLSCREEN: string;
    };
    /**
     * The composer's intended height, which can be modified by the user
     * (by dragging the composer handle).
     *
     * @type {number}
     */
    height: number;
    /**
     * The dynamic component being shown inside the composer.
     *
     * @type {Object}
     */
    body: Object;
    /**
     * A reference to the text editor that allows text manipulation.
     *
     * @type {import('../../common/utils/EditorDriverInterface')|null}
     */
    editor: typeof import("../../common/utils/EditorDriverInterface") | null;
    /**
     * Load a content component into the composer.
     *
     * @param {typeof import('../components/ComposerBody').default} componentClass
     */
    load(componentClass: typeof import('../components/ComposerBody').default, attrs: any): void;
    /**
     * Clear the composer's content component.
     */
    clear(): void;
    onExit: {
        callback: () => boolean;
        message: string;
    } | null | undefined;
    fields: {
        content: Stream<string>;
    } | undefined;
    /**
     * Show the composer.
     */
    show(): void;
    /**
     * Close the composer.
     */
    hide(): void;
    /**
     * Confirm with the user so they don't lose their content, then close the
     * composer.
     */
    close(): void;
    /**
     * Minimize the composer. Has no effect if the composer is hidden.
     */
    minimize(): void;
    /**
     * Take the composer into fullscreen mode. Has no effect if the composer is
     * hidden.
     */
    fullScreen(): void;
    /**
     * Exit fullscreen mode.
     */
    exitFullScreen(): void;
    /**
     * Determine whether the body matches the given component class and data.
     *
     * @param {object} type The component class to check against. Subclasses are accepted as well.
     * @param {object} data
     * @return {boolean}
     */
    bodyMatches(type: object, data?: object): boolean;
    /**
     * Determine whether or not the Composer is visible.
     *
     * True when the composer is displayed on the screen and has a body component.
     * It could be open in "normal" or full-screen mode, or even minimized.
     *
     * @returns {boolean}
     */
    isVisible(): boolean;
    /**
     * Determine whether or not the Composer is covering the screen.
     *
     * This will be true if the Composer is in full-screen mode on desktop,
     * or if we are on a mobile device, where we always consider the composer as full-screen..
     *
     * @return {boolean}
     */
    isFullScreen(): boolean;
    /**
     * Check whether or not the user is currently composing a reply to a
     * discussion.
     *
     * @param {import('../../common/models/Discussion').default} discussion
     * @return {boolean}
     */
    composingReplyTo(discussion: import('../../common/models/Discussion').default): boolean;
    /**
     * Confirm with the user that they want to close the composer and lose their
     * content.
     *
     * @return {boolean} Whether or not the exit was cancelled.
     */
    preventExit(): boolean;
    /**
     * Configure when / what to ask the user before closing the composer.
     *
     * The provided callback will be used to determine whether asking for
     * confirmation is necessary. If the callback returns true at the time of
     * closing, the provided text will be shown in a standard confirmation dialog.
     *
     * @param {() => boolean} callback
     * @param {string} message
     */
    preventClosingWhen(callback: () => boolean, message: string): void;
    /**
     * Minimum height of the Composer.
     * @returns {number}
     */
    minimumHeight(): number;
    /**
     * Maxmimum height of the Composer.
     * @returns {number}
     */
    maximumHeight(): number;
    /**
     * Computed the composer's current height, based on the intended height, and
     * the composer's current state. This will be applied to the composer
     * content's DOM element.
     * @returns {number | string}
     */
    computedHeight(): number | string;
}
declare namespace ComposerState {
    namespace Position {
        const HIDDEN: string;
        const NORMAL: string;
        const MINIMIZED: string;
        const FULLSCREEN: string;
    }
}
import Stream from "../../common/utils/Stream";
