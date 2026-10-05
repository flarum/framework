import app from 'flarum/forum/app';
import DeckColumnState from './DeckColumnState';
import { REMOVED, subjectPost } from '../columns/realtimeEvents';
import type Post from 'flarum/common/models/Post';
import type Discussion from 'flarum/common/models/Discussion';
import {
  PREFERENCE_KEY,
  defaultColumns,
  hasCustomLayout,
  isDisplayable,
  maxColumns,
  resetLayout,
  saveColumns,
  saveSplit,
  storedColumns,
  storedSplit,
} from '../utils/deckLayout';
import { DEFAULT_SPLIT, clampSplit, clampWidth } from '../utils/deckSizes';
import type { DeckColumnConfig } from '../columns/DeckColumnType';

/** The slice of a pusher-js channel this needs, without depending on flarum/realtime. */
interface Channel {
  bind_global(callback: (event: string, data: any) => void): void;
  unbind_global(callback: (event: string, data: any) => void): void;
}

type ChannelKind = 'public' | 'user';

/** Throttling for columns whose events need the server to decide; see DeckColumnState. */
export const MIN_CHECK_INTERVAL = 15000;
const HEARTBEAT = 15000;

/** An event reaches a member on both the public and their own channel; this long apart is one event. */
const DUPLICATE_WINDOW = 5000;

export const ROWS = 2;

/** Columns saved before rows existed, or with no row, belong to the first. */
export const rowOf = (config: DeckColumnConfig): number => (config.row && config.row < ROWS ? config.row : 0);

/**
 * Held for the whole session, so leaving the deck to read a discussion and
 * coming back finds every column, its scroll position and its pending count
 * as they were.
 */
export default class DeckState {
  /** Every stored column, including ones that can't currently be shown. */
  protected configs: DeckColumnConfig[];
  protected states = new Map<string, DeckColumnState>();

  protected channels: Array<[ChannelKind, Channel, (name: string, data: any) => void]> = [];
  protected recentEvents = new Map<string, { kind: ChannelKind; at: number }>();
  protected wasLive = false;
  protected heartbeat: number | null = null;
  protected lastCheck = Date.now();
  protected saveTimer: number | null = null;

  /** The top row's share of the height, or null for an even split. */
  protected split: number | null = storedSplit();
  protected splitTimer: number | null = null;

  /** Whether the member has a layout of their own, rather than the default. */
  protected customised: boolean;

  /** Read out by screen readers after a column is moved without dragging. */
  public announcement = '';

  constructor() {
    this.customised = hasCustomLayout();
    this.configs = storedColumns();
  }

  /**
   * Picks up a layout saved from outside the deck (e.g. "Add to Deck" on a
   * discussion), keeping the live state of columns that survived.
   */
  sync(): void {
    if (this.saveTimer) return;

    const stored = app.session.user?.preferences()?.[PREFERENCE_KEY];

    if (!Array.isArray(stored) || JSON.stringify(stored) === JSON.stringify(this.configs)) return;

    this.customised = true;
    this.configs = stored.map((config: DeckColumnConfig) => ({ ...config, params: { ...config.params } }));

    const ids = new Set(this.configs.map((config) => config.id));
    [...this.states.keys()].filter((id) => !ids.has(id)).forEach((id) => this.states.delete(id));
  }

  /** Every column that can be shown, top row first: the order phones swipe through. */
  columns(): DeckColumnState[] {
    return this.rows().flat();
  }

  /** The columns in each row. The second row is empty until something is put there. */
  rows(): DeckColumnState[][] {
    const rows: DeckColumnState[][] = Array.from({ length: ROWS }, () => []);

    this.columnStates().forEach((column) => rows[rowOf(column.config)].push(column));

    return rows;
  }

  protected columnStates(): DeckColumnState[] {
    return this.configs.filter(isDisplayable).map((config) => {
      let state = this.states.get(config.id);

      if (!state) {
        state = new DeckColumnState(config);
        this.states.set(config.id, state);
      }

      state.config = config;

      return state;
    });
  }

  canAddColumn(): boolean {
    return this.configs.length < maxColumns();
  }

  addColumn(config: DeckColumnConfig): void {
    if (!this.canAddColumn()) return;

    this.configs.push(config);
    this.save();
  }

  removeColumn(id: string): void {
    this.configs = this.configs.filter((config) => config.id !== id);
    this.states.delete(id);
    this.save();
  }

  /**
   * Puts a column at `index` among the visible columns of `row`, as a drag and
   * drop leaves it. Hidden columns (an extension disabled, a permission
   * revoked) keep their place in the stored order and don't count.
   */
  moveTo(id: string, row: number, index: number): void {
    const config = this.configs.find((config) => config.id === id);

    if (!config) return;

    this.configs = this.configs.filter((other) => other !== config);
    config.row = row;

    const target = this.rows()[row].filter((column) => column.config.id !== id)[index];
    const at = target ? this.configs.indexOf(target.config) : this.endOfRow(row);

    this.configs.splice(at, 0, config);
    this.save();
  }

