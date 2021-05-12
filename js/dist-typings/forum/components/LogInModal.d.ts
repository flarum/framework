/**
 * The `LogInModal` component displays a modal dialog with a login form.
 *
 * ### Attrs
 *
 * - `identification`
 * - `password`
 */
export default class LogInModal extends Modal {
    /**
     * The value of the identification input.
     *
     * @type {Function}
     */
    identification: Function | undefined;
    /**
     * The value of the password input.
     *
     * @type {Function}
     */
    password: Function | undefined;
    /**
     * The value of the remember me input.
     *
     * @type {Function}
     */
    remember: Function | undefined;
    body(): JSX.Element[];
    fields(): ItemList;
    footer(): (string | JSX.Element)[];
    /**
     * Open the forgot password modal, prefilling it with an email if the user has
     * entered one.
     *
     * @public
     */
    public forgotPassword(): void;
    /**
     * Open the sign up modal, prefilling it with an email/username/password if
     * the user has entered one.
     *
     * @public
     */
    public signUp(): void;
}
import Modal from "../../common/components/Modal";
import ItemList from "../../common/utils/ItemList";
