import ExtensionPage, { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import type { TabItem } from 'flarum/common/components/Tabs';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import OAuthClient from '../models/OAuthClient';
export default class OAuthClientsPage extends ExtensionPage {
    clients: OAuthClient[];
    loading: boolean;
    oninit(vnode: Mithril.Vnode<ExtensionPageAttrs, this>): void;
    loadClients(): Promise<void>;
    /**
     * The Clients tab first, then the registered Settings tab, then core's Permissions tab.
     */
    pageTabItems(): ItemList<TabItem>;
    pageTabContent(tab: string, vnode: Mithril.VnodeDOM<ExtensionPageAttrs, this>): Mithril.Children;
    /**
     * The registered clients and the button to add one, shown on the Clients tab.
     */
    content(): JSX.Element;
    emptyState(): Mithril.Children;
    clientsTable(): Mithril.Children;
    onCreated(client: OAuthClient, plainSecret: string | null): void;
    deleteClient(client: OAuthClient): void;
    rotateSecret(client: OAuthClient): void;
}
