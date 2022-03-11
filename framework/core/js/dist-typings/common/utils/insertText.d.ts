export interface SelectionRange {
    text: string;
    selectionStart: number | undefined;
    selectionEnd: number | undefined;
}
export default function insertText(textarea: HTMLTextAreaElement, { text, selectionStart, selectionEnd }: SelectionRange): void;
