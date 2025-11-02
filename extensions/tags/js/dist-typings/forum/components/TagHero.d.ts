export default class TagHero extends Component<import("flarum/common/Component").ComponentAttrs, undefined> {
    constructor();
    view(): JSX.Element;
    /**
     * @returns {ItemList<Mithril.Children>}
     */
    viewItems(): ItemList<Mithril.Children>;
    /**
     * @returns {ItemList<Mithril.Children>}
     */
    contentItems(): ItemList<Mithril.Children>;
}
import Component from "flarum/common/Component";
import ItemList from "flarum/common/utils/ItemList";
import Mithril from "mithril";
