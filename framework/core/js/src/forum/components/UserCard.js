import app from '../../forum/app';
import Component from '../../common/Component';
import humanTime from '../../common/utils/humanTime';
import ItemList from '../../common/utils/ItemList';
import UserControls from '../utils/UserControls';
import username from '../../common/helpers/username';
import Dropdown from '../../common/components/Dropdown';
import Link from '../../common/components/Link';
import AvatarEditor from './AvatarEditor';
import listItems from '../../common/helpers/listItems';
import classList from '../../common/utils/classList';
import Icon from '../../common/components/Icon';
import Avatar from '../../common/components/Avatar';

/**
 * The `UserCard` component displays a user's profile card. This is used both on
 * the `UserPage` (in the hero) and in discussions, shown when hovering over a
 * post author.
 *
 * ### Attrs
 *
 * - `user`
 * - `className`
 * - `editable`
 * - `controlsButtonClassName`
 */
export default class UserCard extends Component {
  view() {
    const user = this.attrs.user;
    const color = user.color();

    return (
      <div className={classList('UserCard', this.attrs.className)} style={color && !process.env.testing && { '--usercard-bg': color }}>
        <div className="darkenBackground">
          <div className="container">
            <div className="UserCard-profile">{this.profileItems().toArray()}</div>
            <div className="UserCard-controls">{this.controlsItems().toArray()}</div>
          </div>
        </div>
      </div>
    );
  }

  profileItems() {
    const items = new ItemList();

    items.add('avatar', this.avatar(), 100);
    items.add('content', this.content(), 10);

    return items;
  }

  avatar() {
    const user = this.attrs.user;

    return this.attrs.editable ? (
      <AvatarEditor user={user} className="UserCard-avatar" />
    ) : (
      <Link href={app.route.user(user)}>
        <div className="UserCard-avatar">
          <Avatar user={user} loading="eager" />
        </div>
      </Link>
    );
  }

  content() {
    return <div className="UserCard-content">{this.contentItems().toArray()}</div>;
  }

  contentItems() {
    const items = new ItemList();

    const user = this.attrs.user;
    const badges = user.badges().toArray();

    items.add('identity', <h1 className="UserCard-identity">{username(user)}</h1>, 100);

    if (badges.length) {
      items.add('badges', <ul className="UserCard-badges badges">{listItems(badges)}</ul>, 90);
    }

    items.add('info', <ul className="UserCard-info">{listItems(this.infoItems().toArray())}</ul>, 80);

    return items;
  }

  /**
   * Build an item list of tidbits of info to show on this user's profile.
   *
   * @return {ItemList<import('mithril').Children>}
   */
  infoItems() {
    const items = new ItemList();
    const user = this.attrs.user;
    const lastSeenAt = user.lastSeenAt();

    if (lastSeenAt) {
      const online = user.isOnline();

      items.add(
        'lastSeen',
        <span className={classList('UserCard-lastSeen', { online })}>
          {online
            ? [<Icon name={'fas fa-circle'} />, ' ', app.translator.trans('core.forum.user.online_text')]
            : [<Icon name={'far fa-clock'} />, ' ', humanTime(lastSeenAt)]}
        </span>,
        100
      );
    }

    items.add('joined', app.translator.trans('core.forum.user.joined_date_text', { ago: humanTime(user.joinTime()) }), 90);

    return items;
  }

  controlsItems() {
    const items = new ItemList();

    const user = this.attrs.user;
    const controls = UserControls.controls(user, this).toArray();

    if (controls.length) {
      items.add(
        'controls',
        <Dropdown
          className="App-primaryControl"
          menuClassName="Dropdown-menu--right"
          buttonClassName={this.attrs.controlsButtonClassName}
          label={app.translator.trans('core.forum.user_controls.button')}
          accessibleToggleLabel={app.translator.trans('core.forum.user_controls.toggle_dropdown_accessible_label')}
          icon="fas fa-ellipsis-v"
        >
          {controls}
        </Dropdown>,
        100
      );
    }

    return items;
  }
}
