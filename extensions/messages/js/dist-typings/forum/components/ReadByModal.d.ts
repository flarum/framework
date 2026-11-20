import Modal, { type IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Mithril from 'mithril';
import type Dialog from '../../common/models/Dialog';
import type DialogMessage from '../../common/models/DialogMessage';
export interface IReadByModalAttrs extends IInternalModalAttrs {
    dialog: Dialog;
    message: DialogMessage;
}
export default class ReadByModal<CustomAttrs extends IReadByModalAttrs = IReadByModalAttrs> extends Modal<CustomAttrs> {
    loading: boolean;
    failed: boolean;
    /** Readers the forum may name now. */
    current: Set<string>;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): string | any[];
    content(): JSX.Element;
}
