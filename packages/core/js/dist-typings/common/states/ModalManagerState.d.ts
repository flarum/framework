export default class ModalManagerState {
    modal: {
        componentClass: any;
        attrs: any;
    } | null;
    /**
     * Show a modal dialog.
     *
     * @public
     */
    public show(componentClass: any, attrs: any): void;
    /**
     * Close the modal dialog.
     *
     * @public
     */
    public close(): void;
    closeTimeout: number | undefined;
}
