import app from 'flarum/forum/app';
import type Discussion from 'flarum/common/models/Discussion';
import type PostStreamState from 'flarum/forum/states/PostStreamState';

/**
 * Reloads a discussion after realtime activity in it and, if the reader was at
 * the end, brings the new posts into view.
 *
 * Resolves with whether it caught up, never rejecting. A failed request has
 * shown its own error, and the posts on screen stay as they are until the next
 * event or reconnect catches up again.
 *
 * @param wasViewingEnd Whether the reader was at the end before the activity
 *                      reached the store; see #4717.
 */
export default function catchUpDiscussion(discussion: Discussion, stream: PostStreamState, wasViewingEnd: boolean): Promise<boolean> {
  return app.store
    .find<Discussion>('discussions', discussion.id() as string)
    .then(() => (wasViewingEnd ? stream.syncEnd() : undefined))
    .then(() => {
      m.redraw();

      return true;
    })
    .catch(() => false);
}
