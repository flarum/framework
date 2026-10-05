import app from 'flarum/forum/app';
import deckColumnTypes from '../columns/deckColumnTypes';
import { DEFAULT_WIDTH } from './deckSizes';
import type { DeckColumnConfig } from '../columns/DeckColumnType';

export const PREFERENCE_KEY = 'deckColumns';
export const SPLIT_PREFERENCE_KEY = 'deckRowSplit';

export function canUseDeck(): boolean {
  return !!app.session.user && !!app.forum.attribute<boolean>('canUseDeck');
}

export function maxColumns(): number {
  return app.forum.attribute<number>('deckMaxColumns') || 8;
}

export function newColumnId(): string {
  return Math.random().toString(36).slice(2, 10);
}

export function makeColumn(type: string, params: DeckColumnConfig['params'] = {}, width: number = DEFAULT_WIDTH): DeckColumnConfig {
  return { id: newColumnId(), type, width, row: 0, params };
}

/**
 * The member's saved layout, or a starter deck if they've never saved one.
 * Columns whose type is unknown or unavailable (an extension was disabled, a
 * permission was revoked) are hidden but kept, so they come back if it does.
 */
export function storedColumns(): DeckColumnConfig[] {
  const stored = app.session.user?.preferences()?.[PREFERENCE_KEY];

  if (Array.isArray(stored)) return stored as DeckColumnConfig[];

  return defaultColumns();
}

export function hasCustomLayout(): boolean {
  return Array.isArray(app.session.user?.preferences()?.[PREFERENCE_KEY]);
}

/**
 * flarum/subscriptions registers this column. Named here rather than imported,
 * so Deck doesn't depend on it: without it, the starter deck uses All instead.
 */
const FOLLOWING = 'flarum-subscriptions.following';

export function defaultColumns(): DeckColumnConfig[] {
  const starter = deckColumnTypes.has(FOLLOWING) && deckColumnTypes.get(FOLLOWING).isAvailable() ? FOLLOWING : 'all';

  return [makeColumn(starter), makeColumn('unread'), makeColumn('notifications')].filter((column) => isDisplayable(column));
}

export function isDisplayable(column: DeckColumnConfig): boolean {
  return deckColumnTypes.has(column.type) && deckColumnTypes.get(column.type).isAvailable();
}

/** Null clears the layout, so the member follows the default (and any later change to it). */
export function saveColumns(columns: DeckColumnConfig[] | null): Promise<unknown> {
  return app.session.user!.savePreferences({ [PREFERENCE_KEY]: columns });
}

/** The top row's share of the height; null for an even split. */
export function storedSplit(): number | null {
  const split = app.session.user?.preferences()?.[SPLIT_PREFERENCE_KEY];

  return typeof split === 'number' ? split : null;
}

export function saveSplit(split: number | null): Promise<unknown> {
  return app.session.user!.savePreferences({ [SPLIT_PREFERENCE_KEY]: split });
}

/** Back to the default deck, rows evenly split. */
export function resetLayout(): Promise<unknown> {
  return app.session.user!.savePreferences({ [PREFERENCE_KEY]: null, [SPLIT_PREFERENCE_KEY]: null });
}
