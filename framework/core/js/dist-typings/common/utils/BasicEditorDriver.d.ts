import ItemList from './ItemList';
import type EditorDriverInterface from './EditorDriverInterface';
import { type EditorDriverParams } from './EditorDriverInterface';
export default class BasicEditorDriver implements EditorDriverInterface {
    el: HTMLTextAreaElement;
    constructor(dom: HTMLElement, params: EditorDriverParams);
    protected build(dom: HTMLElement, params: EditorDriverParams): void;
    protected keyHandlers(params: EditorDriverParams): ItemList<(e: KeyboardEvent) => void>;
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
