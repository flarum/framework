import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import Dropdown from '../../common/components/Dropdown';
import Button from '../../common/components/Button';
import ItemList from '../../common/utils/ItemList';

/**
 * The `SessionDropdown` component shows a button with the current user's
 * avatar/name, with a dropdown of session controls.
 */
export default class SessionDropdown extends Dropdown {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.className = 'SessionDropdown';
    attrs.buttonClassName = 'Button Button--user Button--flat';
    attrs.menuClassName = 'Dropdown-menu--right';
  }

  view(vnode) {
    return super.view({ ...vnode, children: this.items().toArray() });
  }

  getButtonContent() {
    const user = app.session.user;

    return [avatar(user), ' ', <span className="Button-label">{username(user)}</span>];
  }

  /**
   * Build an item list for the contents of the dropdown menu.
   *
   * @return {ItemList}
   */
  items() {
    const items = new ItemList();

    items.add(
      'logOut',
      Button.component(
        {
          icon: 'fas fa-sign-out-alt',
          onclick: app.session.logout.bind(app.session),
        },
        app.translator.trans('core.admin.header.log_out_button')
      ),
      -100
    );

    return items;
  }
}
