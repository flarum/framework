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
 */
export default function refreshMentionsOnPostSave(): void {
  extend(Post.prototype, 'save', function (result) {
    result.then(revalidateMentionsPage, () => {});
  });
}
