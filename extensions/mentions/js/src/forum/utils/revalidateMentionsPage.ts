import app from 'flarum/forum/app';
import type { IMentionsPageState } from '../components/MentionsUserPage';

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
 * A null list of users means the response could not say which users changed,
 * because the caller turned the endpoint's default includes off. Reloading is
 * then the safe answer: leaving the list alone could leave it out of step with
 * its count.
 */
export default function revalidateMentionsPage(affectedUserIds: string[] | null): void {
  const mentions: IMentionsPageState | undefined = app.current.get('mentions');

  if (!mentions) return;

  const userId = mentions.user.id();

  if (userId !== undefined && affectedUserIds !== null && !affectedUserIds.includes(userId)) return;

  mentions.posts.revalidate();
}
