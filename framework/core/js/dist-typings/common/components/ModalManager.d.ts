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
    protected closeWatcher: CloseWatcher | undefined;
    protected lastCloseWatcherKey: number | undefined;
    protected modalClosing: boolean;
    protected closingModalKey: number | undefined;
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
    /**
     * Animates the current modal out, then closes it.
     *
     * `closedCallback` runs when the hide animation ends. If the modal is closed
     * some other way before then, such as by a page change (Page closes any open
     * modal) or by a non-stacked modal replacing it, its dialog is removed, the
     * animation never ends and `closedCallback` does not run. It is not run in
     * that case because whatever closed the modal may already have moved on: a
     * callback that changes the route, for example, would close the new modal too.
     */
    animateHide(closedCallback?: () => void): void;
    protected handleEscPress(e: KeyboardEvent): void;
    protected handlePossibleBackdropClick(e: MouseEvent): void;
    protected onBackdropTransitionEnd(e: TransitionEvent): void;
}
export {};
