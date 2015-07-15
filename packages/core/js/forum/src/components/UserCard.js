import Component from 'flarum/Component';
import humanTime from 'flarum/utils/humanTime';
import ItemList from 'flarum/utils/ItemList';
import UserControls from 'flarum/utils/UserControls';
import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';
import icon from 'flarum/helpers/icon';
import Dropdown from 'flarum/components/Dropdown';
import UserBio from 'flarum/components/UserBio';
import AvatarEditor from 'flarum/components/AvatarEditor';
import listItems from 'flarum/helpers/listItems';

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

    return (
      <div className={'user-card ' + (this.props.className || '')}
        style={{backgroundColor: user.color()}}>
        <div className="darken-overlay"/>

        <div className="container">
          {controls.length ? Dropdown.component({
            children: controls,
            className: 'contextual-controls',
            menuClass: 'dropdown-menu-right',
            buttonClass: this.props.controlsButtonClassName
          }) : ''}

          <div className="user-profile">
            <h2 className="user-identity">
              {this.props.editable
                ? [AvatarEditor.component({user, className: 'user-avatar'}), username(user)]
                : (
                  <a href={app.route.user(user)} config={m.route}>
                    {avatar(user, {className: 'user-avatar'})}
                    {username(user)}
                  </a>
                )}
            </h2>

            <ul className="badges user-badges">{listItems(user.badges().toArray())}</ul>
            <ul className="user-info">{listItems(this.infoItems().toArray())}</ul>
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
    const lastSeenTime = user.lastSeenTime();

    items.add('bio',
      UserBio.component({
        user,
        editable: this.props.editable
      })
    );

    if (lastSeenTime) {
      const online = user.isOnline();

      items.add('lastSeen', (
        <span className={'user-last-seen' + (online ? ' online' : '')}>
          {online
            ? [icon('circle'), ' Online']
            : [icon('clock-o'), ' ', humanTime(lastSeenTime)]}
        </span>
      ));
    }

    items.add('joined', ['Joined ', humanTime(user.joinTime())]);

    return items;
  }
}
