/**
 * The `AvatarEditor` component displays a user's avatar along with a dropdown
 * menu which allows the user to upload/remove the avatar.
 *
 * ### Attrs
 *
 * - `className`
 * - `user`
 */
export default class AvatarEditor extends Component<import("../../common/Component").ComponentAttrs> {
    constructor();
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
    /**
     * Get the items in the edit avatar dropdown menu.
     *
     * @return {ItemList}
     */
    controlItems(): ItemList;
    /**
     * Enable dragover style
     *
     * @param {Event} e
     */
    enableDragover(e: Event): void;
    /**
     * Disable dragover style
     *
     * @param {Event} e
     */
    disableDragover(e: Event): void;
    /**
     * Upload avatar when file is dropped into dropzone.
     *
     * @param {Event} e
     */
    dropUpload(e: Event): void;
    /**
     * If the user doesn't have an avatar, there's no point in showing the
     * controls dropdown, because only one option would be viable: uploading.
     * Thus, when the avatar editor's dropdown toggle button is clicked, we prompt
     * the user to upload an avatar immediately.
     *
     * @param {Event} e
     */
    quickUpload(e: Event): void;
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
     * store, and force a recomputation of the user's avatar color.
     *
     * @param {Object} response
     * @protected
     */
    protected success(response: Object): void;
    /**
     * If avatar upload/removal fails, stop loading.
     *
     * @param {Object} response
     * @protected
     */
    protected failure(response: Object): void;
}
import Component from "../../common/Component";
import ItemList from "../../common/utils/ItemList";
