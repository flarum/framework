import type ForumApplication from 'flarum/forum/ForumApplication';
import type IExtender from 'flarum/common/extenders/IExtender';
import type { IExtensionModule } from 'flarum/common/extenders/IExtender';
import deckColumnTypes from '../columns/deckColumnTypes';
import { membershipEvents } from '../columns/realtimeEvents';
import type { DeckColumnType } from '../columns/DeckColumnType';

/**
 * Registers column types for Deck. From an extension that supports Deck
 * optionally, list `flarum/deck` under `optional-dependencies` (so Deck loads
 * first) and only add the extender while it's enabled:
 *
 *     import DeckColumns from 'ext:flarum/deck/forum/extenders/DeckColumns';
 *
 *     // in your extend.ts array:
 *     ...('flarum-deck' in flarum.extensions
 *       ? [new DeckColumns().add('acme-bookmarks', { ... }, 20)]
 *       : []),
 *
 * (flarum-webpack-config finds a module's default export with a regex over its
 * source, comments included, so the usual array export can't be spelled out
 * here without unregistering this module.)
 *
 * Prefix keys with your extension's name; they're stored in members' layouts.
 * Priority orders the add-column modal (Deck's own run from 100 down to 20).
 */
export default class DeckColumns implements IExtender<ForumApplication> {
  private readonly types: Array<{ key: string; type: DeckColumnType; priority: number }> = [];
  private readonly events: string[] = [];

  add(key: string, type: DeckColumnType, priority: number = 0): this {
    this.types.push({ key, type, priority });

    return this;
  }

  /**
   * Realtime events, as your extension broadcasts them, that can move a
   * discussion in or out of a filter without anything being posted: such as
   * choosing a best answer, for an `is:solved` column. Filtered discussion
   * columns ask the server about that discussion again, rather than only
   * redrawing it.
   */
  membershipEvents(...names: string[]): this {
    this.events.push(...names);

    return this;
  }

  extend(app: ForumApplication, extension: IExtensionModule): void {
    this.types.forEach(({ key, type, priority }) => deckColumnTypes.add(key, type, priority));
    this.events.forEach((name) => membershipEvents.add(name));
  }
}
