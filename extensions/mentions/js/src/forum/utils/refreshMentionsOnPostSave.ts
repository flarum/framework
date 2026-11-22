import { extend } from 'flarum/common/extend';
import Post from 'flarum/common/models/Post';
import revalidateMentionsPage from './revalidateMentionsPage';

/**
 * Reload an open Mentions tab after a post is saved.
 *
 * Editing, hiding, restoring and approving a post change who it mentions or
 * whether those mentions count. The save response carries the mentioned
 * users' new counts, and the reload takes the post out of, or puts it back
 * into, the list that the count describes.
 *
 * A save can also be about something else: liking a post saves `isLiked`,
 * which mentions know nothing about. Reloading then would replace a list the
 * reader has paged through with its first page, so only saves that carry one
 * of the attributes above are followed.
 */
export default function refreshMentionsOnPostSave(): void {
  extend(Post.prototype, 'save', function (result, attributes) {
    if (!('content' in attributes || 'isHidden' in attributes || 'isApproved' in attributes)) return;

    result?.then(revalidateMentionsPage, () => {});
  });
}
