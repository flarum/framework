import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';

/**
 * The `DiscussionHero` component displays the hero on a discussion page.
 *
 * ### attrs
 *
 * - `discussion`
 */
export default class DiscussionHero extends Component {
  view() {
    return (
      <header className="Hero DiscussionHero">
        <div className="container">
          <ul className="DiscussionHero-items">{listItems(this.items().toArray())}</ul>
        </div>
      </header>
    );
  }

  /**
   * Build an item list for the contents of the discussion hero.
   *
   * @return {ItemList<import('mithril').Children>}
   */
  items() {
    const items = new ItemList();
    const discussion = this.attrs.discussion;
    const badges = discussion.badges().toArray();

    if (badges.length) {
      items.add('badges', <ul className="DiscussionHero-badges badges">{listItems(badges)}</ul>, 10);
    }

    items.add('title', <h2 className="DiscussionHero-title">{discussion.title()}</h2>);

    return items;
  }
}
