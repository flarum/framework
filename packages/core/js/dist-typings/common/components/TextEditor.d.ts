/**
 * The `TextEditor` component displays a textarea with controls, including a
 * submit button.
 *
 * ### Attrs
 *
 * - `composer`
 * - `submitLabel`
 * - `value`
 * - `placeholder`
 * - `disabled`
 * - `preview`
 */
export default class TextEditor extends Component<import("../Component").ComponentAttrs> {
    constructor();
    /**
     * The value of the editor.
     *
     * @type {String}
     */
    value: string | undefined;
    /**
     * Whether the editor is disabled.
     */
    disabled: any;
    buildEditorParams(): {
        classNames: string[];
        disabled: any;
        placeholder: any;
        value: string | undefined;
        oninput: (value: string) => void;
        inputListeners: never[];
        onsubmit: () => void;
    };
    buildEditor(dom: any): BasicEditorDriver;
    /**
     * Build an item list for the text editor controls.
     *
     * @return {ItemList}
     */
    controlItems(): ItemList;
    /**
     * Build an item list for the toolbar controls.
     *
     * @return {ItemList}
     */
    toolbarItems(): ItemList;
    /**
     * Handle input into the textarea.
     *
     * @param {String} value
     */
    oninput(value: string): void;
    /**
     * Handle the submit button being clicked.
     */
    onsubmit(): void;
}
import Component from "../Component";
import BasicEditorDriver from "../utils/BasicEditorDriver";
import ItemList from "../utils/ItemList";
