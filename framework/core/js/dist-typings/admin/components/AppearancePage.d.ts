/// <reference path="../../@types/translator-icu-rich.d.ts" />
import AdminPage from './AdminPage';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
export default class AppearancePage extends AdminPage {
    headerInfo(): {
        className: string;
        icon: string;
        title: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
        description: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    };
    content(): JSX.Element;
    colorItems(): ItemList<Mithril.Children>;
    onsaved(): void;
}
