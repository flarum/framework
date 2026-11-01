import UserPage, { IUserPageAttrs } from './UserPage';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
import UserSecurityPageState from '../states/UserSecurityPageState';
/**
 * The `UserSecurityPage` component displays the user's security control panel, in
 * the context of their user profile.
 */
export default class UserSecurityPage<CustomAttrs extends IUserPageAttrs = IUserPageAttrs> extends UserPage<CustomAttrs, UserSecurityPageState> {
    state: UserSecurityPageState;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    content(): JSX.Element;
    /**
     * Build an item list for the user's settings controls. Developer tokens
     * (30), sessions (20) and logging out everywhere (10) come in that order,
     * so an extension can place a section between them.
     */
    settingsItems(): ItemList<Mithril.Children>;
    /**
     * Build an item list for the user's access accessToken settings.
     */
    developerTokensItems(): ItemList<Mithril.Children>;
    /**
     * Build an item list for the user's access accessToken settings.
     */
    sessionsItems(): ItemList<Mithril.Children>;
    loadTokens(): Promise<void>;
    terminateAllOtherSessions(): Promise<void> | undefined;
    globalLogout(): Promise<void>;
}
