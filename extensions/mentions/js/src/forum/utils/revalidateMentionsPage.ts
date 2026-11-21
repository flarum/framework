import app from 'flarum/forum/app';

/**
 * Reload the Mentions tab on screen, if there is one.
 *
 * The posts a Mentions tab lists follow post visibility, and the count beside
 * it is the mentioned user's. Saving a post (editing, hiding, restoring or
 * approving) can change either, so the list is reloaded to keep the two in
 * step with each other.
 */
export default function revalidateMentionsPage(): void {
  app.current.get('mentions')?.revalidate();
}
