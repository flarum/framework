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
 * The order saves were started in, and the last save whose count has been
 * applied to each user.
 *
 * Responses can arrive out of order, so a slow save must not write an older
 * count over one a later save already applied. Each save takes its number
 * before its response, and a count is skipped when a later save has already
 * written that user's count.
 */
let saveSequence = 0;
const lastAppliedCount = new WeakMap<User, number>();

/**
 * Reload an open Mentions tab after a post is saved and the save changed the
 * count of the user whose list it holds.
 *
 * Editing, hiding, restoring and approving a post can change who it mentions
 * or whether those mentions count. When that happens the update response names
 * the users whose count changed in the `affectedMentionUsers` attribute, and
 * the reload takes the post out of, or puts it back into, the list that the
 * count describes. A slow response cannot roll a badge back: counts are
 * applied in the order their saves started.
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
 * A new post is a create, and its response has no `affectedMentionUsers`
 * attribute because that is only sent on updates. The Create endpoint does
 * include `mentionsUsers`, though, so the same decision is made from that
 * relationship: no mentioned users means no count changed and the tab is left
 * alone, while a named user may have gained the post. The counts need no work
 * here, because the side-loaded users are already pushed into the store by the
 * save itself.
 *
 * The attribute is read from the response, not from the stored post: the
 * endpoint always sends it on an update, whatever `include` the caller named,
 * and an explicit empty array means no count changed. A server from before the
 * attribute sends the relationship instead; one that sends neither cannot say,
 * and a reload is then the safe answer. A sparse fieldset such as
 * `fields[posts]=content` can omit it too, and that conservative reload is
 * accepted for now: Flarum 2 has no extender for resource meta, and adding one
 * is core work rather than part of this change.
 */
export default function refreshMentionsOnPostSave(): void {
  extend(Post.prototype, 'save', function (result, attributes) {
    if (!('content' in attributes || 'isHidden' in attributes || 'isApproved' in attributes)) return;

    // `exists` is still false here for a new post: the save only marks it as
    // existing when the response is pushed into the store.
    const created = !this.exists;
    const sequence = ++saveSequence;

    result?.then(
      (post) => {
        const data = post.payload?.data;

        if (created) {
          // The create response reports the users the new post mentions, with
          // `[]` when it mentions nobody.
          const mentioned = data?.relationships?.mentionsUsers?.data;

          if (Array.isArray(mentioned)) {
            if (mentioned.length > 0) revalidateMentionsPage(mentioned.map((user) => String(user.id)));
            return;
          }

          revalidateMentionsPage(null);
          return;
        }

        const affected = data?.attributes?.affectedMentionUsers;

        if (Array.isArray(affected)) {
          if (affected.length === 0) return;

          applyMentionCounts(affected as AffectedMentionUser[], sequence);
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
 * already in the store are updated. A count is skipped when a later save has
 * already written the same user's count, so a slow response cannot roll the
 * badge back.
 */
function applyMentionCounts(affected: AffectedMentionUser[], sequence: number): void {
  let updated = false;

  for (const { id, mentionCount } of affected) {
    const user = app.store.getById<User>('users', String(id));

    if (!user || typeof mentionCount !== 'number') continue;

    if ((lastAppliedCount.get(user) ?? 0) > sequence) continue;

    lastAppliedCount.set(user, sequence);
    user.pushAttributes({ mentionCount });
    updated = true;
  }

  if (updated) m.redraw();
}
