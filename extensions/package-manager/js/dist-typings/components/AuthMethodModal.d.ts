import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Mithril from 'mithril';
import Stream from 'flarum/common/utils/Stream';
export interface IAuthMethodModalAttrs extends IInternalModalAttrs {
    onsubmit: (type: string, host: string, token: string) => void;
    type?: string;
    host?: string;
    token?: string;
}
export default class AuthMethodModal<CustomAttrs extends IAuthMethodModalAttrs = IAuthMethodModalAttrs> extends Modal<CustomAttrs> {
    protected type: Stream<string>;
    protected host: Stream<string>;
    protected token: Stream<string>;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    submit(): void;
}
