import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import deckColumnTypes from '../../../src/forum/columns/deckColumnTypes';
import { PREFERENCE_KEY } from '../../../src/forum/utils/deckLayout';
import type {
  DeckColumnConfig,
  DeckColumnSource,
  DeckColumnType,
  DeckRealtimeEvent,
  DeckRealtimeResult,
} from '../../../src/forum/columns/DeckColumnType';

let booted = false;

export function boot(): void {
  if (booted) return;

  bootstrapForum();
  app.boot();
  booted = true;
}

/** A source that records what it's handed, for column types registered by tests. */
export class RecordingSource implements DeckColumnSource {
  events: DeckRealtimeEvent[] = [];
  result: DeckRealtimeResult = undefined;

  checkForNew = jest.fn(() => Promise.resolve(0));
  applyNew?: () => Promise<number | null>;

  load(): Promise<unknown> {
    return Promise.resolve();
  }

  view() {
    return null;
  }

  showNew(): Promise<unknown> {
    return Promise.resolve();
  }

  onRealtime(event: DeckRealtimeEvent): DeckRealtimeResult {
    this.events.push(event);

    return this.result;
  }
}

const sources = new Map<string, RecordingSource>();

/** The source behind a test column, by column id. */
export const sourceOf = (id: string): RecordingSource => sources.get(id)!;

function testType(available: boolean): DeckColumnType {
  return {
    icon: 'fas fa-vial',
    label: () => 'Test',
    title: () => 'Test',
    isAvailable: () => available,
    createSource: (config: DeckColumnConfig) => {
      const source = new RecordingSource();
      sources.set(config.id, source);

      return source;
    },
  } as unknown as DeckColumnType;
}

/** `test` columns can be shown; `test-hidden` stand in for a disabled extension's. */
export function registerTestTypes(): void {
  if (!deckColumnTypes.has('test')) deckColumnTypes.add('test', testType(true));
  if (!deckColumnTypes.has('test-hidden')) deckColumnTypes.add('test-hidden', testType(false));
}

export const column = (id: string, row = 0, type = 'test'): DeckColumnConfig => ({ id, type, width: 'normal', row, params: {} });

/** Sets the member's stored layout (null for never saved) and stubs out saving it. */
export function setLayout(configs: DeckColumnConfig[] | null) {
  const user = app.session.user!;
  const save = jest.fn((_preferences: Record<string, unknown>) => Promise.resolve(user));

  user.pushAttributes({ preferences: configs ? { [PREFERENCE_KEY]: configs } : {} });
  (user as any).savePreferences = save;

  return save;
}

export const discussionData = (id: string, attributes: Record<string, unknown> = {}, relationships: Record<string, unknown> = {}) => ({
  type: 'discussions',
  id,
  attributes: { title: `Discussion ${id}`, lastPostedAt: '2026-10-01T10:00:00+00:00', ...attributes },
  relationships,
});

export const postData = (id: string, discussionId: string, userId = '2', contentType = 'comment') => ({
  type: 'posts',
  id,
  attributes: { number: Number(id), contentType, createdAt: '2026-10-01T10:00:00+00:00' },
  relationships: {
    discussion: { data: { type: 'discussions', id: discussionId } },
    user: { data: { type: 'users', id: userId } },
  },
});

/** A post event as realtime sends it: the discussion, with the post appended last to `included`. */
export const postEventPayload = (postId: string, discussionId: string, userId = '2', contentType = 'comment') => ({
  data: discussionData(discussionId),
  included: [postData(postId, discussionId, userId, contentType)],
});
