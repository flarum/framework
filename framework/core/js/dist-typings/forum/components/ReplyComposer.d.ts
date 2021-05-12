/**
 * The `ReplyComposer` component displays the composer content for replying to a
 * discussion.
 *
 * ### Attrs
 *
 * - All of the attrs of ComposerBody
 * - `discussion`
 */
export default class ReplyComposer extends ComposerBody {
    static initAttrs(attrs: any): void;
    /**
     * Jump to the preview when triggered by the text editor.
     */
    jumpToPreview(e: any): void;
    /**
     * Get the data to submit to the server when the reply is saved.
     *
     * @return {Object}
     */
    data(): Object;
}
import ComposerBody from "./ComposerBody";
