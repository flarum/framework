/// <reference path="../../@types/translator-icu-rich.d.ts" />
export default class MailPage extends AdminPage<import("../../common/components/Page").IPageAttrs> {
    constructor();
    oninit(vnode: any): void;
    sendingTest: boolean | undefined;
    headerInfo(): {
        className: string;
        icon: string;
        title: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
        description: import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    };
    refresh(): void;
    status: {
        sending: boolean;
        errors: {};
    } | undefined;
    driverFields: any;
    content(): JSX.Element;
    sendTestEmail(): void;
    testEmailSuccessAlert: number | undefined;
    saveSettings(e: any): void;
}
import AdminPage from "./AdminPage";
