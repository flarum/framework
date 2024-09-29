export default class TagHero extends Component<import("flarum/common/Component").ComponentAttrs, undefined> {
    constructor();
    view(): JSX.Element;
    /**
     * @returns {ItemList}
     */
    viewItems(): ItemList<any>;
    /**
     * @returns {ItemList}
     */
    contentItems(): ItemList<any>;
}
import Component from "flarum/common/Component";
import ItemList from "flarum/common/utils/ItemList";
