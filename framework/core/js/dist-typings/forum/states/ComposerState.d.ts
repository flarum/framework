import Stream from '../../common/utils/Stream';
import type EditorDriverInterface from '../../common/utils/EditorDriverInterface';
import type ComposerBody from '../components/ComposerBody';
import type Discussion from '../../common/models/Discussion';
declare class ComposerState {
    static Position: {
        HIDDEN: string;
        NORMAL: string;
        MINIMIZED: string;
        FULLSCREEN: string;
    };
    /**
     * The composer's current position.
     */
    position: string;
    /**
     * The composer's intended height, which can be modified by the user
     * (by dragging the composer handle).
     */
    height: number | null;
    /**
     * The dynamic component being shown inside the composer.
     */
    body: any;
    /**
     * A reference to the text editor that allows text manipulation.
     */
    editor: EditorDriverInterface | null;
    /**
     * If the composer was loaded and mounted.
     */
    mounted: boolean;
    protected onExit: {
        callback: () => boolean;
        message: string;
    } | null;
    /**
     * Fields of the composer.
     */
    fields: Record<string, Stream<any>> & {
        content: Stream<string>;
    };
    constructor();
    /**
     * Load a content component into the composer.
     *
     */
    load(componentClass: () => Promise<any & {
        default: ComposerBody;
    }> | ComposerBody, attrs: object): Promise<(() => Promise<any & {
        default: ComposerBody;
    }> | ComposerBody) | undefined>;
    /**
     * Clear the composer's content component.
     */
    clear(): void;
    /**
     * Show the composer.
     */
    show(): Promise<void>;
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
     * @param type The component class to check against. Subclasses are accepted as well.
     * @param data
     */
    bodyMatches(type: object, data?: any): boolean;
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
    composingReplyTo(discussion: Discussion): boolean;
    /**
     * Confirm with the user that they want to close the composer and lose their
     * content.
     *
     * @return Whether or not the exit was cancelled.
     */
    preventExit(): boolean | void;
    /**
     * Configure when / what to ask the user before closing the composer.
     *
     * The provided callback will be used to determine whether asking for
     * confirmation is necessary. If the callback returns true at the time of
     * closing, the provided text will be shown in a standard confirmation dialog.
     */
    preventClosingWhen(callback: () => boolean, message: string): void;
    /**
     * Minimum height of the Composer.
     * @returns {number}
     */
    minimumHeight(): number;
    /**
     * Maximum height of the Composer.
     */
    maximumHeight(): number;
    /**
     * Computed the composer's current height, based on the intended height, and
     * the composer's current state. This will be applied to the composer
     * content's DOM element.
     */
    computedHeight(): number | string;
}
export default ComposerState;
