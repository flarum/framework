export default class AutocompleteReader {
    readonly symbol: string | ((character: string) => boolean) | null;
    relativeStart: number;
    absoluteStart: number;
    constructor(symbol: string | ((character: string) => boolean) | null);
    check(lastChunk: string, cursor: number, validBit?: RegExp | null): AutocompleteCheck | null;
}
export declare type AutocompleteCheck = {
    symbol: string | null;
    relativeStart: number;
    absoluteStart: number;
    typed: string;
};
