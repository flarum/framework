export default class MailPage extends AdminPage {
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
