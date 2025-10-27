import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
export interface IDiscoverSectionAttrs extends ComponentAttrs {
}
export default class DiscoverSection<CustomAttrs extends IDiscoverSectionAttrs = IDiscoverSectionAttrs> extends Component<CustomAttrs> {
    protected search: any;
    protected warningsDismissed: any;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    load(page?: number): void;
    view(): JSX.Element;
    tabFilters(): Record<string, {
        label: Mithril.Children;
        active: () => boolean;
    }>;
    tabItems(): any;
    warningItems(): any;
    private applySearch;
    toolbarPrimaryItems(): any;
    toolbarSecondaryItems(): any;
    extensionList(): JSX.Element;
    footerItems(): any;
    private setWarningDismissed;
}
