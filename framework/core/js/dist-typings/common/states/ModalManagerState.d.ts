import type Component from '../Component';
import Modal, { IDismissibleOptions } from '../components/Modal';
/**
 * Ideally, `show` would take a higher-kinded generic, ala:
 *  `show<Attrs, C>(componentClass: C<Attrs>, attrs: Attrs): void`
 * Unfortunately, TypeScript does not support this:
 * https://github.com/Microsoft/TypeScript/issues/1213
 * Therefore, we have to use this ugly, messy workaround.
 */
declare type UnsafeModalClass = ComponentClass<any, Modal> & {
    get dismissibleOptions(): IDismissibleOptions;
    component: typeof Component.component;
};
declare type ModalItem = {
    componentClass: UnsafeModalClass;
    attrs?: Record<string, unknown>;
    key: number;
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
    modal: ModalItem | null;
    /**
     * @internal
     */
    modalList: ModalItem[];
    /**
     * @internal
     */
    backdropShown: boolean;
    /**
     * Used to force re-initialization of modals if a modal
     * is replaced by another of the same type.
     */
    private key;
    /**
     * Shows a modal dialog.
     *
     * If `stackModal` is `true`, the modal will be shown on top of the current modal.
     *
     * If a value for `stackModal` is not provided, opening a new modal will close
     * any others currently being shown for backwards compatibility.
     *
     * @example <caption>Show a modal</caption>
     * app.modal.show(MyCoolModal, { attr: 'value' });
     *
     * @example <caption>Show a modal from a lifecycle method (`oncreate`, `view`, etc.)</caption>
     * // This "hack" is needed due to quirks with nested redraws in Mithril.
     * setTimeout(() => app.modal.show(MyCoolModal, { attr: 'value' }), 0);
     *
     * @example <caption>Stacking modals</caption>
     * app.modal.show(MyCoolStackedModal, { attr: 'value' }, true);
     */
    show(componentClass: UnsafeModalClass, attrs?: Record<string, unknown>, stackModal?: boolean): void;
    /**
     * Closes the topmost currently open dialog, if one is open.
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
