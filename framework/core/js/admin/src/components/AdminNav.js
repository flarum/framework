/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import Component from 'flarum/Component';
import AdminLinkButton from 'flarum/components/AdminLinkButton';
import SelectDropdown from 'flarum/components/SelectDropdown';

import ItemList from 'flarum/utils/ItemList';

export default class AdminNav extends Component {
  view() {
    return (
      <SelectDropdown
        className="AdminNav App-titleControl"
        buttonClassName="Button"
        children={this.items().toArray()}
        />
    );
  }

  /**
   * Build an item list of links to show in the admin navigation.
   *
   * @return {ItemList}
   */
  items() {
    const items = new ItemList();

    items.add('dashboard', AdminLinkButton.component({
      href: app.route('dashboard'),
      icon: 'bar-chart',
      children: app.trans('core.admin.nav_dashboard_button'),
      description: app.trans('core.admin.nav_dashboard_text')
    }));

    items.add('basics', AdminLinkButton.component({
      href: app.route('basics'),
      icon: 'pencil',
      children: app.trans('core.admin.nav_basics_button'),
      description: app.trans('core.admin.nav_basics_text')
    }));

    items.add('permissions', AdminLinkButton.component({
      href: app.route('permissions'),
      icon: 'key',
      children: app.trans('core.admin.nav_permissions_button'),
      description: app.trans('core.admin.nav_permissions_text')
    }));

    items.add('appearance', AdminLinkButton.component({
      href: app.route('appearance'),
      icon: 'paint-brush',
      children: app.trans('core.admin.nav_appearance_button'),
      description: app.trans('core.admin.nav_appearance_text')
    }));

    items.add('extensions', AdminLinkButton.component({
      href: app.route('extensions'),
      icon: 'puzzle-piece',
      children: app.trans('core.admin.nav_extensions_button'),
      description: app.trans('core.admin.nav_extensions_text')
    }));

    return items;
  }
}
