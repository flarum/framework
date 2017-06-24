/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import Component from 'flarum/lib/Component';
import ItemList from 'flarum/lib/utils/ItemList';
import AdminLinkButton from 'flarum/components/AdminLinkButton';
import SelectDropdown from 'flarum/components/SelectDropdown';

function addLink(items, route, icon) {
  items.add(route, <AdminLinkButton
    href={flarum.router.to(route)}
    icon={icon}
    children={flarum.translator.trans(`admin.nav.${route}_button`)}
    description={flarum.translator.trans(`admin.nav.${route}_text`)}/>);
}

/**
 *
 */
export default class Nav extends Component {
  /**
   * @inheritdoc
   */
  view() {
    return <SelectDropdown className="Nav" buttonClassName="Button" children={this.items().toArray()}/>;
  }

  /**
   * Build an item list of links to show in the admin navigation.
   *
   * @return {ItemList}
   * @public
   */
  items() {
    const items = new ItemList();

    addLink(items, 'dashboard', 'bar-chart'));
    addLink(items, 'basics', 'pencil'));
    addLink(items, 'mail', 'envelope'));
    addLink(items, 'permissions', 'key'));
    addLink(items, 'appearance', 'paint-brush'));
    addLink(items, 'extensions', 'puzzle-piece'));

    return items;
  }
}
