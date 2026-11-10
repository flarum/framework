import type Mithril from 'mithril';
import type ItemList from 'flarum/common/utils/ItemList';
import type Model from 'flarum/common/Model';
import type Discussion from 'flarum/common/models/Discussion';
import type Post from 'flarum/common/models/Post';
import type { GlobalSearchSource } from 'flarum/common/components/AbstractGlobalSearch';

/** One event from flarum/realtime, after Deck has put its payload in the store. */
export interface DeckRealtimeEvent {
  /** As broadcast, e.g. `Flarum\\Post\\Event\\Posted`, `revisedEvent`, `notification`. */
  name: string;
  /** The JSON:API document the event carried. */
  payload: any;
  /** The payload's main model, from the store. For post events this is the discussion. */
  model: Model | null;
  /** The discussion the event concerns, if any. */
  discussion: Discussion | null;
  /** For post events (new replies, edits, likes), the post itself. */
  post: Post | null;
}

/**
 * What a column did with a realtime event:
 * - `inserted`: added items at the top from the payload (the reader keeps their place);
 * - `updated`: changed what's shown in place;
 * - `check`: it may concern the column but only the server can tell, so Deck
 *   runs the column's `applyNew()` (throttled) for that column alone;
 * - nothing: not relevant.
 */
export type DeckRealtimeResult = 'inserted' | 'updated' | 'check' | void;

/**
 * One column as stored in the member's `deckColumns` preference. The server
 * caps params at four scalar values; anything richer belongs in the column
 * type, not the preference.
 */
export interface DeckColumnConfig {
  id: string;
  type: string;
  /** In pixels; columns stretch past it, in proportion, to fill a row. */
  width: number;
  /** 0 or 1: Deck has up to two rows of columns. */
  row?: number;
  params: Record<string, string | number>;
}

/**
 * Makes a field a picker: the member searches the way they would in the
 * forum's search, and has to choose a real result, whose params are stored.
 */
export interface DeckColumnSearch<T extends Model = any> {
  /**
   * Lists results as the forum's search does, e.g. core's
   * GlobalUsersSearchSource. Each result's `data-id` is looked up in the store
   * under the source's `resource`.
   */
  source(): GlobalSearchSource;
  /** List every result before anything is typed, for short lists such as tags. */
  browse?: boolean;
  /** The chosen result as the field shows it; defaults to `label`. */
  display?(result: T): Mithril.Children;
  label(result: T): string;
  /** Merged into the column's params when chosen. */
  params(result: T): DeckColumnConfig['params'];
}

export interface DeckColumnField {
  key: string;
  label: Mithril.Children;
  placeholder?: string;
  help?: Mithril.Children;
  /** Turns the field into a select of value => label. */
  options?: () => Record<string, string>;
  /** Turns the field into a picker. */
  search?: DeckColumnSearch;
  /**
   * Makes the field a query of this resource's registered gambits (e.g.
   * 'discussions'), written in the search modal with its suggestions and a
   * preview of what matches.
   */
  gambits?: string;
  /** A picker of your own; whatever it passes to `onchange` is the field's value. */
  input?: (attrs: { id: string; value: string; onchange: (value: string) => void }) => Mithril.Children;
  /** Converts what the member typed into the stored value; return null to reject it. */
  parse?: (value: string) => string | number | null;
  /** Shown when `parse` rejects the value. */
  invalidText?: Mithril.Children;
}

/**
 * The live half of a column. A source owns its data and must never change what
 * is on screen from `checkForNew()`: new activity waits behind the column's
 * "new" pill until the member asks for it with `showNew()`.
 */
export interface DeckColumnSource {
  load(): Promise<unknown>;
  view(): Mithril.Children;
  /** Resolves to how many items `showNew()` would add or move to the top. */
  checkForNew(): Promise<number>;
  showNew(): Promise<unknown>;
  /**
   * Realtime only: put new items straight at the top of the column. Resolves
   * to how many were added, or null when this column can't (it then falls
   * back to `checkForNew()` and the "new" pill).
   */
  applyNew?(): Promise<number | null>;
  /**
   * Realtime: react to an event from the public or the member's channel. Every
   * column should, so that it updates without polling; the payload usually
   * holds enough to update directly. Without this, the column is checked
   * whenever a discussion is started or a reply posted.
   */
  onRealtime?(event: DeckRealtimeEvent): DeckRealtimeResult;
  /**
   * Take out items the store already shows no longer belong, such as a
   * discussion just read, in Unread. Some changes send no realtime event,
   * reading among them, so this runs when the deck is shown again and before
   * every check.
   */
  prune?(): void;
  /**
   * Called while the column is on screen, and again whenever realtime
   * reconnects, so must be safe to repeat. For sources with their own feed.
   */
  start?(): void;
  stop?(): void;
  controls?(items: ItemList<Mithril.Children>): void;
}

export interface DeckColumnType {
  icon: string;
  /** Shown in the column header instead of the icon, e.g. a group's badge. */
  badge?(config: DeckColumnConfig): Mithril.Children;
  /** Shown in the add-column modal. */
  label(): string;
  /** Shown in the column header; may depend on the column's params. */
  title(config: DeckColumnConfig): string;
  /** Whether the current member may add (and see) this kind of column. */
  isAvailable(): boolean;
  /**
   * Built when the add-column modal opens, not at registration: an extension
   * registers from its extend.ts before the app has booted, when translations
   * (and anything else on `app`) aren't available yet.
   */
  fields?: () => DeckColumnField[];
  createSource(config: DeckColumnConfig): DeckColumnSource;
}
