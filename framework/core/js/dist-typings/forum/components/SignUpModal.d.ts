/**
 * The `SignUpModal` component displays a modal dialog with a singup form.
 *
 * ### Attrs
 *
 * - `username`
 * - `email`
 * - `password`
 * - `token` An email token to sign up with.
 */
export default class SignUpModal extends Modal {
    /**
     * The value of the username input.
     *
     * @type {Function}
     */
    username: Function | undefined;
    /**
     * The value of the email input.
     *
     * @type {Function}
     */
    email: Function | undefined;
    /**
     * The value of the password input.
     *
     * @type {Function}
     */
    password: Function | undefined;
    isProvided(field: any): any;
    body(): (string | JSX.Element)[];
    fields(): ItemList;
    footer(): JSX.Element[];
    /**
     * Open the log in modal, prefilling it with an email/username/password if
     * the user has entered one.
     *
     * @public
     */
    public logIn(): void;
    /**
     * Get the data that should be submitted in the sign-up request.
     *
     * @return {Object}
     * @protected
     */
    protected submitData(): Object;
}
import Modal from "../../common/components/Modal";
import ItemList from "../../common/utils/ItemList";
