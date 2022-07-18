/// <reference types="flarum/@types/translator-icu-rich" />
export default class AppearancePage extends AdminPage<import("../../common/components/Page").IPageAttrs> {
    constructor();
    headerInfo(): {
        className: string;
        icon: string;
        title: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
        description: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    };
    content(): JSX.Element[];
    colorItems(): ItemList<any>;
}
import AdminPage from "./AdminPage";
import ItemList from "../../common/utils/ItemList";
