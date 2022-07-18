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
export default class TextEditor extends Component<import("../Component").ComponentAttrs, undefined> {
    constructor();
    oninit(vnode: any): void;
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
    view(): JSX.Element;
    oncreate(vnode: any): void;
    onupdate(vnode: any): void;
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
     * @return {ItemList<import('mithril').Children>}
     */
    controlItems(): ItemList<import('mithril').Children>;
    /**
     * Build an item list for the toolbar controls.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    toolbarItems(): ItemList<import('mithril').Children>;
    /**
     * Handle input into the textarea.
     *
     * @param {string} value
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
