import Component, { type ComponentAttrs } from '../../common/Component';
import User from '../../common/models/User';
import Link from '../../common/components/Link';
import app from '../app';
import Avatar from '../../common/components/Avatar';
import listItems from '../../common/helpers/listItems';
import username from '../../common/helpers/username';
import highlight from '../../common/helpers/highlight';
import classList from '../../common/utils/classList';
import type Mithril from 'mithril';
import type ForumApplication from '../../forum/ForumApplication';

export interface IUserSearchResultAttrs extends ComponentAttrs {
  user: User;
  onclick?: (user: User) => void;
  query: string;
}

export default class UserSearchResult<CustomAttrs extends IUserSearchResultAttrs = IUserSearchResultAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const user = this.attrs.user;

    return (
      <li
        className={classList('UserSearchResult', this.attrs.className)}
        data-index={'users' + user.id()}
        data-id={user.id()}
        onclick={this.attrs.onclick}
      >
        {this.attrs.onclick ? (
          <button type="button">{this.content(vnode)}</button>
        ) : (
          <Link
            href={
              'user' in app.route ? (app as unknown as ForumApplication).route.user(user) : app.forum.attribute('baseUrl') + '/u/' + user.username()
            }
          >
            {this.content(vnode)}
          </Link>
        )}
      </li>
    );
  }

  content(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const user = this.attrs.user;
    const query = this.attrs.query;
    const name = username(user, (name: string) => highlight(name, query));

    return (
      <>
        <Avatar user={user} />
        <div className="UserSearchResult-name">
          {name}
          <div className="badges badges--packed">{listItems(user.badges().toArray())}</div>
        </div>
        {vnode.children}
      </>
    );
  }
}
