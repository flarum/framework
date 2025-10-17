export default class TagHero {
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
import Mithril from "mithril";
