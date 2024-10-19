/// <reference path="../../@types/translator-icu-rich.d.ts" />
import ItemList from '../../common/utils/ItemList';
import AdminPage from './AdminPage';
import type { Children } from 'mithril';
export default class DashboardPage extends AdminPage {
    headerInfo(): {
        className: string;
        icon: string;
        title: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
        description: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    };
    content(): (Children & {
        itemName: string;
    })[];
    availableWidgets(): ItemList<Children>;
}
