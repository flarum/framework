import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import ItemList from 'flarum/common/utils/ItemList';
import Stream from 'flarum/common/utils/Stream';
export interface IDiscoverSectionAttrs extends ComponentAttrs {
}
export default class DiscoverSection<CustomAttrs extends IDiscoverSectionAttrs = IDiscoverSectionAttrs> extends Component<CustomAttrs> {
    protected search: Stream<string>;
    protected warningsDismissed: Stream<boolean>;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    load(page?: number): void;
    view(): JSX.Element;
    /**
     * Maps tab keys to the type filter value forwarded to the Packagist search API.
     * The empty-string key ("") means no type filter (show all flarum-extension packages).
     */
    tabFilters(): Record<string, {
        label: Mithril.Children;
        packagistType: string | null;
    }>;
    tabItems(): ItemList<unknown>;
    warningItems(): ItemList<Mithril.Children>;
    private applySearch;
    toolbarPrimaryItems(): ItemList<unknown>;
    extensionList(): JSX.Element;
    footerItems(): ItemList<Mithril.Children>;
    private setWarningDismissed;
}
