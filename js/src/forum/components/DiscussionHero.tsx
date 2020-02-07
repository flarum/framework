import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';
import { DiscussionProp } from '../../common/concerns/ComponentProps';

/**
 * The `DiscussionHero` component displays the hero on a discussion page.
 */
export default class DiscussionHero<T extends DiscussionProp = DiscussionProp> extends Component<T> {
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
     * @return {ItemList}
     */
    items() {
        const items = new ItemList();
        const discussion = this.props.discussion;
        const badges = discussion.badges().toArray();

        if (badges.length) {
            items.add('badges', <ul className="DiscussionHero-badges badges">{listItems(badges)}</ul>, 10);
        }

        items.add('title', <h2 className="DiscussionHero-title">{discussion.title()}</h2>);

        return items;
    }
}
