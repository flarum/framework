import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Button from 'flarum/common/components/Button';
import CommentPost from 'flarum/forum/components/CommentPost';
import haptic from 'flarum/common/utils/haptic';

const pendingRefreshes = new WeakMap();

function refreshLikeCount(user) {
  const refresh = {};
  pendingRefreshes.set(user, refresh);

  // Store.find merges automatically, so inspect the response before updating the user.
  return app
    .request({
      url: app.forum.attribute('apiUrl') + '/users/' + user.id(),
      params: { fields: { users: 'likeCount' } },
    })
    .then(({ data }) => {
      if (pendingRefreshes.get(user) === refresh) {
        user.pushAttributes({ likeCount: data.attributes.likeCount });
        m.redraw();
      }
    });
}

function withoutActorLikeCount(text, userId) {
  let payload;
  try {
    payload = JSON.parse(text);
  } catch {
    return text;
  }

  const actor = payload?.included?.find((item) => item.type === 'users' && item.id === userId);
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

          // A mutation may also include the actor among the likers.
          const save = post.save({ isLiked }, { modifyText: (text) => withoutActorLikeCount(text, user.id()) }).then(() => refreshLikeCount(user));

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