  /**
   * Reorders within the single strip that phones and short screens show: the
   * column joins the row of the neighbour it lands after (or before, at the
   * very start), so both rows survive for when there's room for them again.
   */
  moveFlat(id: string, index: number): void {
    const others = this.columns().filter((column) => column.config.id !== id);
    const neighbour = others[index - 1] ?? others[index];
    const row = neighbour ? rowOf(neighbour.config) : 0;
    const before = others.slice(0, index).filter((column) => rowOf(column.config) === row).length;

    this.moveTo(id, row, before);
  }

  /** A column's place among the columns shown beside it: in its row, or along the single strip. */
  placeOf(id: string, flat: boolean): { index: number; count: number; row: number } | null {
    const column = this.columns().find((column) => column.config.id === id);

    if (!column) return null;

    const row = rowOf(column.config);
    const peers = flat ? this.columns() : this.rows()[row];

    return { index: peers.indexOf(column), count: peers.length, row };
  }

  canMoveBy(id: string, delta: number, flat: boolean): boolean {
    const place = this.placeOf(id, flat);

    return !!place && place.index + delta >= 0 && place.index + delta < place.count;
  }

  /** Moves a column `delta` places along its row, or along the single strip: the keyboard's drag and drop. */
  moveBy(id: string, delta: number, flat: boolean): void {
    if (!this.canMoveBy(id, delta, flat)) return;

    const { index, row } = this.placeOf(id, flat)!;

    flat ? this.moveFlat(id, index + delta) : this.moveTo(id, row, index + delta);
  }

  protected endOfRow(row: number): number {
    const last = this.configs.map((config) => rowOf(config) === row).lastIndexOf(true);

    return last === -1 ? this.configs.length : last + 1;
  }

  /**
   * @param save False while the width is still being dragged: it's saved once,
   *             when the drag ends.
   */
  setWidth(id: string, width: number, save = true): void {
    const config = this.configs.find((config) => config.id === id);

    if (!config) return;

    config.width = clampWidth(width);

    save ? this.save() : m.redraw();
  }

  /** The other columns shown beside one, in its row or along the single strip. */
  neighbours(id: string, flat: boolean): DeckColumnState[] {
    const place = this.placeOf(id, flat);

    if (!place) return [];

    return (flat ? this.columns() : this.rows()[place.row]).filter((column) => column.config.id !== id);
  }

  rowSplit(): number {
    return this.split ?? DEFAULT_SPLIT;
  }

  /**
   * @param split The top row's share of the height; null to split evenly.
   * @param save False while it's still being dragged.
   */
  setRowSplit(split: number | null, save = true): void {
    this.split = split === null ? null : clampSplit(split);

    if (save) {
      if (this.splitTimer) clearTimeout(this.splitTimer);

      this.splitTimer = window.setTimeout(() => {
        this.splitTimer = null;
        saveSplit(this.split);
      }, 500);
    }

    m.redraw();
  }

  isCustomised(): boolean {
    return this.customised || this.split !== null;
  }

  reset(): void {
    if (this.saveTimer) clearTimeout(this.saveTimer);
    if (this.splitTimer) clearTimeout(this.splitTimer);
    this.saveTimer = this.splitTimer = null;

    this.customised = false;
    this.split = null;
    this.configs = defaultColumns();
    this.states.clear();

    resetLayout();
    m.redraw();
  }

  /** Batches rapid rearranging into one request. */
  protected save(): void {
    this.customised = true;

    if (this.saveTimer) clearTimeout(this.saveTimer);

    this.saveTimer = window.setTimeout(() => {
      this.saveTimer = null;
      saveColumns(this.configs);
    }, 500);

    m.redraw();
  }

  start(): void {
    this.bindChannels();
    this.wasLive = this.isLive();

    this.heartbeat = window.setInterval(() => this.tick(), HEARTBEAT);
    document.addEventListener('visibilitychange', this.onVisibilityChange);
  }

  stop(): void {
    this.unbindChannels();

    if (this.heartbeat) clearInterval(this.heartbeat);
    this.heartbeat = null;
    this.columns().forEach((column) => column.cancelCheck());

    document.removeEventListener('visibilitychange', this.onVisibilityChange);
  }

  /** Asks every column for anything new: on reconnecting, returning to the tab, or polling. */
  checkAll(): void {
    const live = this.isLive();

    this.lastCheck = Date.now();
    this.columns().forEach((column) => column.check(live));
  }

  /** Channel objects outlive a dropped socket, so only a connected socket counts. */
  isLive(): boolean {
    return this.channels.length > 0 && (app as any).websocket?.connection?.state === 'connected';
  }

