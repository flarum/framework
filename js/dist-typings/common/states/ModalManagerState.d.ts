import Modal from '../components/Modal';
/**
 * Class used to manage modal state.
 *
 * Accessible on the `app` object via `app.modal` property.
 */
export default class ModalManagerState {
    /**
     * @internal
     */
    modal: null | {
        componentClass: typeof Modal;
        attrs?: Record<string, unknown>;
    };
    private closeTimeout?;
    /**
     * Shows a modal dialog.
     *
     * If a modal is already open, the existing one will close and the new modal will replace it.
     *
     * @example <caption>Show a modal</caption>
     * app.modal.show(MyCoolModal, { attr: 'value' });
     *
     * @example <caption>Show a modal from a lifecycle method (`oncreate`, `view`, etc.)</caption>
     * // This "hack" is needed due to quirks with nested redraws in Mithril.
     * setTimeout(() => app.modal.show(MyCoolModal, { attr: 'value' }), 0);
     */
    show(componentClass: typeof Modal, attrs?: Record<string, unknown>): void;
    /**
     * Closes the currently open dialog, if one is open.
     */
    close(): void;
    /**
     * Checks if a modal is currently open.
     *
     * @returns `true` if a modal dialog is currently open, otherwise `false`.
     */
    isModalOpen(): boolean;
}
