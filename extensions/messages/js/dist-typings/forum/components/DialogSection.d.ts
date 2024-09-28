import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Dialog from '../../common/models/Dialog';
import type Mithril from 'mithril';
import MessageStreamState from '../states/MessageStreamState';
import ItemList from 'flarum/common/utils/ItemList';
export interface IDialogStreamAttrs extends ComponentAttrs {
    dialog: Dialog;
}
export default class DialogSection<CustomAttrs extends IDialogStreamAttrs = IDialogStreamAttrs> extends Component<CustomAttrs> {
    protected loading: boolean;
    protected messages: MessageStreamState;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    view(): JSX.Element;
    actionItems(): ItemList<Mithril.Children>;
    controlItems(): ItemList<Mithril.Children>;
}
