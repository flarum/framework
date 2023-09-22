export default interface IGambit {
  pattern(): string;
  toFilter(matches: string[], negate: boolean): Record<string, any>;
}
