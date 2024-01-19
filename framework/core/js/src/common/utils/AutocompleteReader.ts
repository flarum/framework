export default class AutocompleteReader {
  public readonly symbol: string | ((character: string) => boolean) | null;
  public relativeStart: number = 0;
  public absoluteStart: number = 0;

  constructor(symbol: string | ((character: string) => boolean) | null) {
    this.symbol = symbol;
  }

  check(lastChunk: string, cursor: number, validBit: RegExp | null = null): AutocompleteCheck | null {
    this.absoluteStart = 0;

    // Search backwards from the cursor for a symbol. If we find
    // one and followed by a whitespace, we will want to show the
    // autocomplete dropdown!
    for (let i = lastChunk.length - 1; i >= 0; i--) {
      const character = lastChunk.substr(i, 1);

      // check what the user typed is valid.
      if (validBit && !validBit?.test(character)) return null;

      // check if the character is the symbol we are looking for.
      if (this.symbol) {
        const symbol = typeof this.symbol === 'string' ? (character: string) => character === this.symbol : this.symbol;
        if (!symbol(character)) continue;
      }

      // make sure the symbol preceded by a whitespace or newline
      if (i === 0 || /\s/.test(lastChunk.substr(i - 1, 1))) {
        this.relativeStart = i + (this.symbol ? 1 : 0);
        this.absoluteStart = cursor - lastChunk.length + i + (this.symbol ? 1 : 0);

        return {
          symbol: this.symbol && character,
          relativeStart: this.relativeStart,
          absoluteStart: this.absoluteStart,
          typed: lastChunk.substring(this.relativeStart).toLowerCase(),
        };
      }
    }

    return null;
  }
}

export type AutocompleteCheck = {
  symbol: string | null;
  relativeStart: number;
  absoluteStart: number;
  typed: string;
};
