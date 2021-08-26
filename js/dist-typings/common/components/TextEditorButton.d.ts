/**
 * The `TextEditorButton` component displays a button suitable for the text
 * editor toolbar.
 *
 * Automatically creates tooltips using the Tooltip component and provided text.
 *
 * ## Attrs
 * - `title` - Tooltip for the button
 */
export default class TextEditorButton extends Button<import("./Button").IButtonAttrs> {
    static initAttrs(attrs: any): void;
    constructor();
}
import Button from "./Button";
