import Component from '../../common/Component';
import type { ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import classList from '../../common/utils/classList';
import ItemList from '../../common/utils/ItemList';

export interface PageStructureAttrs extends ComponentAttrs {
  hero?: Mithril.Children;
  sidebar?: Mithril.Children;
  rootItems?: (items: ItemList<Mithril.Children>) => void;
}

export default class PageStructure<CustomAttrs extends PageStructureAttrs = PageStructureAttrs> extends Component<CustomAttrs> {
  private content?: Mithril.Children;

  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    const { className } = vnode.attrs;

    this.content = vnode.children;

    return <div className={classList('Page', className)}>{this.rootItems().toArray()}</div>;
  }

  rootItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('main', this.main(), 100);

    if (this.attrs.rootItems) {
      this.attrs.rootItems(items);
    }

    return items;
  }

  mainItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('hero', this.providedHero(), 100);
    items.add('container', this.container(), 10);

    return items;
  }

  main(): Mithril.Children {
    return <div className="Page-main">{this.mainItems().toArray()}</div>;
  }

  containerItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('sidebar', this.sidebar(), 100);
    items.add('content', this.providedContent(), 10);

    return items;
  }

  container(): Mithril.Children {
    return <div className="Page-container container">{this.containerItems().toArray()}</div>;
  }

  sidebarItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('sidebar', this.attrs.sidebar, 100);

    return items;
  }

  sidebar(): Mithril.Children {
    return <div className="Page-sidebar">{this.sidebarItems().toArray()}</div>;
  }

  providedHero(): Mithril.Children {
    return <div className="Page-hero">{this.attrs.hero}</div>;
  }

  providedContent(): Mithril.Children {
    return <div className="Page-content">{this.content}</div>;
  }
}
