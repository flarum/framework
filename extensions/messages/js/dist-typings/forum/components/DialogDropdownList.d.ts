import Component from 'flarum/common/Component';
import type { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import DialogListState from '../states/DialogListState';
import ItemList from 'flarum/common/utils/ItemList';
export interface IDialogListDropdownAttrs extends ComponentAttrs {
    state: DialogListState;
}
export default class DialogDropdownList<CustomAttrs extends IDialogListDropdownAttrs = IDialogListDropdownAttrs> extends Component<CustomAttrs, DialogListState> {
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    view(): JSX.Element;
    controlItems(): ItemList<unknown>;
    content(): JSX.Element;
}
