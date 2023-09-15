import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import CommentPost from 'flarum/forum/components/CommentPost';
import Link from 'flarum/common/components/Link';
import punctuateSeries from 'flarum/common/helpers/punctuateSeries';
import username from 'flarum/common/helpers/username';
import icon from 'flarum/common/helpers/icon';
import Button from 'flarum/common/components/Button';

import PostLikesModal from './components/PostLikesModal';

export default function () {
  extend(CommentPost.prototype, 'footerItems', function (items) {
    const post = this.attrs.post;
    const likes = post.likes();

    if (likes && likes.length) {
      const limit = 4;
      const overLimit = post.likesCount() > limit;

      // Construct a list of names of users who have liked this post. Make sure the
      // current user is first in the list, and cap a maximum of 4 items.
      const names = likes
        .sort((a) => (a === app.session.user ? -1 : 1))
        .slice(0, overLimit ? limit - 1 : limit)
        .map((user) => {
          return (
            <Link href={app.route.user(user)}>
              {user === app.session.user ? app.translator.trans('flarum-likes.forum.post.you_text') : username(user)}
            </Link>
          );
        });

      // If there are more users that we've run out of room to display, add a "x
      // others" name to the end of the list. Clicking on it will display a modal
      // with a full list of names.
      if (overLimit) {
        const count = post.likesCount() - names.length;
        const label = app.translator.trans('flarum-likes.forum.post.others_link', { count });

        if (app.forum.attribute('canSearchUsers')) {
          names.push(
            <Button
              className="Button Button--ua-reset Button--text"
              onclick={(e) => {
                e.preventDefault();
                app.modal.show(PostLikesModal, { post });
              }}
            >
              {label}
            </Button>
          );
        } else {
          names.push(<span>{label}</span>);
        }
      }

      items.add(
        'liked',
        <div className="Post-likedBy">
          {icon('far fa-thumbs-up')}
          {app.translator.trans(`flarum-likes.forum.post.liked_by${likes[0] === app.session.user ? '_self' : ''}_text`, {
            count: names.length,
            users: punctuateSeries(names),
          })}
        </div>
      );
    }
  });
}
