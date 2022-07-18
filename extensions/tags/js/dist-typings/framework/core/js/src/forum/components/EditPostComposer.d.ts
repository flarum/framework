/**
 * The `EditPostComposer` component displays the composer content for editing a
 * post. It sets the initial content to the content of the post that is being
 * edited, and adds a header control to indicate which post is being edited.
 *
 * ### Attrs
 *
 * - All of the attrs for ComposerBody
 * - `post`
 */
export default class EditPostComposer extends ComposerBody {
    static initAttrs(attrs: any): void;
    /**
     * Jump to the preview when triggered by the text editor.
     */
    jumpToPreview(e: any): void;
    /**
     * Get the data to submit to the server when the post is saved.
     *
     * @return {Record<string, unknown>}
     */
    data(): Record<string, unknown>;
}
import ComposerBody from "./ComposerBody";
