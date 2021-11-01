import Component from '../Component';
import type Mithril from 'mithril';
import type ModalManagerState from '../states/ModalManagerState';
interface IModalManagerAttrs {
    state: ModalManagerState;
}
/**
 * The `ModalManager` component manages a modal dialog. Only one modal dialog
 * can be shown at once; loading a new component into the ModalManager will
 * overwrite the previous one.
 */
export default class ModalManager extends Component<IModalManagerAttrs> {
    view(): JSX.Element;
    oncreate(vnode: Mithril.VnodeDOM<IModalManagerAttrs, this>): void;
    animateShow(readyCallback: () => void): void;
    animateHide(): void;
}
export {};
