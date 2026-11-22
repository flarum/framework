import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Button from 'flarum/common/components/Button';
import CommentPost from 'flarum/forum/components/CommentPost';
import haptic from 'flarum/common/utils/haptic';

const pendingCounts = new WeakMap();

/**
 * Remove the actor's own likeCount from a response before it is parsed.
 *
 * The update endpoint default-includes `likes`, in which the actor sorts first,
 * so nearly every like response carries the actor with the count read for that
 * request. Model.save() pushes the whole payload before this click's count is
 * checked, so a response overtaken by a newer click would write its stale count
 * first. The count is taken from the guarded meta instead.
 */
function withoutActorLikeCount(text, userId) {
  let payload;

  try {
    payload = JSON.parse(text);
  } catch {
    return text;
  }

  const actor = payload?.included?.find((user) => user.type === 'users' && user.id === userId);

  if (!actor?.attributes) return text;

  delete actor.attributes.likeCount;

  return JSON.stringify(payload);
}

export default function () {
  extend(CommentPost.prototype, 'actionItems', function (items) {
    const post = this.attrs.post;
    const user = app.session.user;

    if (!user || post.isHidden() || !post.canLike()) return;

    const likes = post.likes();

    let isLiked = likes && likes.some((liker) => liker === user);

    items.add(
      'like',
      <Button
        className="Button Button--link"
        onclick={() => {
          isLiked = !isLiked;

          if (isLiked) haptic('success');

          // Latest-wins: a response that arrives after a newer click must not
          // overwrite the count that newer mutation returned. The count is also
          // kept out of the payload itself, see withoutActorLikeCount().
          const pending = {};
          pendingCounts.set(user, pending);

          const save = post.save({ isLiked }, { modifyText: (text) => withoutActorLikeCount(text, user.id()) }).then((response) => {
            if (pendingCounts.get(user) !== pending) return;

            const likeCount = response.payload?.meta?.actorLikeCount;

            if (typeof likeCount === 'number') {
              user.pushAttributes({ likeCount });
              m.redraw();
            }
          });

          // We've saved the fact that we do or don't like the post, but in order
          // to provide instantaneous feedback to the user, we'll need to add or
          // remove the like from the relationship data manually. A post loaded
          // without its likes starts from none; the save's response fills them in.
          const relationships = (post.data.relationships ??= {});
          const data = (relationships.likes ??= { data: [] }).data;
          data.some((like, i) => {
            if (like.id === user.id()) {
              data.splice(i, 1);
              return true;
            }
          });

          if (isLiked) {
            data.unshift({ type: 'users', id: user.id() });
          }

          return save;
        }}
      >
        {app.translator.trans(isLiked ? 'flarum-likes.forum.post.unlike_link' : 'flarum-likes.forum.post.like_link')}
      </Button>
    );
  });
}
