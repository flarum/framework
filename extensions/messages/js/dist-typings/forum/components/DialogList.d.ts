/// <reference types="mithril" />
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import DialogListState from '../states/DialogListState';
import Dialog from '../../common/models/Dialog';
export interface IDialogListAttrs extends ComponentAttrs {
    state: DialogListState;
    activeDialog?: Dialog | null;
    hideMore?: boolean;
    itemActions?: boolean;
}
export default class DialogList<CustomAttrs extends IDialogListAttrs = IDialogListAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
}
