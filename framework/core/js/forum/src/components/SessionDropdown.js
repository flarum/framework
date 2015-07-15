import avatar from 'flarum/helpers/avatar';
import username from 'flarum/helpers/username';
import Dropdown from 'flarum/components/Dropdown';
import Button from 'flarum/components/Button';
import ItemList from 'flarum/utils/ItemList';
import Separator from 'flarum/components/Separator';
import Group from 'flarum/models/Group';

/**
 * The `SessionDropdown` component shows a button with the current user's
 * avatar/name, with a dropdown of session controls.
 */
export default class SessionDropdown extends Dropdown {
  static initProps(props) {
    super.initProps(props);

    props.buttonClassName = 'btn btn-default btn-naked btn-rounded btn-user';
    props.menuClassName = 'dropdown-menu-right';
  }

  view() {
    this.props.children = this.items().toArray();

    return super.view();
  }

  getButtonContent() {
    const user = app.session.user;

    return [
      avatar(user), ' ',
      <span className="label">{username(user)}</span>
    ];
  }

  /**
   * Build an item list for the contents of the dropdown menu.
   *
   * @return {ItemList}
   */
  items() {
    const items = new ItemList();
    const user = app.session.user;

    items.add('profile',
      Button.component({
        icon: 'user',
        children: 'Profile',
        href: app.route.user(user),
        config: m.route
      }),
      100
    );

    items.add('settings',
      Button.component({
        icon: 'cog',
        children: 'Settings',
        href: app.route('settings'),
        config: m.route
      }),
      50
    );

    if (user.groups().some(group => Number(group.id()) === Group.ADMINISTRATOR_ID)) {
      items.add('administration',
        Button.component({
          icon: 'wrench',
          children: 'Administration',
          href: app.forum.attribute('baseUrl') + '/admin',
          target: '_blank'
        }),
        0
      );
    }

    items.add('separator', Separator.component(), -90);

    items.add('logOut',
      Button.component({
        icon: 'sign-out',
        children: 'Log Out',
        onclick: app.session.logout.bind(app.session)
      }),
      -100
    );

    return items;
  }
}
