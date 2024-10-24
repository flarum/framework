/// <reference types="mithril" />
import AdminPage from './AdminPage';
export default class PermissionsPage extends AdminPage {
    headerInfo(): {
        className: string;
        icon: string;
        title: string | any[];
        description: string | any[];
    };
    content(): JSX.Element;
    static register(): void;
    static registerViewPermissions(): void;
    static registerStartPermissions(): void;
    static registerReplyPermissions(): void;
    static registerModeratePermissions(): void;
}
