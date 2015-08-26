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
      children: 'Dashboard',
      description: 'Your forum at a glance.'
    }));

    items.add('basics', AdminLinkButton.component({
      href: app.route('basics'),
      icon: 'pencil',
      children: 'Basics',
      description: 'Set your forum title, language, and other basic settings.'
    }));

    items.add('permissions', AdminLinkButton.component({
      href: app.route('permissions'),
      icon: 'key',
      children: 'Permissions',
      description: 'Configure who can see and do what.'
    }));

    items.add('appearance', AdminLinkButton.component({
      href: app.route('appearance'),
      icon: 'paint-brush',
      children: 'Appearance',
      description: 'Customize your forum\'s colors, logos, and other variables.'
    }));

    items.add('extensions', AdminLinkButton.component({
      href: app.route('extensions'),
      icon: 'puzzle-piece',
      children: 'Extensions',
      description: 'Add extra functionality to your forum and make it your own.'
    }));

    return items;
  }
}
