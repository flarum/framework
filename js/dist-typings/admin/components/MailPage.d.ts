export default class MailPage extends AdminPage<import("../../common/components/Page").IPageAttrs> {
    constructor();
    sendingTest: boolean | undefined;
    refresh(): void;
    status: {
        sending: boolean;
        errors: {};
    } | undefined;
    driverFields: any;
    sendTestEmail(): void;
    testEmailSuccessAlert: number | undefined;
}
import AdminPage from "./AdminPage";
