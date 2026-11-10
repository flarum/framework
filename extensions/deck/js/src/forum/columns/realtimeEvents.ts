import app from 'flarum/forum/app';
import type Discussion from 'flarum/common/models/Discussion';
import type Model from 'flarum/common/Model';
import type Post from 'flarum/common/models/Post';
import type { DeckRealtimeEvent } from './DeckColumnType';

/** Event names as flarum/realtime and the bundled extensions broadcast them. */
export const STARTED = 'Flarum\\Discussion\\Event\\Started';
export const POSTED = 'Flarum\\Post\\Event\\Posted';
export const REVISED = 'revisedEvent';
export const LIKED = 'likesMutation';
export const RENAMED = 'discussionRenamed';
export const TAGGED = 'taggedEvent';
export const NOTIFICATION = 'notification';
export const POST_HIDDEN = 'postHidden';
export const POST_RESTORED = 'postRestored';
export const DISCUSSION_HIDDEN = 'discussionHidden';
export const DISCUSSION_RESTORED = 'discussionRestored';
/** Something the member could see has been hidden or deleted: `{ data: { type, id } }` only. */
export const REMOVED = 'removed';

/**
 * Events about a post. Realtime sends these as the post's *discussion*, with
 * the post itself appended last to `included`.
 */
export const POST_EVENTS = [POSTED, REVISED, LIKED, POST_HIDDEN, POST_RESTORED];

/** The post an event is about: for post events, the one appended last to `included`. */
export function subjectPost(name: string, payload: any): Post | null {
  if (!POST_EVENTS.includes(name)) return null;

  const included: any[] = payload?.included ?? [];

  for (let i = included.length - 1; i >= 0; i--) {
    if (included[i]?.type === 'posts') return app.store.getById<Post>('posts', String(included[i].id)) ?? null;
  }

  return null;
}

/** A to-one relationship's id straight from the model's data, without needing the related model loaded. */
export function relatedId(model: Model, relationship: string): string | null {
  return (model.data as any)?.relationships?.[relationship]?.data?.id ?? null;
}

export function postOf(event: DeckRealtimeEvent): Post | null {
  return event.post;
}

export function discussionOf(event: DeckRealtimeEvent): Discussion | null {
  return event.discussion;
}
