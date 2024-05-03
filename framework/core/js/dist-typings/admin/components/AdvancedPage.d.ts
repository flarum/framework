/// <reference path="../../@types/translator-icu-rich.d.ts" />
import AdminPage from './AdminPage';
import type { IPageAttrs } from '../../common/components/Page';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
export default class AdvancedPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends AdminPage<CustomAttrs> {
    searchDriverOptions: Record<string, Record<string, string>>;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    headerInfo(): {
        className: string;
        icon: string;
        title: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
        description: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    };
    content(): JSX.Element[];
    driverLocale(): Record<string, Record<string, string>>;
    sectionItems(): ItemList<Mithril.Children>;
    searchDrivers(): JSX.Element;
    maintenance(): JSX.Element;
}
