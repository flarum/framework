import Modal, { type IInternalModalAttrs } from './Modal';
import type Mithril from 'mithril';
export interface ConfirmOptions {
    /** Without one, the dialog shows only the message. */
    title?: Mithril.Children;
    message: Mithril.Children;
    /** Shown under the message, such as a list of what will change. */
    details?: Mithril.Children;
    /** Name the action ("Delete", "Revoke") rather than "OK". */
    confirmText?: Mithril.Children;
    cancelText?: Mithril.Children;
    /** Styles the confirm button as destructive. */
    danger?: boolean;
}
export interface IConfirmModalAttrs extends IInternalModalAttrs, ConfirmOptions {
    onanswer: (confirmed: boolean) => void;
}
/**
 * Shown with `app.confirm()`.
 */
export default class ConfirmModal<CustomAttrs extends IConfirmModalAttrs = IConfirmModalAttrs> extends Modal<CustomAttrs> {
    protected answered: boolean;
    className(): string;
    title(): Mithril.Children;
    protected inner(): Mithril.Children;
    content(): JSX.Element;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    /**
     * Answers once the dialog has gone, so the caller can close a modal this one
     * was stacked on: one modal can't start closing while another is.
     */
    protected confirm(): void;
    protected answer(confirmed: boolean): void;
}
