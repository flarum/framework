import Component, { ComponentAttrs } from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
import type AccessToken from '../../common/models/AccessToken';
import { NestedStringArray } from '@askvortsov/rich-icu-message-formatter';
export interface IAccessTokensListAttrs extends ComponentAttrs {
    tokens: AccessToken[];
    type: 'session' | 'developer_token';
    hideTokens?: boolean;
    icon?: string;
    ondelete?: (token: AccessToken) => void;
}
export default class AccessTokensList<CustomAttrs extends IAccessTokensListAttrs = IAccessTokensListAttrs> extends Component<CustomAttrs> {
    protected loading: Record<string, boolean | undefined>;
    protected showingTokens: Record<string, boolean | undefined>;
    view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;
    tokenView(token: AccessToken): Mithril.Children;
    tokenViewItems(token: AccessToken): ItemList<Mithril.Children>;
    tokenInfoItems(token: AccessToken): ItemList<Mithril.Children>;
    tokenActionItems(token: AccessToken): ItemList<Mithril.Children>;
    revoke(token: AccessToken): Promise<void>;
    generateTokenTitle(token: AccessToken): NestedStringArray;
    tokenValueDisplay(token: AccessToken): Mithril.Children;
}
