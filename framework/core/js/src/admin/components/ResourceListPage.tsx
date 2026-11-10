import type Mithril from 'mithril';

import type { IPageAttrs } from '../../common/components/Page';
import type Model from '../../common/Model';
import type ResourceDefinition from '../../common/resource/ResourceDefinition';
import ResourceListState from '../../common/resource/ResourceListState';
import AdminPage, { type AdminHeaderAttrs } from './AdminPage';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import type ResourceTable from './resource/ResourceTable';
import type { ResourceTableAttrs } from './resource/ResourceTable';

type ModelOf<D> = D extends ResourceDefinition<infer M> ? M : Model;

/** The page header: its class (the page root's, and `{className}-header` on the header), icon and text. */
export interface ResourceListPageHeader {
  className: string;
  icon: string;
  title: Mithril.Children;
  description: Mithril.Children;
}

/**
 * An admin page that is one `ResourceTable`: the definition from `createDefinition()`, a list of 50
 * per page from `createList()`, the header from `page()`, and the table from `tableComponent()` (loaded
 * async) with `tableAttrs()`.
 */
export default abstract class ResourceListPage<
  D extends ResourceDefinition<any> = ResourceDefinition,
  CustomAttrs extends IPageAttrs = IPageAttrs,
> extends AdminPage<CustomAttrs> {
  protected definition!: D;
  protected list!: ResourceListState<ModelOf<D>>;

  /** The table class from `tableComponent()`, once its chunk has loaded. */
  protected Table: typeof ResourceTable<any, any> | null = null;

  abstract createDefinition(): D;

  abstract page(): ResourceListPageHeader;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.definition = this.createDefinition();
    this.list = this.createList();

    this.tableComponent().then((Table) => {
      this.Table = Table;
      m.redraw();
    });
  }

  createList(): ResourceListState<ModelOf<D>> {
    return new ResourceListState<ModelOf<D>>(this.definition, { pageSize: 50 });
  }

  headerInfo(): AdminHeaderAttrs {
    return this.page();
  }

  /**
   * The table class. The tables are an async chunk (`./resource/tables`), so this resolves to one;
   * the page shows a loading indicator until it does.
   */
  tableComponent(): Promise<typeof ResourceTable<any, any>> {
    return import('./resource/tables').then(({ ResourceTable }) => ResourceTable);
  }

  /** Default: the definition, the list and `{className}-table`. */
  tableAttrs(): ResourceTableAttrs<ModelOf<D>> {
    return { definition: this.definition, state: this.list, className: `${this.page().className}-table` };
  }

  content(): Mithril.Children {
    const Table = this.Table;

    return <div className="ResourceListPage">{Table ? <Table {...this.tableAttrs()} /> : <LoadingIndicator />}</div>;
  }
}
