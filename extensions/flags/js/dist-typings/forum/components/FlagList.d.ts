import Component from 'flarum/common/Component';
import type { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import type FlagListState from '../states/FlagListState';
export interface IFlagListAttrs extends ComponentAttrs {
    state: FlagListState;
}
export default class FlagList<CustomAttrs extends IFlagListAttrs = IFlagListAttrs> extends Component<CustomAttrs, FlagListState> {
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    view(): JSX.Element;
    content(state: FlagListState): JSX.Element[][] | null;
}
