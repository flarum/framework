import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import Dropdown from '../../common/components/Dropdown';
import LinkButton from '../../common/components/LinkButton';
import Button from '../../common/components/Button';
import ItemList from '../../common/utils/ItemList';
import Separator from '../../common/components/Separator';
import Group from '../../common/models/Group';

/**
 * The `SessionDropdown` component shows a button with the current user's
 * avatar/name, with a dropdown of session controls.
 */
export default class SessionDropdown extends Dropdown {
  static initProps(props) {
    super.initProps(props);

    props.className = 'SessionDropdown';
    props.buttonClassName = 'Button Button--user Button--flat';
    props.menuClassName = 'Dropdown-menu--right';
  }

  view() {
    this.props.children = this.items().toArray();

    return super.view();
  }

  getButtonContent() {
    const user = app.session.user;

    return [
      avatar(user), ' ',
      <span className="Button-label">{username(user)}</span>
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
      LinkButton.component({
        icon: 'fas fa-user',
        children: app.translator.trans('core.forum.header.profile_button'),
        href: app.route.user(user)
      }),
      100
    );

    items.add('settings',
      LinkButton.component({
        icon: 'fas fa-cog',
        children: app.translator.trans('core.forum.header.settings_button'),
        href: app.route('settings')
      }),
      50
    );

    if (app.forum.attribute('adminUrl')) {
      items.add('administration',
        LinkButton.component({
          icon: 'fas fa-wrench',
          children: app.translator.trans('core.forum.header.admin_button'),
          href: app.forum.attribute('adminUrl'),
          target: '_blank',
          config: () => {}
        }),
        0
      );
    }

    items.add('separator', Separator.component(), -90);

    items.add('logOut',
      Button.component({
        icon: 'fas fa-sign-out-alt',
        children: app.translator.trans('core.forum.header.log_out_button'),
        onclick: app.session.logout.bind(app.session)
      }),
      -100
    );

    return items;
  }
}
