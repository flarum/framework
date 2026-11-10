import Component, { ComponentAttrs } from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
export interface IHeroAttrs extends ComponentAttrs {
}
export default abstract class Hero<CustomAttrs extends IHeroAttrs = IHeroAttrs> extends Component<CustomAttrs> {
    /**
     * Defines the primary CSS class name for the hero component's root element.
     * Subclasses MUST implement this method to provide a specific class name.
     *
     * @example
     * ```ts
     * className(): string {
     *  return 'FoobarHero';
     * }
     * ```
     */
    abstract className(): string;
    /**
     * Defines the child elements that will be rendered within the main container of the hero.
     * Subclasses MUST implement this method to define the specific content of the hero.
     *
     * @example
     * ```tsx
     * bodyItems(): ItemList<Mithril.Children> {
     *   const items = new ItemList<Mithril.Children>();
     *   items.add('title', <h1>Welcome!</h1>);
     *   return items;
     * }
     * ```
     */
    abstract bodyItems(): ItemList<Mithril.Children>;
    /**
     * Defines inline CSS styles for the hero component's root element.
     * Subclasses can override this method to provide custom styles.
     *
     * @example
     * ```ts
     * style(): Record<string, string> {
     *   return {
     *     backgroundColor: '#e7672e',
     *   };
     * }
     * ```
     */
    style(): Record<string, string> | undefined;
    /**
     * Renders the hero component. This method constructs the root element with the
     * appropriate class names and styles. It then calls `viewItems()` to render the
     * content of the hero.
     */
    view(): JSX.Element | null;
    /**
     * Builds the list of items to be rendered within the hero's main container.
     * By default, this method wraps the output of `bodyItems()` in a div with the class "container".
     * Subclasses can override this method to customize the structure of the hero's content.
     *
     * @example
     * ```tsx
     * viewItems(): ItemList<Mithril.Children> {
     *    const items = super.viewItems();
     *
     *    items.add('custom', <div className="containerNarrow">custom element</div>);
     *
     *    return items;
     * }
     */
    viewItems(): ItemList<Mithril.Children>;
}
