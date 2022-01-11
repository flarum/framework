import app from 'flarum/forum/app';
import Modal from 'flarum/common/components/Modal';
import Link from 'flarum/common/components/Link';
import avatar from 'flarum/common/helpers/avatar';
import username from 'flarum/common/helpers/username';

export default class PostLikesModal extends Modal {
  className() {
    return 'PostLikesModal Modal--small';
  }

  title() {
    return app.translator.trans('flarum-likes.forum.post_likes.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <ul className="PostLikesModal-list">
          {this.attrs.post.likes().map((user) => (
            <li>
              <Link href={app.route.user(user)}>
                {avatar(user)} {username(user)}
              </Link>
            </li>
          ))}
        </ul>
      </div>
    );
  }
}
