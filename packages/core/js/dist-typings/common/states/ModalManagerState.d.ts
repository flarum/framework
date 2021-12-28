import type Component from '../Component';
import Modal from '../components/Modal';
/**
 * Ideally, `show` would take a higher-kinded generic, ala:
 *  `show<Attrs, C>(componentClass: C<Attrs>, attrs: Attrs): void`
 * Unfortunately, TypeScript does not support this:
 * https://github.com/Microsoft/TypeScript/issues/1213
 * Therefore, we have to use this ugly, messy workaround.
 */
declare type UnsafeModalClass = ComponentClass<any, Modal> & {
    isDismissible: boolean;
    component: typeof Component.component;
};
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
        componentClass: UnsafeModalClass;
        attrs?: Record<string, unknown>;
        key: number;
    };
    /**
     * Used to force re-initialization of modals if a modal
     * is replaced by another of the same type.
     */
    private key;
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
    show(componentClass: UnsafeModalClass, attrs?: Record<string, unknown>): void;
    /**
     * Closes the currently open dialog, if one is open.
     */
    close(): void;
    /**
     * Checks if a modal is currently open.
     *
     * @return `true` if a modal dialog is currently open, otherwise `false`.
     */
    isModalOpen(): boolean;
}
export {};
