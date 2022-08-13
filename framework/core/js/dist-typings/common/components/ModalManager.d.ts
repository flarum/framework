import Component from '../Component';
import { FocusTrap } from '../utils/focusTrap';
import type ModalManagerState from '../states/ModalManagerState';
import type Mithril from 'mithril';
interface IModalManagerAttrs {
    state: ModalManagerState;
}
/**
 * The `ModalManager` component manages one or more modal dialogs. Stacking modals
 * is supported. Multiple dialogs can be shown at once; loading a new component
 * into the ModalManager will overwrite the previous one.
 */
export default class ModalManager extends Component<IModalManagerAttrs> {
    protected focusTrap: FocusTrap | undefined;
    protected lastSetFocusTrap: number | undefined;
    protected modalClosing: boolean;
    protected keyUpListener: null | ((e: KeyboardEvent) => void);
    view(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): Mithril.Children;
    oncreate(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): void;
    onbeforeremove(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): void;
    onupdate(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): void;
    /**
     * Get current active dialog
     */
    private get activeDialogElement();
    /**
     * Get current active dialog
     */
    private get activeDialogManagerElement();
    animateShow(readyCallback?: () => void): void;
    animateHide(closedCallback?: () => void): void;
    protected handleEscPress(e: KeyboardEvent): void;
    protected handlePossibleBackdropClick(e: MouseEvent): void;
    protected onBackdropTransitionEnd(e: TransitionEvent): void;
}
export {};
