import Component from '../../common/Component';
import humanTime from '../../common/utils/humanTime';
import ItemList from '../../common/utils/ItemList';
import UserControls from '../utils/UserControls';
import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import icon from '../../common/helpers/icon';
import Dropdown from '../../common/components/Dropdown';
import AvatarEditor from './AvatarEditor';
import listItems from '../../common/helpers/listItems';

/**
 * The `UserCard` component displays a user's profile card. This is used both on
 * the `UserPage` (in the hero) and in discussions, shown when hovering over a
 * post author.
 *
 * ### Props
 *
 * - `user`
 * - `className`
 * - `editable`
 * - `controlsButtonClassName`
 */
export default class UserCard extends Component {
  view() {
    const user = this.props.user;
    const controls = UserControls.controls(user, this).toArray();
    const color = user.color();
    const badges = user.badges().toArray();

    return (
      <div className={'UserCard ' + (this.props.className || '')} style={color ? { backgroundColor: color } : ''}>
        <div className="darkenBackground">
          <div className="container">
            {controls.length
              ? Dropdown.component({
                  children: controls,
                  className: 'UserCard-controls App-primaryControl',
                  menuClassName: 'Dropdown-menu--right',
                  buttonClassName: this.props.controlsButtonClassName,
                  label: app.translator.trans('core.forum.user_controls.button'),
                  icon: 'fas fa-ellipsis-v',
                })
              : ''}

            <div className="UserCard-profile">
              <h2 className="UserCard-identity">
                {this.props.editable ? (
                  [AvatarEditor.component({ user, className: 'UserCard-avatar' }), username(user)]
                ) : (
                  <a href={app.route.user(user)} config={m.route}>
                    <div className="UserCard-avatar">{avatar(user)}</div>
                    {username(user)}
                  </a>
                )}
              </h2>

              {badges.length ? <ul className="UserCard-badges badges">{listItems(badges)}</ul> : ''}

              <ul className="UserCard-info">{listItems(this.infoItems().toArray())}</ul>
            </div>
          </div>
        </div>
      </div>
    );
  }

  /**
   * Build an item list of tidbits of info to show on this user's profile.
   *
   * @return {ItemList}
   */
  infoItems() {
    const items = new ItemList();
    const user = this.props.user;
    const lastSeenAt = user.lastSeenAt();

    if (lastSeenAt) {
      const online = user.isOnline();

      items.add(
        'lastSeen',
        <span className={'UserCard-lastSeen' + (online ? ' online' : '')}>
          {online
            ? [icon('fas fa-circle'), ' ', app.translator.trans('core.forum.user.online_text')]
            : [icon('far fa-clock'), ' ', humanTime(lastSeenAt)]}
        </span>
      );
    }

    items.add('joined', app.translator.trans('core.forum.user.joined_date_text', { ago: humanTime(user.joinTime()) }));

    return items;
  }
}
