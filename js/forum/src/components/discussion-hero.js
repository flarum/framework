import Component from 'flarum/component';
import ItemList from 'flarum/utils/item-list';
import listItems from 'flarum/helpers/list-items';

export default class DiscussionHero extends Component {
  view() {
    var discussion = this.props.discussion;

    return m('header.hero.discussion-hero', [
      m('div.container', m('ul.discussion-hero-items', listItems(this.items().toArray())))
    ]);
  }

  items() {
    var items = new ItemList();
    var discussion = this.props.discussion;

    var badges = discussion.badges().toArray();
    if (badges.length) {
      items.add('badges', m('ul.badges', listItems(badges)));
    }

    items.add('title', m('h2.discussion-title', discussion.title()));

    return items;
  }
}
