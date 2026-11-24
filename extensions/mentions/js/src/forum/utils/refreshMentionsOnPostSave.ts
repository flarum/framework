import { extend } from 'flarum/common/extend';
import Post from 'flarum/common/models/Post';
import revalidateMentionsPage from './revalidateMentionsPage';

/**
 * Reload an open Mentions tab after a post is saved and the save changed a
 * mention count.
 *
 * Editing, hiding, restoring and approving a post can change who it mentions
 * or whether those mentions count. When that happens the update response names
 * the users whose count changed in `affectedMentionUsers`, and the reload takes
 * the post out of, or puts it back into, the list that the count describes.
 *
 * A save can also be about something else: liking a post saves `isLiked`,
 * which mentions know nothing about, and an edit that only fixes a typo leaves
 * every mention where it was, so the response names nobody. Reloading then
 * would replace a list the reader has paged through with its first page, so
 * only saves that actually reported a changed count are followed.
 *
 * The relationship is read from the response, not from the stored post: an
 * explicit empty `affectedMentionUsers` means no count changed, while its
 * absence (a caller that named its own `include` turns the endpoint's default
 * includes off) leaves the response unable to say, and a reload is the safe
 * answer.
 */
export default function refreshMentionsOnPostSave(): void {
  extend(Post.prototype, 'save', function (result, attributes) {
    if (!('content' in attributes || 'isHidden' in attributes || 'isApproved' in attributes)) return;

    result?.then(
      (post) => {
        const affected = post.payload?.data.relationships?.affectedMentionUsers?.data;

        if (!Array.isArray(affected) || affected.length > 0) revalidateMentionsPage();
      },
      () => {}
    );
  });
}
