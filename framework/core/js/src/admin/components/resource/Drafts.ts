/**
 * Uncommitted text of debounced inputs (the search, text filters), by key: typing shows at once
 * and is committed once it stops, so the redraw a slow commit triggers never reverts it.
 */
export default class Drafts {
  protected values: Record<string, string> = {};
  protected timers: Record<string, ReturnType<typeof setTimeout>> = {};

  /** How long typing has to stop before a commit, in ms. */
  constructor(readonly delay: number = 300) {}

  has(key: string): boolean {
    return key in this.values;
  }

  /** The draft, else the committed value. */
  get(key: string, committed: () => string): string {
    return this.has(key) ? this.values[key] : committed();
  }

  /** Keep `value` as the draft and commit it once typing stops; the draft goes once committed. */
  set(key: string, value: string, commit: (value: string) => Promise<unknown>): void {
    this.values[key] = value;

    clearTimeout(this.timers[key]);

    this.timers[key] = setTimeout(() => {
      delete this.timers[key];
      commit(value)
        // A failed request has already shown its error.
        .catch(() => {})
        .finally(() => {
          if (this.values[key] === value) delete this.values[key];
          m.redraw();
        });
      m.redraw();
    }, this.delay);
  }

  /** Drop the drafts whose key passes `test` (default: all), uncommitted. */
  discard(test: (key: string) => boolean = () => true): void {
    Object.keys(this.values)
      .filter(test)
      .forEach((key) => {
        clearTimeout(this.timers[key]);
        delete this.timers[key];
        delete this.values[key];
      });
  }
}
