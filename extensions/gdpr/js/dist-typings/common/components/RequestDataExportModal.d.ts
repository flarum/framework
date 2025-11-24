import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import User from 'flarum/common/models/User';
import type Mithril from 'mithril';
interface RequestDataExportModalAttrs extends IInternalModalAttrs {
    user: User;
}
export default class RequestDataExportModal extends Modal<RequestDataExportModalAttrs> {
    user: User;
    oninit(vnode: Mithril.Vnode<RequestDataExportModalAttrs>): void;
    className(): string;
    title(): any[];
    content(): JSX.Element;
    requestExport(): void;
}
export {};
