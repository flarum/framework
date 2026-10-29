import type Mithril from 'mithril';

import type { IPageAttrs } from '../../common/components/Page';
import type Model from '../../common/Model';
import type ResourceDefinition from '../../common/resource/ResourceDefinition';
import ResourceListState from '../../common/resource/ResourceListState';
import AdminPage, { type AdminHeaderAttrs } from './AdminPage';
import ResourceTable, { type ResourceTableAttrs } from './resource/ResourceTable';

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
 * per page from `createList()`, the header from `page()`, and the table from `tableComponent()` with
 * `tableAttrs()`.
 */
export default abstract class ResourceListPage<
  D extends ResourceDefinition<any> = ResourceDefinition,
  CustomAttrs extends IPageAttrs = IPageAttrs,
> extends AdminPage<CustomAttrs> {
  protected definition!: D;
  protected list!: ResourceListState<ModelOf<D>>;

  abstract createDefinition(): D;

  abstract page(): ResourceListPageHeader;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.definition = this.createDefinition();
    this.list = this.createList();
  }

  createList(): ResourceListState<ModelOf<D>> {
    return new ResourceListState<ModelOf<D>>(this.definition, { pageSize: 50 });
  }

  headerInfo(): AdminHeaderAttrs {
    return this.page();
  }

  tableComponent(): typeof ResourceTable<any, any> {
    return ResourceTable;
  }

  /** Default: the definition, the list and `{className}-table`. */
  tableAttrs(): ResourceTableAttrs<ModelOf<D>> {
    return { definition: this.definition, state: this.list, className: `${this.page().className}-table` };
  }

  content(): Mithril.Children {
    const Table = this.tableComponent();

    return (
      <div className="ResourceListPage">
        <Table {...this.tableAttrs()} />
      </div>
    );
  }
}
