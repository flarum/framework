import app from '../app';
import Component from '../../common/Component';
import type { ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import classList from '../../common/utils/classList';
import ItemList from '../../common/utils/ItemList';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import { prepareSkipLinks } from '../../common/utils/a11y';

export interface PageStructureAttrs extends ComponentAttrs {
  hero?: () => Mithril.Children;
  sidebar?: () => Mithril.Children;
  pane?: () => Mithril.Children;
  loading?: boolean;
  className: string;
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

    items.add('pane', this.providedPane(), 100);
    items.add('main', this.main(), 10);

    return items;
  }

  mainItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('hero', this.providedHero(), 100);
    items.add('container', this.container(), 10);

    return items;
  }

  loadingItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('spinner', <LoadingIndicator display="block" />, 100);

    return items;
  }

  main(): Mithril.Children {
    return (
      <div className="Page-main" id="page-main">
        {this.attrs.loading ? this.loadingItems().toArray() : this.mainItems().toArray()}
      </div>
    );
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

    items.add(
      'skipToMainContent',
      <a href="#main-content" className="sr-only sr-only-focusable-custom" oncreate={() => prepareSkipLinks()}>
        {app.translator.trans('core.forum.index.skip_to_main_content')}
      </a>,
      200
    );

    items.add('sidebar', (this.attrs.sidebar && this.attrs.sidebar()) || null, 100);

    return items;
  }

  sidebar(): Mithril.Children {
    return <div className="Page-sidebar">{this.sidebarItems().toArray()}</div>;
  }

  providedPane(): Mithril.Children {
    return <div className="Page-pane">{(this.attrs.pane && this.attrs.pane()) || null}</div>;
  }

  providedHero(): Mithril.Children {
    return <div className="Page-hero">{(this.attrs.hero && this.attrs.hero()) || null}</div>;
  }

  providedContent(): Mithril.Children {
    return (
      <div className="Page-content" id="main-content">
        {this.content}
      </div>
    );
  }
}
