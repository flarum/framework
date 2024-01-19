import app from '../../forum/app';
import Component from '../../common/Component';
import Link from '../../common/components/Link';
import UserCard from './UserCard';
import username from '../../common/helpers/username';
import userOnline from '../../common/helpers/userOnline';
import listItems from '../../common/helpers/listItems';
import Avatar from '../../common/components/Avatar';

/**
 * The `PostUser` component shows the avatar and username of a post's author.
 *
 * ### Attrs
 *
 * - `post`
 */
export default class PostUser extends Component {
  view() {
    const post = this.attrs.post;
    const user = post.user();

    if (!user) {
      return (
        <div className="PostUser">
          <h3 className="PostUser-name">
            <Avatar user={user} className="Post-avatar" /> {username(user)}
          </h3>
        </div>
      );
    }

    return (
      <div className="PostUser">
        <h3 className="PostUser-name">
          <Link href={app.route.user(user)}>
            <Avatar user={user} className="Post-avatar" />
            {userOnline(user)}
            {username(user)}
          </Link>
        </h3>
        <ul className="PostUser-badges badges badges--packed">{listItems(user.badges().toArray())}</ul>
      </div>
    );
  }
}
