import Modal, { type IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Mithril from 'mithril';
export interface IConfirmModalAttrs extends IInternalModalAttrs {
    title: Mithril.Children;
    /** What will happen, and what it means, in a sentence or two. */
    message: Mithril.Children;
    /** Anything worth listing under the message, e.g. the requirements that will change. */
    details?: Mithril.Children;
    /** The confirm button's label: the action itself, never just "OK". */
    confirmText: Mithril.Children;
    /** Styles the confirm button as destructive. */
    danger?: boolean;
    onconfirm: () => void;
}
/**
 * Asks before an action that changes the forum, in place of the browser's
 * `confirm()`: themed, translatable, and able to show what will change.
 */
export default class ConfirmModal extends Modal<IConfirmModalAttrs> {
    className(): string;
    title(): Mithril.Children;
    content(): JSX.Element;
    onready(): void;
}
/**
 * Show a confirmation. Core's `app.modal.show` is typed for modals without
 * required attributes, so a modal with its own needs the cast core's bundled
 * extensions use (gdpr's DeleteUserModal does the same); it is made once,
 * here, rather than at every call.
 */
export declare function confirmAction(attrs: Omit<IConfirmModalAttrs, keyof IInternalModalAttrs>): void;
