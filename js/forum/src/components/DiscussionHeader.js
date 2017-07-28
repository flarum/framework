import Component from 'flarum/Component';
import ItemList from 'flarum/utils/ItemList';
import listItems from 'flarum/helpers/listItems';

/**
 * The `DiscussionHeader` component displays the header on a discussion page.
 *
 * ### Props
 *
 * - `discussion`
 */
export default class DiscussionHeader extends Component {
  view() {
    return (
      <header className="DiscussionHeader">
        <ul className="DiscussionHeader-items">{listItems(this.items().toArray())}</ul>
      </header>
    );
  }

  /**
   * Build an item list for the contents of the discussion title.
   *
   * @return {ItemList}
   */
  items() {
    const items = new ItemList();
    const discussion = this.props.discussion;
    const badges = discussion.badges().toArray();

    if (badges.length) {
      items.add('badges', <ul className="DiscussionHeader-badges badges">{listItems(badges)}</ul>, 10);
    }

    items.add('title', <h2 className="DiscussionHeader-title">{discussion.title()}</h2>);

    return items;
  }
}
