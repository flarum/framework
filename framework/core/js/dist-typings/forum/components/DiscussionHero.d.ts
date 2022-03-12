/**
 * The `DiscussionHero` component displays the hero on a discussion page.
 *
 * ### attrs
 *
 * - `discussion`
 */
export default class DiscussionHero extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    view(): JSX.Element;
    /**
     * Build an item list for the contents of the discussion hero.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    items(): ItemList<import('mithril').Children>;
}
import Component from "../../common/Component";
import ItemList from "../../common/utils/ItemList";
