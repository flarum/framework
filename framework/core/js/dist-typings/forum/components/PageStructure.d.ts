import Component from '../../common/Component';
import type { ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
export interface PageStructureAttrs extends ComponentAttrs {
    hero?: () => Mithril.Children;
    sidebar?: () => Mithril.Children;
    pane?: () => Mithril.Children;
    loading?: boolean;
    className: string;
}
export default class PageStructure<CustomAttrs extends PageStructureAttrs = PageStructureAttrs> extends Component<CustomAttrs> {
    private content?;
    view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;
    rootItems(): ItemList<Mithril.Children>;
    mainItems(): ItemList<Mithril.Children>;
    loadingItems(): ItemList<Mithril.Children>;
    main(): Mithril.Children;
    containerItems(): ItemList<Mithril.Children>;
    container(): Mithril.Children;
    sidebarItems(): ItemList<Mithril.Children>;
    sidebar(): Mithril.Children;
    providedPane(): Mithril.Children;
    providedHero(): Mithril.Children;
    providedContent(): Mithril.Children;
}
