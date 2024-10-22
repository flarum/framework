import app from '../../forum/app';
import Component, { type ComponentAttrs } from '../../common/Component';
import Link from '../../common/components/Link';
import username from '../../common/helpers/username';
import userOnline from '../../common/helpers/userOnline';
import listItems from '../../common/helpers/listItems';
import Avatar from '../../common/components/Avatar';
import type Model from '../../common/Model';
import type Post from '../../common/models/Post';
import type User from '../../common/models/User';

export interface IPostUserAttrs extends ComponentAttrs {
  /** Can be a post or similar model like private message */
  post: Post | (Model & { user: () => User | null | false });
}

/**
 * The `PostUser` component shows the avatar and username of a post's author.
 */
export default class PostUser<CustomAttrs extends IPostUserAttrs = IPostUserAttrs> extends Component<CustomAttrs> {
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
