import Component from 'flarum/component';
import UserDropdown from 'flarum/components/user-dropdown';

import ItemList from 'flarum/utils/item-list';
import listItems from 'flarum/helpers/list-items';

export default class HeaderSecondary extends Component {
  view() {
    return m('ul.header-controls', listItems(this.items().toArray()));
  }

  items() {
    var items = new ItemList();

    items.add('user', UserDropdown.component({ user: app.session.user() }));

    return items;
  }
}
