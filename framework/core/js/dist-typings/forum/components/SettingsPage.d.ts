import UserPage, { IUserPageAttrs } from './UserPage';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
/**
 * The `SettingsPage` component displays the user's settings control panel, in
 * the context of their user profile.
 */
export default class SettingsPage<CustomAttrs extends IUserPageAttrs = IUserPageAttrs> extends UserPage<CustomAttrs> {
    discloseOnlineLoading?: boolean;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    content(): JSX.Element;
    /**
     * Build an item list for the user's settings controls.
     */
    settingsItems(): ItemList<Mithril.Children>;
    /**
     * Build an item list for the user's account settings.
     */
    accountItems(): ItemList<Mithril.Children>;
    /**
     * Build an item list for the user's notification settings.
     */
    notificationsItems(): ItemList<Mithril.Children>;
    /**
     * Build an item list for the user's privacy settings.
     */
    privacyItems(): ItemList<Mithril.Children>;
}
