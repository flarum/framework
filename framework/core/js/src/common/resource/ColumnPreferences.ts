import GuardedStorage from './guardedStorage';

/**
 * The columns of a resource table the user has explicitly shown or hidden, per resource type.
 *
 * Only explicit choices are stored, as `{columnKey: visible}`. A column the user never toggled
 * follows its `hidden` default, so a default-hidden column added later still starts hidden.
 *
 * The choices are kept under `flarum.resourceTable.{type}.columns` (a JSON object), see
 * `GuardedStorage`.
 */
export default class ColumnPreferences {
  protected static storage = new GuardedStorage();

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
    return this.parse(this.storage.read(this.key(type)) ?? null);
  }

  /**
   * Store the explicit choices, column key to visible. Never throws.
   */
  static save(type: string, choices: Map<string, boolean>): void {
    this.storage.write(this.key(type), Object.fromEntries(choices));
  }

  /**
   * Forget the in-memory values (storage is left alone). For tests.
   *
   * @internal
   */
  static clear(): void {
    this.storage.clear();
  }

  protected static parse(value: unknown): Map<string, boolean> {
    const choices = new Map<string, boolean>();

    if (value !== null && typeof value === 'object' && !Array.isArray(value)) {
      Object.entries(value).forEach(([key, visible]) => typeof visible === 'boolean' && choices.set(key, visible));
    }

    return choices;
  }
}
