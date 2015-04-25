import Component from 'flarum/component';
import UserDropdown from 'flarum/components/user-dropdown';
import AdminNavItem from 'flarum/components/admin-nav-item';
import DropdownSelect from 'flarum/components/dropdown-select';

import ItemList from 'flarum/utils/item-list';
import listItems from 'flarum/helpers/list-items';

export default class AdminNav extends Component {
  view() {
    return DropdownSelect.component({ items: this.items().toArray() });
  }

  items() {
    var items = new ItemList();

    items.add('dashboard', AdminNavItem.component({
      href: app.route('dashboard'),
      icon: 'bar-chart',
      label: 'Dashboard',
      description: 'Your forum at a glance.'
    }));

    items.add('basics', AdminNavItem.component({
      href: app.route('basics'),
      icon: 'pencil',
      label: 'Basics',
      description: 'Set your forum title, language, and other basic settings.'
    }));

    items.add('permissions', AdminNavItem.component({
      href: app.route('permissions'),
      icon: 'key',
      label: 'Permissions',
      description: 'Configure who can see and do what.'
    }));

    items.add('appearance', AdminNavItem.component({
      href: app.route('appearance'),
      icon: 'paint-brush',
      label: 'Appearance',
      description: 'Customize your forum\'s colors, logos, and other variables.'
    }));

    items.add('extensions', AdminNavItem.component({
      href: app.route('extensions'),
      icon: 'puzzle-piece',
      label: 'Extensions',
      description: 'Add extra functionality to your forum and make it your own.'
    }));

    return items;
  }
}
