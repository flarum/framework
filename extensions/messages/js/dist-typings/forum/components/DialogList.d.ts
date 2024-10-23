import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import DialogListState from '../states/DialogListState';
import Dialog from '../../common/models/Dialog';
export interface IDialogListAttrs extends ComponentAttrs {
    state: DialogListState;
    activeDialog?: Dialog | null;
    hideMore?: boolean;
    itemActions?: boolean;
}
export default class DialogList<CustomAttrs extends IDialogListAttrs = IDialogListAttrs> extends Component<CustomAttrs> {
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    view(): JSX.Element;
}
