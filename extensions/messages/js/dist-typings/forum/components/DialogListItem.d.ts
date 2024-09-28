import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Mithril from 'mithril';
import ItemList from 'flarum/common/utils/ItemList';
import type Dialog from '../../common/models/Dialog';
export interface IDialogListItemAttrs extends ComponentAttrs {
    dialog: Dialog;
    active?: boolean;
    actions?: boolean;
}
export default class DialogListItem<CustomAttrs extends IDialogListItemAttrs = IDialogListItemAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
    actionItems(): ItemList<Mithril.Children>;
}
