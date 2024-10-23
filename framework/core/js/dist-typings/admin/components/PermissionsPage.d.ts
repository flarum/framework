/// <reference path="../../@types/translator-icu-rich.d.ts" />
/// <reference types="mithril" />
import AdminPage from './AdminPage';
export default class PermissionsPage extends AdminPage {
    headerInfo(): {
        className: string;
        icon: string;
        title: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
        description: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    };
    content(): JSX.Element;
    static register(): void;
    static registerViewPermissions(): void;
    static registerStartPermissions(): void;
    static registerReplyPermissions(): void;
    static registerModeratePermissions(): void;
}
