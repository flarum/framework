import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import User from 'flarum/common/models/User';
import type Mithril from 'mithril';
interface DeleteUserModalAttrs extends IInternalModalAttrs {
    user: User;
}
export default class DeleteUserModal extends Modal<DeleteUserModalAttrs> {
    user: User;
    loadingAnonymization: boolean;
    loadingDeletion: boolean;
    oninit(vnode: Mithril.Vnode<DeleteUserModalAttrs>): void;
    className(): string;
    title(): any[];
    content(): JSX.Element;
    defaultErasure(): void;
    specificErasure(mode: string): void;
}
export {};
