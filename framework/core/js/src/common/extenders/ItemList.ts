import type IExtender from './IExtender';
import type { IExtensionModule } from './IExtender';
import type Application from '../Application';
import type Mithril from 'mithril';
import type { IItemObject } from '../utils/ItemList';
import { extend } from '../extend';
import ItemListComponent from '../components/ItemList';

type LazyContent<T> = (context: T) => Mithril.Children;

/**
 * The `ItemList` extender allows you to add, remove, and replace items in an
 * `ItemList` component. Each ItemList has a unique key, which is used to
 * identify it.
 *
 * @example
 * ```tsx
 * import Extend from 'flarum/common/extenders';
 *
 * export default [
 *   new Extend.ItemList<PageStructure>('PageStructure.mainItems')
 *     .add('test', (context) => app.forum.attribute('baseUrl'), 400)
 *     .setContent('hero', (context) => <div>My new content</div>)
 *     .setPriority('hero', 0)
 *     .remove('hero')
 * ]
 * ```
 */
export default class ItemList<T = Component<any>> implements IExtender {
  protected key: string;
  protected additions: Array<IItemObject<LazyContent<T>>> = [];
  protected removals: string[] = [];
  protected contentReplacements: Record<string, LazyContent<T>> = {};
  protected priorityReplacements: Record<string, number> = {};

  constructor(key: string) {
    this.key = key;
  }

  add(itemName: string, content: LazyContent<T>, priority: number = 0) {
    this.additions.push({ itemName, content, priority });

    return this;
  }

  remove(itemName: string) {
    this.removals.push(itemName);

    return this;
  }

  setContent(itemName: string, content: LazyContent<T>) {
    this.contentReplacements[itemName] = content;

    return this;
  }

  setPriority(itemName: string, priority: number) {
    this.priorityReplacements[itemName] = priority;

    return this;
  }

  extend(app: Application, extension: IExtensionModule) {
    const { key, additions, removals, contentReplacements, priorityReplacements } = this;

    extend(ItemListComponent.prototype, 'items', function (this: ItemListComponent, items) {
      if (key !== this.attrs.key) return;

      const safeContent = (content: Mithril.Children) => (typeof content === 'string' ? [content] : content);

      for (const itemName of removals) {
        items.remove(itemName);
      }

      for (const { itemName, content, priority } of additions) {
        items.add(itemName, safeContent(content(this.attrs.context)), priority);
      }

      for (const [itemName, content] of Object.entries(contentReplacements)) {
        items.setContent(itemName, safeContent(content(this.attrs.context)));
      }

      for (const [itemName, priority] of Object.entries(priorityReplacements)) {
        items.setPriority(itemName, priority);
      }
    });
  }
}
