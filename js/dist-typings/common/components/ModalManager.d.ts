/**
 * The `ModalManager` component manages a modal dialog. Only one modal dialog
 * can be shown at once; loading a new component into the ModalManager will
 * overwrite the previous one.
 */
export default class ModalManager extends Component<import("../Component").ComponentAttrs> {
    constructor();
    animateShow(readyCallback: any): void;
    animateHide(): void;
}
import Component from "../Component";
