import Component from '../../common/Component';
import type { ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import classList from '../../common/utils/classList';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import ItemList from '../../common/components/ItemList';

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

    return (
      <div className={classList('Page', className)}>
        <ItemList key="PageStructure.rootItems" context={this}>
          <div key="pane" className="Page-pane">
            {(this.attrs.pane && this.attrs.pane()) || null}
          </div>

          <div key="main" className="Page-main">
            {this.attrs.loading ? (
              <ItemList key="PageStructure.loadingItems" context={this}>
                <LoadingIndicator key="spinner" display="block" />
              </ItemList>
            ) : (
              <ItemList key="PageStructure.mainItems" context={this}>
                <div key="hero" className="Page-hero">
                  {(this.attrs.hero && this.attrs.hero()) || null}
                </div>

                <div key="container" className="Page-container container">
                  <div key="sidebar" className="Page-sidebar">
                    <ItemList key="PageStructure.sidebarItems" context={this}>
                      {this.attrs.sidebar && (
                        <div key="provided" className="Page-sidebar-main">
                          {this.attrs.sidebar()}
                        </div>
                      )}
                    </ItemList>
                  </div>

                  <div key="content" className="Page-content">
                    {this.content}
                  </div>
                </div>
              </ItemList>
            )}
          </div>
        </ItemList>
      </div>
    );
  }
}
