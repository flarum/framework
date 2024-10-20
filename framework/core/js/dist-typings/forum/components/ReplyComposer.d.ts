/**
 * The `ReplyComposer` component displays the composer content for replying to a
 * discussion.
 *
 * ### Attrs
 *
 * - All of the attrs of ComposerBody
 * - `discussion`
 */
export default class ReplyComposer extends ComposerBody<import("./ComposerBody").IComposerBodyAttrs> {
    static initAttrs(attrs: any): void;
    constructor();
    /**
     * Jump to the preview when triggered by the text editor.
     */
    jumpToPreview(e: any): void;
    /**
     * Get the data to submit to the server when the reply is saved.
     *
     * @return {Record<string, unknown>}
     */
    data(): Record<string, unknown>;
}
import ComposerBody from "./ComposerBody";