  /**
   * Every event on the public and the member's channel, handed to each column.
   * Realtime sends the member a payload built for them on their own channel,
   * and public discussions' events on the public channel too, so the first copy
   * is dispatched and a later personalised copy only refreshes the store.
   */
  protected onEvent(kind: ChannelKind, name: string, data: any): void {
    if (name.startsWith('pusher:') || name.startsWith('pusher_internal:') || !data?.data) return;

    if (name === REMOVED) {
      // A member's removals come on their own channel, worked out for them;
      // the public channel's copy is for guests.
      if (kind === 'user') this.onRemoved(data);

      return;
    }

    // Post events carry their discussion as the main item, so tell two replies
    // in one discussion apart by the post appended to `included`.
    const postRef = [...(data.included ?? [])].reverse().find((item: any) => item?.type === 'posts');
    const key = `${name}:${data.data.type}:${data.data.id}:${postRef?.id ?? ''}`;
    const now = Date.now();
    const seen = this.recentEvents.get(key);

    this.pruneRecentEvents(now);

    // Only the other channel's copy is a duplicate: the same event twice on
    // one channel is two changes, such as two flags in a row.
    if (seen && seen.kind !== kind && now - seen.at < DUPLICATE_WINDOW) {
      if (kind === 'user' && seen.kind === 'public') {
        app.store.pushPayload(data);
        this.recentEvents.set(key, { kind, at: seen.at });
        m.redraw();
      }

      return;
    }

    this.recentEvents.set(key, { kind, at: now });

    const pushed = (app.store.pushPayload(data) as any) ?? null;
    const model = Array.isArray(pushed) ? pushed[0] ?? null : pushed;
    const post = subjectPost(name, data) ?? (model?.data?.type === 'posts' ? model : null);
    const discussion = model?.data?.type === 'discussions' ? (model as Discussion) : post?.discussion() || null;
    const event = { name, payload: data, model, discussion, post };
    const live = this.isLive();

    this.columns().forEach((column) => column.handleRealtime(event, live));

    m.redraw();
  }

  /** Only a type and id arrive: nothing to put in the store, just things to take out of columns. */
  protected onRemoved(data: any): void {
    const { type, id } = data.data;
    const model = app.store.getById(type, String(id)) ?? null;
    const post = type === 'posts' ? (model as Post | null) : null;
    const discussion =
      type === 'discussions'
        ? (model as Discussion | null)
        : post?.discussion() ||
          (data.meta?.discussionId ? app.store.getById<Discussion>('discussions', String(data.meta.discussionId)) ?? null : null);
    const event = { name: REMOVED, payload: data, model, discussion, post };
    const live = this.isLive();

    this.columns().forEach((column) => column.handleRealtime(event, live));

    m.redraw();
  }

  protected pruneRecentEvents(now: number): void {
    if (this.recentEvents.size < 200) return;

    this.recentEvents.forEach((seen, key) => {
      if (now - seen.at >= DUPLICATE_WINDOW) this.recentEvents.delete(key);
    });
  }

  protected onVisibilityChange = (): void => {
    if (!document.hidden && Date.now() - this.lastCheck > MIN_CHECK_INTERVAL) this.checkAll();
  };

  /**
   * While realtime is connected nothing polls: events drive every column. Events
   * can only be missed while disconnected, so reconnecting catches up once.
   * Without realtime, columns check on the admin's polling interval instead.
   */
  protected tick(): void {
    // Realtime replaces its channel objects when it reconnects.
    if (this.channelsChanged()) {
      this.unbindChannels();
      this.bindChannels();
      this.columns().forEach((column) => column.source.start?.());
    }

    const live = this.isLive();
    const reconnected = live && !this.wasLive;
    this.wasLive = live;

    if (document.hidden) return;

    if (reconnected) {
      this.checkAll();
      return;
    }

    const interval = app.forum.attribute<number>('deckPollInterval');

    if (!live && interval > 0 && Date.now() - this.lastCheck >= interval * 1000) {
      this.checkAll();
    }
  }

  protected currentChannels(): Array<[ChannelKind, Channel]> {
    const channels = (app as any).websocket_channels;

    return (
      [
        ['public', channels?.public],
        ['user', channels?.user],
      ] as Array<[ChannelKind, Channel | null]>
    ).filter((entry): entry is [ChannelKind, Channel] => !!entry[1]);
  }

  protected channelsChanged(): boolean {
    const current = this.currentChannels();

    return current.length !== this.channels.length || current.some(([, channel], i) => channel !== this.channels[i][1]);
  }

  protected bindChannels(): void {
    this.channels = this.currentChannels().map(([kind, channel]) => {
      const handler = (name: string, data: any) => this.onEvent(kind, name, data);

      channel.bind_global(handler);

      return [kind, channel, handler] as [ChannelKind, Channel, (name: string, data: any) => void];
    });
  }

  protected unbindChannels(): void {
    this.channels.forEach(([, channel, handler]) => channel.unbind_global(handler));
    this.channels = [];
  }
}
