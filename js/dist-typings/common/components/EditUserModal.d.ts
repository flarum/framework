/**
 * The `EditUserModal` component displays a modal dialog with a login form.
 */
export default class EditUserModal extends Modal {
    username: Stream<any> | undefined;
    email: Stream<any> | undefined;
    isEmailConfirmed: Stream<any> | undefined;
    setPassword: Stream<boolean> | undefined;
    password: Stream<any> | undefined;
    groups: {} | undefined;
    fields(): ItemList;
    activate(): void;
    data(): {
        relationships: {};
    };
    nonAdminEditingAdmin(): any;
    /**
     * @internal
     * @protected
     */
    protected userIsAdmin(user: any): any;
}
import Modal from "./Modal";
import Stream from "../utils/Stream";
import ItemList from "../utils/ItemList";
