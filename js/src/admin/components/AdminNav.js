/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import Component from '../../common/Component';
import AdminLinkButton from './AdminLinkButton';
import SelectDropdown from '../../common/components/SelectDropdown';
import ItemList from '../../common/utils/ItemList';

export default class AdminNav extends Component {
  view() {
    return (
      <SelectDropdown className="AdminNav App-titleControl" buttonClassName="Button">
        {this.items().toArray()}
      </SelectDropdown>
    );
  }

  /**
   * Build an item list of links to show in the admin navigation.
   *
   * @return {ItemList}
   */
  items() {
    const items = new ItemList();

    items.add(
      'dashboard',
      AdminLinkButton.component(
        {
          href: app.route('dashboard'),
          icon: 'far fa-chart-bar',
          description: app.translator.trans('core.admin.nav.dashboard_text'),
        },
        app.translator.trans('core.admin.nav.dashboard_button')
      )
    );

    items.add(
      'basics',
      AdminLinkButton.component(
        {
          href: app.route('basics'),
          icon: 'fas fa-pencil-alt',
          description: app.translator.trans('core.admin.nav.basics_text'),
        },
        app.translator.trans('core.admin.nav.basics_button')
      )
    );

    items.add(
      'mail',
      AdminLinkButton.component(
        {
          href: app.route('mail'),
          icon: 'fas fa-envelope',
          description: app.translator.trans('core.admin.nav.email_text'),
        },
        app.translator.trans('core.admin.nav.email_button')
      )
    );

    items.add(
      'permissions',
      AdminLinkButton.component(
        {
          href: app.route('permissions'),
          icon: 'fas fa-key',
          description: app.translator.trans('core.admin.nav.permissions_text'),
        },
        app.translator.trans('core.admin.nav.permissions_button')
      )
    );

    items.add(
      'appearance',
      AdminLinkButton.component(
        {
          href: app.route('appearance'),
          icon: 'fas fa-paint-brush',
          description: app.translator.trans('core.admin.nav.appearance_text'),
        },
        app.translator.trans('core.admin.nav.appearance_button')
      )
    );

    items.add(
      'extensions',
      AdminLinkButton.component(
        {
          href: app.route('extensions'),
          icon: 'fas fa-puzzle-piece',
          description: app.translator.trans('core.admin.nav.extensions_text'),
        },
        app.translator.trans('core.admin.nav.extensions_button')
      )
    );

    return items;
  }
}
