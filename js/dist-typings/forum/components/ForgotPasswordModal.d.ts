/**
 * The `ForgotPasswordModal` component displays a modal which allows the user to
 * enter their email address and request a link to reset their password.
 *
 * ### Attrs
 *
 * - `email`
 */
export default class ForgotPasswordModal extends Modal {
    /**
     * The value of the email input.
     *
     * @type {Function}
     */
    email: Function | undefined;
    /**
     * Whether or not the password reset email was sent successfully.
     *
     * @type {Boolean}
     */
    success: boolean | undefined;
    alert: any;
}
import Modal from "../../common/components/Modal";
