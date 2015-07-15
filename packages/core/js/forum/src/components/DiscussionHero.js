import Component from 'flarum/Component';
import ItemList from 'flarum/utils/ItemList';
import listItems from 'flarum/helpers/listItems';

/**
 * The `DiscussionHero` component displays the hero on a discussion page.
 *
 * ### Props
 *
 * - `discussion`
 */
export default class DiscussionHero extends Component {
  view() {
    return (
      <header className="hero discussion-hero">
        <div className="container">
          <ul className="discussion-hero-items">{listItems(this.items().toArray())}</ul>
        </div>
      </header>
    );
  }

  /**
   * Build an item list for the contents of the discussion hero.
   *
   * @return {ItemList}
   */
  items() {
    const items = new ItemList();
    const discussion = this.props.discussion;
    const badges = discussion.badges().toArray();

    if (badges.length) {
      items.add('badges', <ul className="badges">{listItems(badges)}</ul>);
    }

    items.add('title', <h2 className="discussion-title">{discussion.title()}</h2>);

    return items;
  }
}
