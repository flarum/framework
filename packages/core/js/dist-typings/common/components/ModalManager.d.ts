import Component from '../Component';
import { FocusTrap } from '../utils/focusTrap';
import type ModalManagerState from '../states/ModalManagerState';
import type Mithril from 'mithril';
interface IModalManagerAttrs {
    state: ModalManagerState;
}
/**
 * The `ModalManager` component manages a modal dialog. Only one modal dialog
 * can be shown at once; loading a new component into the ModalManager will
 * overwrite the previous one.
 */
export default class ModalManager extends Component<IModalManagerAttrs> {
    protected focusTrap: FocusTrap | undefined;
    /**
     * Whether a modal is currently shown by this modal manager.
     */
    protected modalShown: boolean;
    view(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): Mithril.Children;
    oncreate(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): void;
    onupdate(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): void;
    animateShow(readyCallback: () => void): void;
    animateHide(): void;
}
export {};
