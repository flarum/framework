import Component from 'flarum/component';
import ItemList from 'flarum/utils/item-list';
import listItems from 'flarum/helpers/list-items';

export default class FooterSecondary extends Component {
  view() {
    return m('ul.footer-controls', listItems(this.items().toArray()));
  }

  items() {
    var items = new ItemList();

    items.add('poweredBy', m('a[href=http://flarum.org][target=_blank]', 'Powered by Flarum'));

    return items;
  }
}
