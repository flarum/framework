import Component from '../Component';
import { AlertAttrs } from './Alert';
import type Mithril from 'mithril';
import type ModalManagerState from '../states/ModalManagerState';
import type ModalManager from './ModalManager';
export interface IInternalModalAttrs {
    state: ModalManagerState;
    animateShow: ModalManager['animateShow'];
    animateHide: ModalManager['animateHide'];
}
export interface IDismissibleOptions {
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
    oncreate(vnode: Mithril.VnodeDOM<ModalAttrs, this>): void;
    onbeforeremove(vnode: Mithril.VnodeDOM<ModalAttrs, this>): Promise<void> | void;
    /**
     * @todo split into FormModal and Modal in 2.0
     */
    view(): JSX.Element;
    protected wrapper(children: Mithril.Children): Mithril.Children;
    protected inner(): Mithril.Children;
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
     * Callback executed when the modal is shown and ready to be interacted with.
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
    protected get dismissibleOptions(): IDismissibleOptions;
}
