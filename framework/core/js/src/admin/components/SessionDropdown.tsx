import app from '../../admin/app';
import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import Dropdown, { IDropdownAttrs } from '../../common/components/Dropdown';
import Button from '../../common/components/Button';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';

export interface ISessionDropdownAttrs extends IDropdownAttrs {}

/**
 * The `SessionDropdown` component shows a button with the current user's
 * avatar/name, with a dropdown of session controls.
 */
export default class SessionDropdown<CustomAttrs extends ISessionDropdownAttrs = ISessionDropdownAttrs> extends Dropdown<CustomAttrs> {
  static initAttrs(attrs: ISessionDropdownAttrs) {
    super.initAttrs(attrs);

    attrs.className = 'SessionDropdown';
    attrs.buttonClassName = 'Button Button--user Button--flat';
    attrs.menuClassName = 'Dropdown-menu--right';
  }

  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    return super.view({ ...vnode, children: this.items().toArray() });
  }

  getButtonContent() {
    const user = app.session.user;

    return [avatar(user), ' ', <span className="Button-label">{username(user)}</span>];
  }

  /**
   * Build an item list for the contents of the dropdown menu.
   */
  items(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'logOut',
      <Button icon="fas fa-sign-out-alt" onclick={app.session.logout.bind(app.session)}>
        {app.translator.trans('core.admin.header.log_out_button')}
      </Button>,
      -100
    );

    return items;
  }
}
