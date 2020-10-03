import Modal from 'flarum/components/Modal';
import Link from 'flarum/components/Link';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';

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
          {this.attrs.post.likes().map(user => (
            <li>
              <Link href={app.route.user(user)}>
                {avatar(user)} {' '}
                {username(user)}
              </Link>
            </li>
          ))}
        </ul>
      </div>
    );
  }
}
