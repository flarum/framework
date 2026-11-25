import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Post from 'flarum/common/models/Post';
import type User from 'flarum/common/models/User';
import m from 'mithril';
import revalidateMentionsPage from './revalidateMentionsPage';

/**
 * One user whose mention count an update changed, as the update response
 * carries it in the `affectedMentionUsers` attribute.
 */
interface AffectedMentionUser {
  id: string | number;
  /** Only present when the reader may see that user's count. */
  mentionCount?: number;
}

/**
 * Reload an open Mentions tab after a post is saved and the save changed the
 * count of the user whose list it holds.
 *
 * Editing, hiding, restoring and approving a post can change who it mentions
 * or whether those mentions count. When that happens the update response names
 * the users whose count changed in the `affectedMentionUsers` attribute, and
 * the reload takes the post out of, or puts it back into, the list that the
 * count describes.
 *
 * A save can also be about something else: liking a post saves `isLiked`,
 * which mentions know nothing about, and an edit that only fixes a typo leaves
 * every mention where it was, so the response names nobody. Reloading then
 * would replace a list the reader has paged through with its first page, so
 * only saves that actually reported a changed count are followed.
 *
 * The users named are not necessarily the user whose tab is open: one edit can
 * add a mention of B to a post that already mentions A, and then only B's list
 * changes. So the hook hands the named users to the reload, which decides
 * whether the tab on screen belongs to one of them.
 *
 * The attribute is read from the response, not from the stored post: the
 * endpoint always sends it on an update, whatever `include` the caller named,
 * and an explicit empty array means no count changed. A server from before the
 * attribute sends the relationship instead; one that sends neither cannot say,
 * and a reload is then the safe answer.
 */
export default function refreshMentionsOnPostSave(): void {
  extend(Post.prototype, 'save', function (result, attributes) {
    if (!('content' in attributes || 'isHidden' in attributes || 'isApproved' in attributes)) return;

    result?.then(
      (post) => {
        const data = post.payload?.data;
        const affected = data?.attributes?.affectedMentionUsers;

        if (Array.isArray(affected)) {
          if (affected.length === 0) return;

          applyMentionCounts(affected as AffectedMentionUser[]);
          revalidateMentionsPage(affected.map((user) => String(user.id)));
          return;
        }

        // Older servers report the same users as a relationship, which a
        // caller that named its own `include` did not get.
        const related = data?.relationships?.affectedMentionUsers?.data;

        if (!Array.isArray(related)) {
          revalidateMentionsPage(null);
          return;
        }

        if (related.length > 0) revalidateMentionsPage(related.map((user) => String(user.id)));
      },
      () => {}
    );
  });
}

/**
 * Give the users already in the store their new counts, so the Mentions tab's
 * badge matches the list after the save.
 *
 * The response can name users the reader has not loaded, and pushing it would
 * create user records holding only an id and a count. Only models that are
 * already in the store are updated.
 */
function applyMentionCounts(affected: AffectedMentionUser[]): void {
  let updated = false;

  for (const { id, mentionCount } of affected) {
    const user = app.store.getById<User>('users', String(id));

    if (user && typeof mentionCount === 'number') {
      user.pushAttributes({ mentionCount });
      updated = true;
    }
  }

  if (updated) m.redraw();
}
