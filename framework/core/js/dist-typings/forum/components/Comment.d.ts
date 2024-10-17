import Component, { type ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import type ItemList from '../../common/utils/ItemList';
import type User from '../../common/models/User';
export interface ICommentAttrs extends ComponentAttrs {
    headerItems: ItemList<Mithril.Children>;
    user: User | false | undefined;
    cardVisible: boolean;
    isEditing: boolean;
    isHidden: boolean;
    contentHtml: string;
    search?: string;
}
export default class Comment<CustomAttrs extends ICommentAttrs = ICommentAttrs> extends Component<CustomAttrs> {
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    view(): JSX.Element[];
}
