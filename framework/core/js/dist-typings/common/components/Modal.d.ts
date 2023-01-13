import Component from '../Component';
import { AlertAttrs } from './Alert';
import type Mithril from 'mithril';
import type ModalManagerState from '../states/ModalManagerState';
import type RequestError from '../utils/RequestError';
import type ModalManager from './ModalManager';
export interface IInternalModalAttrs {
    state: ModalManagerState;
    animateShow: ModalManager['animateShow'];
    animateHide: ModalManager['animateHide'];
}
export interface IDismissibleOptions {
    /**
     * @deprecated Check specific individual attributes instead. Will be removed in Flarum 2.0.
     */
    isDismissible: boolean;
    viaCloseButton: boolean;
    viaEscKey: boolean;
    viaBackdropClick: boolean;
}
/**
 * The `Modal` component displays a modal dialog, wrapped in a form. Subclasses
 * should implement the `className`, `title`, and `content` methods.
 */
export default abstract class Modal<ModalAttrs extends IInternalModalAttrs = IInternalModalAttrs, CustomState = undefined> extends Component<ModalAttrs, CustomState> {
    /**
     * Determine whether or not the modal should be dismissible via an 'x' button.
     *
     * @deprecated Use the individual `isDismissibleVia...` attributes instead and remove references to this.
     */
    static readonly isDismissible: boolean;
    /**
     * Can the model be dismissed with a close button (X)?
     *
     * If `false`, no close button is shown.
     */
    protected static readonly isDismissibleViaCloseButton: boolean;
    /**
     * Can the modal be dismissed by pressing the Esc key on a keyboard?
     */
    protected static readonly isDismissibleViaEscKey: boolean;
    /**
     * Can the modal be dismissed via a click on the backdrop.
     */
    protected static readonly isDismissibleViaBackdropClick: boolean;
    static get dismissibleOptions(): IDismissibleOptions;
    protected loading: boolean;
    /**
     * Attributes for an alert component to show below the header.
     */
    alertAttrs: AlertAttrs | null;
    oninit(vnode: Mithril.Vnode<ModalAttrs, this>): void;
    oncreate(vnode: Mithril.VnodeDOM<ModalAttrs, this>): void;
    onbeforeremove(vnode: Mithril.VnodeDOM<ModalAttrs, this>): Promise<void> | void;
    /**
     * @todo split into FormModal and Modal in 2.0
     */
    view(): JSX.Element;
    /**
     * Get the class name to apply to the modal.
     */
    abstract className(): string;
    /**
     * Get the title of the modal dialog.
     */
    abstract title(): Mithril.Children;
    /**
     * Get the content of the modal.
     */
    abstract content(): Mithril.Children;
    /**
     * Handle the modal form's submit event.
     */
    onsubmit(e: SubmitEvent): void;
    /**
     * Callback executed when the modal is shown and ready to be interacted with.
     *
     * @remark Focuses the first input in the modal.
     */
    onready(): void;
    /**
     * Hides the modal.
     */
    hide(): void;
    /**
     * Sets `loading` to false and triggers a redraw.
     */
    loaded(): void;
    /**
     * Shows an alert describing an error returned from the API, and gives focus to
     * the first relevant field involved in the error.
     */
    onerror(error: RequestError): void;
    private get dismissibleOptions();
}
