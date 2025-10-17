import Component from 'flarum/common/Component';
import type { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import DialogListState from '../states/DialogListState';
export interface IDialogListDropdownAttrs extends ComponentAttrs {
    state: DialogListState;
}
export default class DialogDropdownList<CustomAttrs extends IDialogListDropdownAttrs = IDialogListDropdownAttrs> extends Component<CustomAttrs, DialogListState> {
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    view(): JSX.Element;
    controlItems(): any;
    content(): JSX.Element;
}
