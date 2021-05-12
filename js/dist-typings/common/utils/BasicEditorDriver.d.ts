import EditorDriverInterface, { EditorDriverParams } from './EditorDriverInterface';
import ItemList from './ItemList';
export default class BasicEditorDriver implements EditorDriverInterface {
    el: HTMLTextAreaElement;
    constructor(dom: HTMLElement, params: EditorDriverParams);
    build(dom: HTMLElement, params: EditorDriverParams): void;
    keyHandlers(params: EditorDriverParams): ItemList;
    moveCursorTo(position: number): void;
    getSelectionRange(): Array<number>;
    getLastNChars(n: number): string;
    insertAtCursor(text: string): void;
    insertAt(pos: number, text: string): void;
    insertBetween(selectionStart: number, selectionEnd: number, text: string): void;
    replaceBeforeCursor(start: number, text: string): void;
    protected setSelectionRange(start: number, end: number): void;
    getCaretCoordinates(position: number): {
        top: number;
        left: number;
    };
    /**
     * Set the disabled status of the editor.
     */
    disabled(disabled: boolean): void;
    /**
     * Focus on the editor.
     */
    focus(): void;
    /**
     * Destroy the editor
     */
    destroy(): void;
}
