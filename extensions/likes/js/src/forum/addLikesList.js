import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import CommentPost from 'flarum/forum/components/CommentPost';
import Link from 'flarum/common/components/Link';
import punctuateSeries from 'flarum/common/helpers/punctuateSeries';
import username from 'flarum/common/helpers/username';
import icon from 'flarum/common/helpers/icon';

import PostLikesModal from './components/PostLikesModal';

export default function () {
  extend(CommentPost.prototype, 'footerItems', function (items) {
    const post = this.attrs.post;
    const likes = post.recentLikes();
    const count = post.likesCount();

    if (likes && likes.length) {
      // the limit is dynamic through the backend, we only load those we need
      const limit = likes.length;
      // overLimit indicates there are more likes than the ones we render (and load)
      const overLimit = count > likes.length;

      // Construct a list of names of users who have liked this post. Make sure the
      // current user is first in the list, and cap a maximum of 4 items.
      const names = likes
        .filter((a) => a !== app.session.user)
        .slice(0, limit)
        .map((user) => {
          return (
            <Link href={app.route.user(user)}>
              {user === app.session.user ? app.translator.trans('flarum-likes.forum.post.you_text') : username(user)}
            </Link>
          );
        });

      if (post.likedByActor()) {
        names.unshift(<Link href={app.route.user(app.session.user)}>{app.translator.trans('flarum-likes.forum.post.you_text')}</Link>);
      }

      // If there are more users that we've run out of room to display, add a "x
      // others" name to the end of the list. Clicking on it will display a modal
      // with a full list of names.
      if (overLimit) {
        names.push(
          <a
            href="#"
            onclick={(e) => {
              e.preventDefault();
              app.modal.show(PostLikesModal, { post });
            }}
          >
            {app.translator.trans('flarum-likes.forum.post.others_link', { count: count - likes.length })}
          </a>
        );
      }

      items.add(
        'liked',
        <div className="Post-likedBy">
          {icon('far fa-thumbs-up')}
          {app.translator.trans('flarum-likes.forum.post.liked_by' + (likes[0] === app.session.user ? '_self' : '') + '_text', {
            count: names.length,
            users: punctuateSeries(names),
          })}
        </div>
      );
    }
  });
}
