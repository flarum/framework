export default class UploadImageButton extends Button<import("../../common/components/Button").IButtonAttrs> {
    constructor();
    loading: boolean;
    view(vnode: any): JSX.Element;
    /**
     * Prompt the user to upload an image.
     */
    upload(): void;
    /**
     * Remove the logo.
     */
    remove(): void;
    resourceUrl(): string;
    /**
     * After a successful upload/removal, reload the page.
     *
     * @param {object} response
     * @protected
     */
    protected success(response: object): void;
    /**
     * If upload/removal fails, stop loading.
     *
     * @param {object} response
     * @protected
     */
    protected failure(response: object): void;
}
import Button from "../../common/components/Button";
