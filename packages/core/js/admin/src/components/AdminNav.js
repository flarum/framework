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
        buttonClassName="Button">
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

    items.add('dashboard', AdminLinkButton.component({
      href: app.route('dashboard'),
      icon: 'far fa-chart-bar',
      children: app.translator.trans('core.admin.nav.dashboard_button'),
      description: app.translator.trans('core.admin.nav.dashboard_text')
    }));

    items.add('basics', AdminLinkButton.component({
      href: app.route('basics'),
      icon: 'fa fa-pencil-alt',
      children: app.translator.trans('core.admin.nav.basics_button'),
      description: app.translator.trans('core.admin.nav.basics_text')
    }));

    items.add('mail', AdminLinkButton.component({
      href: app.route('mail'),
      icon: 'fa fa-envelope',
      children: app.translator.trans('core.admin.nav.email_button'),
      description: app.translator.trans('core.admin.nav.email_text')
    }));

    items.add('permissions', AdminLinkButton.component({
      href: app.route('permissions'),
      icon: 'fa fa-key',
      children: app.translator.trans('core.admin.nav.permissions_button'),
      description: app.translator.trans('core.admin.nav.permissions_text')
    }));

    items.add('appearance', AdminLinkButton.component({
      href: app.route('appearance'),
      icon: 'fa fa-paint-brush',
      children: app.translator.trans('core.admin.nav.appearance_button'),
      description: app.translator.trans('core.admin.nav.appearance_text')
    }));

    items.add('extensions', AdminLinkButton.component({
      href: app.route('extensions'),
      icon: 'fa fa-puzzle-piece',
      children: app.translator.trans('core.admin.nav.extensions_button'),
      description: app.translator.trans('core.admin.nav.extensions_text')
    }));

    return items;
  }
}
