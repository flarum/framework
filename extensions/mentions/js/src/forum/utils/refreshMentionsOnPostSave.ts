import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Post from 'flarum/common/models/Post';
import type { ApiPayloadSingle } from 'flarum/common/Store';
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
 * The saves that are open together, and the users their responses named.
 *
 * Nothing guarantees that the server processes saves in the order the browser
 * started them, and a reply pushes its side-loaded users into the store before
 * this hook runs. So a count from a response is only used when its save never
 * overlapped another. Once an overlapping batch has drained, the users it
 * named are fetched again, once each, and those counts are applied.
 *
 * `generation` invalidates a refetch still in flight when a newer save starts:
 * the fetched count may predate that save.
 */
let inFlight = 0;
let generation = 0;
let batch: { overlapping: boolean; userIds: Set<string> } = { overlapping: false, userIds: new Set() };

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
 * A new post is a create, and its response has no `affectedMentionUsers`
 * attribute because that is only sent on updates. The Create endpoint does
 * include `mentionsUsers`, though, so the same decision is made from that
 * relationship: no mentioned users means no count changed and the tab is left
 * alone, while a named user may have gained the post.
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

    inFlight++;
    if (inFlight > 1) batch.overlapping = true;
    generation++;

    result?.then(
      (post) => {
        const data = post.payload?.data;

        if (created) {
          // The create response reports the users the new post mentions, with
          // `[]` when it mentions nobody.
          const mentioned = data?.relationships?.mentionsUsers?.data;

          if (!Array.isArray(mentioned)) {
            settleSave(
              [],
              () => {},
              () => revalidateMentionsPage(null)
            );
            return;
          }

          const ids = mentioned.map((user) => String(user.id));

          settleSave(
            ids,
            () => {},
            () => {
              if (ids.length > 0) revalidateMentionsPage(ids);
            }
          );

          return;
        }

        const affected = data?.attributes?.affectedMentionUsers;

        if (Array.isArray(affected)) {
          const users = affected as AffectedMentionUser[];
          const ids = users.map((user) => String(user.id));

          settleSave(
            ids,
            () => applyCountsFromAttribute(users),
            () => {
              if (ids.length > 0) revalidateMentionsPage(ids);
            }
          );

          return;
        }

        // Older servers report the same users as a relationship, which a
        // caller that named its own `include` did not get.
        const related = data?.relationships?.affectedMentionUsers?.data;

        if (!Array.isArray(related)) {
          settleSave(
            [],
            () => {},
            () => revalidateMentionsPage(null)
          );
          return;
        }

        const ids = related.map((user) => String(user.id));

        settleSave(
          ids,
          () => {},
          () => {
            if (ids.length > 0) revalidateMentionsPage(ids);
          }
        );
      },
      () =>
        settleSave(
          [],
          () => {},
          () => {}
        )
    );
  });
}

/**
 * Close one save: remember the users it named, refresh the list as before, and
 * settle the batch when the last save finishes.
 *
 * A non-overlapping save updates the badge straight from its response. In an
 * overlapping batch the responses cannot say which count is newer, so they are
 * applied from the refetch instead, once every save in the batch has settled.
 */
function settleSave(namedIds: string[], applyCounts: () => void, refreshList: () => void): void {
  for (const id of namedIds) batch.userIds.add(id);

  if (!batch.overlapping) applyCounts();

  refreshList();

  inFlight--;

  if (inFlight > 0) return;

  const refetchIds = batch.overlapping ? [...batch.userIds] : [];
  batch = { overlapping: false, userIds: new Set() };

  if (refetchIds.length > 0) refetchMentionCounts(refetchIds, generation);
}

/**
 * Give the users already in the store the counts from a non-overlapping
 * update response.
 *
 * The response can name users the reader has not loaded, and pushing it would
 * create user records holding only an id and a count. Only models that are
 * already in the store are updated. A count is only applied when the actor may
 * read it, which the server decides by leaving it out.
 */
function applyCountsFromAttribute(affected: AffectedMentionUser[]): void {
  let updated = false;

  for (const { id, mentionCount } of affected) {
    const user = app.store.getById<User>('users', String(id));

    if (!user || typeof mentionCount !== 'number') continue;

    user.pushAttributes({ mentionCount });
    updated = true;
  }

  if (updated) m.redraw();
}

/**
 * Fetch the current counts of the users an overlapping batch named, one
 * request per user already in the store, and apply them together.
 *
 * Failed requests are ignored: the stored count stays as it is. Nothing is
 * applied if a newer save started while the requests were in flight, because
 * those counts may predate it.
 */
function refetchMentionCounts(userIds: string[], refetchGeneration: number): void {
  const storedIds = userIds.filter((id) => app.store.getById<User>('users', id) !== undefined);

  if (storedIds.length === 0) return;

  Promise.all(
    storedIds.map((id) =>
      app
        .request<ApiPayloadSingle>({
          method: 'GET',
          url: `${app.forum.attribute('apiUrl')}/users/${id}`,
          errorHandler: () => {},
        })
        .catch(() => null)
    )
  ).then((payloads) => {
    if (generation !== refetchGeneration || inFlight > 0) return;

    let updated = false;

    payloads.forEach((payload, index) => {
      const mentionCount = payload?.data?.attributes?.mentionCount;

      if (typeof mentionCount !== 'number') return;

      const user = app.store.getById<User>('users', storedIds[index]);

      if (!user) return;

      user.pushAttributes({ mentionCount });
      updated = true;
    });

    if (updated) m.redraw();
  });
}
