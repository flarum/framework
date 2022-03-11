/**
 * The `ChangeEmailModal` component shows a modal dialog which allows the user
 * to change their email address.
 */
export default class ChangeEmailModal extends Modal<import("../../common/components/Modal").IInternalModalAttrs> {
    constructor();
    /**
     * Whether or not the email has been changed successfully.
     *
     * @type {Boolean}
     */
    success: boolean | undefined;
    /**
     * The value of the email input.
     *
     * @type {function}
     */
    email: Function | undefined;
    /**
     * The value of the password input.
     *
     * @type {function}
     */
    password: Function | undefined;
}
import Modal from "../../common/components/Modal";
