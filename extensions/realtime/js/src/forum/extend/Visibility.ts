import app from 'flarum/forum/app';
import type Discussion from 'flarum/common/models/Discussion';
import type Post from 'flarum/common/models/Post';
import RealtimeState from '../RealtimeState';

const REMOVED = 'removed';
const HIDDEN_EVENTS = ['postHidden', 'discussionHidden'];

interface RemovalPayload {
  data: { type: 'posts' | 'discussions'; id: string };
  meta?: { discussionId?: string | null };
}

/**
 * Hidden, deleted and restored content, live. The server sends `removed` only
 * to members who could see the item and can't now, on their own channel; the
 * public channel's copy is for guests. Members who can still see it (e.g.
 * moderators, for a hidden post) get its new state instead.
 */
export default function Visibility(): void {
  RealtimeState.onUserChannelReady((channel: any) => bind(channel, false));
  RealtimeState.onPublicChannelReady((channel: any) => bind(channel, true));
}

function bind(channel: any, isPublic: boolean): void {
  channel.bind(REMOVED, (data: RemovalPayload) => {
    if (isPublic && app.session.user) return;

    remove(data);
  });

  for (const event of HIDDEN_EVENTS) {
    channel.bind(event, (data: unknown) => {
      app.store.pushPayload(data as any);
      m.redraw();
    });
  }
}

/** As if this member had deleted it themselves: the same calls core's controls make. */
function remove({ data, meta }: RemovalPayload): void {
  if (data.type === 'posts') {
    const post = app.store.getById<Post>('posts', data.id);
    const discussion = (post && post.discussion()) || (meta?.discussionId ? app.store.getById<Discussion>('discussions', meta.discussionId) : null);

    discussion?.removePost(data.id);
  }

  if (data.type === 'discussions') {
    const discussion = app.store.getById<Discussion>('discussions', data.id);

    if (discussion) {
      app.discussions.removeDiscussion(discussion);

      if (app.viewingDiscussion(discussion)) {
        app.alerts.show({ type: 'error' }, app.translator.trans('flarum-realtime.forum.discussion-removed'));
        app.history.back();
      }
    }
  }

  m.redraw();
}
