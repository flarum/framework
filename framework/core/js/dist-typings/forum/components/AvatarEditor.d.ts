/**
 * The `AvatarEditor` component displays a user's avatar along with a dropdown
 * menu which allows the user to upload/remove the avatar.
 *
 * ### Attrs
 *
 * - `className`
 * - `user`
 */
export default class AvatarEditor extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    oninit(vnode: any): void;
    /**
     * Whether or not an avatar upload is in progress.
     *
     * @type {Boolean}
     */
    loading: boolean | undefined;
    /**
     * Whether or not an image has been dragged over the dropzone.
     *
     * @type {Boolean}
     */
    isDraggedOver: boolean | undefined;
    view(): JSX.Element;
    /**
     * Get the items in the edit avatar dropdown menu.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    controlItems(): ItemList<import('mithril').Children>;
    /**
     * Enable dragover style
     *
     * @param {DragEvent} e
     */
    enableDragover(e: DragEvent): void;
    /**
     * Disable dragover style
     *
     * @param {DragEvent} e
     */
    disableDragover(e: DragEvent): void;
    /**
     * Upload avatar when file is dropped into dropzone.
     *
     * @param {DragEvent} e
     */
    dropUpload(e: DragEvent): void;
    /**
     * If the user doesn't have an avatar, there's no point in showing the
     * controls dropdown, because only one option would be viable: uploading.
     * Thus, when the avatar editor's dropdown toggle button is clicked, we prompt
     * the user to upload an avatar immediately.
     *
     * @param {MouseEvent} e
     */
    quickUpload(e: MouseEvent): void;
    /**
     * Upload avatar using file picker
     */
    openPicker(): void;
    /**
     * Upload avatar
     *
     * @param {File} file
     */
    upload(file: File): void;
    /**
     * Remove the user's avatar.
     */
    remove(): void;
    /**
     * After a successful upload/removal, push the updated user data into the
     * store, and force a re-computation of the user's avatar color.
     *
     * @param {object} response
     * @protected
     */
    protected success(response: object): void;
    /**
     * If avatar upload/removal fails, stop loading.
     *
     * @param {object} response
     * @protected
     */
    protected failure(response: object): void;
}
import Component from "../../common/Component";
import ItemList from "../../common/utils/ItemList";
