import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import type Mithril from 'mithril';
export default class InfoModal extends Modal<IInternalModalAttrs> {
    protected info: string;
    oninit(vnode: Mithril.Vnode<IInternalModalAttrs, this>): void;
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
    copyToClipboard(): void;
    loadInfo(): Promise<void>;
}
