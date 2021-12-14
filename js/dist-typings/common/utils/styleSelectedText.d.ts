interface StyleArgs {
    prefix: string;
    suffix: string;
    blockPrefix: string;
    blockSuffix: string;
    multiline: boolean;
    replaceNext: string;
    prefixSpace: boolean;
    scanFor: string;
    surroundWithNewlines: boolean;
    orderedList: boolean;
    unorderedList: boolean;
    trimFirst: boolean;
}
export default function styleSelectedText(textarea: HTMLTextAreaElement, styleArgs: StyleArgs): void;
export {};
