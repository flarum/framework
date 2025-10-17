import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Dialog from '../../common/models/Dialog';
import type Mithril from 'mithril';
import MessageStreamState from '../states/MessageStreamState';
export interface IDialogStreamAttrs extends ComponentAttrs {
    dialog: Dialog;
}
export default class DialogSection<CustomAttrs extends IDialogStreamAttrs = IDialogStreamAttrs> extends Component<CustomAttrs> {
    protected loading: boolean;
    protected messages: MessageStreamState;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    requestParams(forgetNear?: boolean): any;
    view(): JSX.Element;
    actionItems(): any;
    controlItems(): any;
}
