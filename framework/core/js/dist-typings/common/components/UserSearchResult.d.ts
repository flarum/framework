import Component, { type ComponentAttrs } from '../../common/Component';
import User from '../../common/models/User';
import type Mithril from 'mithril';
export interface IUserSearchResultAttrs extends ComponentAttrs {
    user: User;
    onclick?: (user: User) => void;
    query: string;
}
export default class UserSearchResult<CustomAttrs extends IUserSearchResultAttrs = IUserSearchResultAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
    content(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
}
