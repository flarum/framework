import app from 'flarum/forum/app';
import type PostListState from 'flarum/forum/states/PostListState';
import type { IMentionsPageState } from '../components/MentionsUserPage';

/**
 * The refresh this module started for a list, and the lists with one more
 * refresh waiting for it to finish.
 *
 * `PaginatedListState.revalidate()` hands back the request already in flight
 * instead of starting a second one, so a save that lands during a refresh
 * would otherwise reuse a response that predates it and leave the list out of
 * step with the save. Keeping the in-flight request here lets the mentions code
 * run one more refresh when it settles, without changing core.
 */
const refreshing = new WeakMap<PostListState, Promise<void>>();
const refreshAgain = new WeakSet<PostListState>();

function refreshMentionsList(posts: PostListState): void {
  if (refreshing.has(posts)) {
    refreshAgain.add(posts);
    return;
  }

  const request = posts.revalidate();

  refreshing.set(posts, request);

  request.finally(() => {
    refreshing.delete(posts);

    if (refreshAgain.delete(posts)) refreshMentionsList(posts);
  });
}

/**
 * Reload the Mentions tab on screen, if there is one and the save changed the
 * count of the user whose list it holds.
 *
 * A save can change the mentions of a user whose tab is not open, or of one
 * user and not another: editing a post that mentions both A and B leaves A's
 * list as it was while B's gains the post. Reloading a list the save cannot
 * have changed would replace the pages the reader has loaded with the first
 * one, so only a save that names this tab's user in `affectedMentionUsers`
 * reloads it.
 *
 * A null list of users means the response could not say which users changed
 * (an older server sends no attribute). Reloading is then the safe answer:
 * leaving the list alone could leave it out of step with its count.
 *
 * A save that lands while the list is already refreshing queues one more
 * refresh, because the in-flight request was sent before the save could have
 * changed anything.
 */
export default function revalidateMentionsPage(affectedUserIds: string[] | null): void {
  const mentions: IMentionsPageState | undefined = app.current.get('mentions');

  if (!mentions) return;

  const userId = mentions.user.id();

  if (userId !== undefined && affectedUserIds !== null && !affectedUserIds.includes(userId)) return;

  refreshMentionsList(mentions.posts);
}
