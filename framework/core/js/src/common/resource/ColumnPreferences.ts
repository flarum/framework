/**
 * The columns of a resource table the user has explicitly shown or hidden, per resource type.
 *
 * Only explicit choices are stored, as `{columnKey: visible}`. A column the user never toggled
 * follows its `hidden` default, so a default-hidden column added later still starts hidden.
 *
 * The choices are kept in `localStorage` under `flarum.resourceTable.{type}.columns` (a JSON
 * object). An array there (the format of the first 2.0 betas) reads as "these keys hidden". Every
 * storage access is wrapped in try/catch: when storage is unavailable or full (private mode, quota,
 * a sandboxed frame), the choices live in memory for the session. The in-memory value wins over
 * storage, so a write that failed is still read back.
 *
 * The storage backend is private to this class, so it can move to user preferences without
 * changing `load()` / `save()`.
 */
export default class ColumnPreferences {
  protected static memory = new Map<string, Record<string, boolean>>();

  /**
   * The storage key of a type's column preferences.
   */
  static key(type: string): string {
    return `flarum.resourceTable.${type}.columns`;
  }

  /**
   * The explicit choices, column key to visible; empty when the user has made none. The map may
   * name columns that no longer exist; callers ignore those.
   */
  static load(type: string): Map<string, boolean> {
    const remembered = this.memory.get(type);

    if (remembered) return new Map(Object.entries(remembered));

    try {
      const raw = window.localStorage.getItem(this.key(type));

      return this.parse(raw === null ? null : JSON.parse(raw));
    } catch {
      // Unavailable storage or a corrupt value: no choices.
    }

    return new Map();
  }

  /**
   * Store the explicit choices, column key to visible. Never throws.
   */
  static save(type: string, choices: Map<string, boolean>): void {
    const value = Object.fromEntries(choices);

    this.memory.set(type, value);

    try {
      window.localStorage.setItem(this.key(type), JSON.stringify(value));
    } catch {
      // Unavailable or full storage: the in-memory value lasts for the session.
    }
  }

  /**
   * Forget the in-memory values (storage is left alone). For tests.
   *
   * @internal
   */
  static clear(): void {
    this.memory.clear();
  }

  protected static parse(value: unknown): Map<string, boolean> {
    const choices = new Map<string, boolean>();

    if (Array.isArray(value)) {
      value.forEach((key) => typeof key === 'string' && choices.set(key, false));
    } else if (value !== null && typeof value === 'object') {
      Object.entries(value).forEach(([key, visible]) => typeof visible === 'boolean' && choices.set(key, visible));
    }

    return choices;
  }
}
