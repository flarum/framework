/**
 * The `DiscussionComposer` component displays the composer content for starting
 * a new discussion. It adds a text field as a header control so the user can
 * enter the title of their discussion. It also overrides the `submit` and
 * `willExit` actions to account for the title.
 *
 * ### Attrs
 *
 * - All of the attrs for ComposerBody
 * - `titlePlaceholder`
 */
export default class DiscussionComposer extends ComposerBody {
    static initAttrs(attrs: any): void;
    /**
     * The value of the title input.
     *
     * @type {Function}
     */
    title: Function | undefined;
    /**
     * Handle the title input's keydown event. When the return key is pressed,
     * move the focus to the start of the text editor.
     *
     * @param {KeyboardEvent} e
     */
    onkeydown(e: KeyboardEvent): void;
    /**
     * Get the data to submit to the server when the discussion is saved.
     *
     * @return {Record<string, unknown>}
     */
    data(): Record<string, unknown>;
}
import ComposerBody from "./ComposerBody";
