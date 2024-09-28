/// <reference path="../../../src/@types/translator-icu-rich.d.ts" />
import ItemList from '../../common/utils/ItemList';
import AdminPage from './AdminPage';
import type { IPageAttrs } from '../../common/components/Page';
import type Mithril from 'mithril';
export declare type HomePageItem = {
    path: string;
    label: Mithril.Children;
};
export declare type DriverLocale = {
    display_name: Record<string, string>;
    slug: Record<string, Record<string, string>>;
};
export default class BasicsPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends AdminPage<CustomAttrs> {
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    headerInfo(): {
        className: string;
        icon: string;
        title: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
        description: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    };
    content(): JSX.Element[];
    /**
     * Build a list of options for the default homepage. Each option must be an
     * object with `path` and `label` properties.
     */
    static homePageItems(): ItemList<HomePageItem>;
    static driverLocale(): DriverLocale;
    static register(): void;
}
