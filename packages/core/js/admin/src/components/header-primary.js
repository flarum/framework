import Component from 'flarum/component';
import ItemList from 'flarum/utils/item-list';
import listItems from 'flarum/helpers/list-items';

export default class HeaderPrimary extends Component {
  view() {
    return m('ul.header-controls', listItems(this.items().toArray()));
  }

  items() {
    var items = new ItemList();

    return items;
  }
}
