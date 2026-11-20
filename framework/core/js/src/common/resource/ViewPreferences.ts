import GuardedStorage from './guardedStorage';

/**
 * The saved view of a resource list: its filters and sort.
 */
export interface SavedView {
  sort?: string;
  /** `''` marks a filter the user cleared, so its `default` does not come back. */
  filter?: Record<string, string>;
}

/**
 * The last filters and sort the user picked on a resource list, per resource type, so the list
 * opens the way it was left. The search and the page are not saved.
 *
 * Kept under `flarum.resourceTable.{type}.view` (a JSON object), see `GuardedStorage`.
 */
export default class ViewPreferences {
  protected static storage = new GuardedStorage();

  /**
   * The storage key of a type's saved view.
   */
  static key(type: string): string {
    return `flarum.resourceTable.${type}.view`;
  }

  /**
   * The saved view, null when there is none. Values are strings; callers drop unknown filters and
   * sorts.
   */
  static load(type: string): SavedView | null {
    const value = this.storage.read(this.key(type));

    return value === undefined ? null : this.parse(value);
  }

  /**
   * Save the view. Never throws.
   */
  static save(type: string, view: SavedView): void {
    this.storage.write(this.key(type), this.copy(view));
  }

  /**
   * Forget the in-memory values (storage is left alone). For tests.
   *
   * @internal
   */
  static clear(): void {
    this.storage.clear();
  }

  protected static copy(view: SavedView): SavedView {
    const out: SavedView = {};

    if (view.sort) out.sort = view.sort;
    if (view.filter && Object.keys(view.filter).length) out.filter = { ...view.filter };

    return out;
  }

  protected static parse(value: unknown): SavedView | null {
    if (value === null || typeof value !== 'object' || Array.isArray(value)) return null;

    const { sort, filter } = value as Record<string, unknown>;
    const view: SavedView = {};

    if (typeof sort === 'string' && sort !== '') view.sort = sort;

    if (filter !== null && typeof filter === 'object' && !Array.isArray(filter)) {
      const entries = Object.entries(filter).filter(([, v]) => typeof v === 'string');

      if (entries.length) view.filter = Object.fromEntries(entries) as Record<string, string>;
    }

    return view;
  }
}
