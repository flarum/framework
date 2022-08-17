import app from '../../forum/app';
import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import Dropdown, { IDropdownAttrs } from '../../common/components/Dropdown';
import LinkButton from '../../common/components/LinkButton';
import Button from '../../common/components/Button';
import ItemList from '../../common/utils/ItemList';
import Separator from '../../common/components/Separator';
import extractText from '../../common/utils/extractText';
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

    attrs.accessibleToggleLabel = extractText(app.translator.trans('core.forum.header.session_dropdown_accessible_label'));
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
    const user = app.session.user!;

    items.add(
      'profile',
      LinkButton.component(
        {
          icon: 'fas fa-user',
          href: app.route.user(user),
        },
        app.translator.trans('core.forum.header.profile_button')
      ),
      100
    );

    items.add(
      'settings',
      LinkButton.component(
        {
          icon: 'fas fa-cog',
          href: app.route('settings'),
        },
        app.translator.trans('core.forum.header.settings_button')
      ),
      50
    );

    items.add(
      'security',
      LinkButton.component(
        {
          icon: 'fas fa-shield-alt',
          href: app.route('user.security', { username: user.slug() }),
        },
        app.translator.trans('core.forum.header.security_button')
      ),
      25
    );

    if (app.forum.attribute('adminUrl')) {
      items.add(
        'administration',
        LinkButton.component(
          {
            icon: 'fas fa-wrench',
            href: app.forum.attribute('adminUrl'),
            target: '_blank',
          },
          app.translator.trans('core.forum.header.admin_button')
        ),
        0
      );
    }

    items.add('separator', Separator.component(), -90);

    items.add(
      'logOut',
      Button.component(
        {
          icon: 'fas fa-sign-out-alt',
          onclick: app.session.logout.bind(app.session),
        },
        app.translator.trans('core.forum.header.log_out_button')
      ),
      -100
    );

    return items;
  }
}
