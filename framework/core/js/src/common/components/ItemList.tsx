import ItemListUtil from '../utils/ItemList';
import Component from '../Component';
import type Mithril from 'mithril';
import listItems from '../helpers/listItems';

export interface IItemListAttrs {
  /** Unique key for the list. Use the convention of `componentName.listName` */
  key: string;
  /** The context of the list. Usually the component instance. Will be automatically set if not provided. */
  context?: any;
  /** Optionally, the element tag to wrap each item in. Defaults to none. */
  wrapper?: string;
}

export default class ItemList<CustomAttrs extends IItemListAttrs = IItemListAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs>) {
    const items = this.items(vnode.children).toArray();

    return vnode.attrs.wrapper ? listItems(items, vnode.attrs.wrapper) : items;
  }

  items(children: Mithril.ChildArrayOrPrimitive | undefined): ItemListUtil<Mithril.Children> {
    const items = new ItemListUtil<Mithril.Children>();

    let priority = 10;

    this.validateChildren(children)
      .reverse()
      .forEach((child: Mithril.Vnode<any, any>) => {
        items.add(child.key!.toString(), child, (priority += 10));
      });

    return items;
  }

  private validateChildren(children: Mithril.ChildArrayOrPrimitive | undefined): Mithril.Vnode<any, any>[] {
    if (!children) return [];

    children = Array.isArray(children) ? children : [children];
    children = children.filter((child: Mithril.Children) => child !== null && child !== undefined);

    // It must be a Vnode array
    children.forEach((child: Mithril.Children) => {
      if (typeof child !== 'object' || !('tag' in child!)) {
        throw new Error(`[${this.attrs.key}] The ItemList component requires a valid mithril Vnode array. Found: ${typeof child}.`);
      }

      if (!child.key) {
        throw new Error('The ItemList component requires a unique key for each child in the list.');
      }
    });

    return children as Mithril.Vnode<any, any>[];
  }
}
