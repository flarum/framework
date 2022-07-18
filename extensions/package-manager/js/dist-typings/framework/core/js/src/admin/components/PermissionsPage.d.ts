/// <reference types="flarum/@types/translator-icu-rich" />
export default class PermissionsPage extends AdminPage<import("../../common/components/Page").IPageAttrs> {
    constructor();
    headerInfo(): {
        className: string;
        icon: string;
        title: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
        description: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    };
    content(): JSX.Element[];
}
import AdminPage from "./AdminPage";
