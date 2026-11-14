import ExtensionPage, { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import type { TabItem } from 'flarum/common/components/Tabs';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import OAuthClient from '../models/OAuthClient';
/**
 * Applications an admin added, and the ones that registered themselves
 * (dynamic registration or a metadata document), such as MCP clients.
 */
type ClientKind = 'applications' | 'self_registered';
export default class OAuthClientsPage extends ExtensionPage {
    clients: OAuthClient[];
    loading: boolean;
    oninit(vnode: Mithril.Vnode<ExtensionPageAttrs, this>): void;
    loadClients(): Promise<void>;
    /**
     * The Applications and Self-registered tabs first, then the registered
     * Settings tab, then core's Permissions tab.
     */
    pageTabItems(): ItemList<TabItem>;
    pageTabContent(tab: string, vnode: Mithril.VnodeDOM<ExtensionPageAttrs, this>): Mithril.Children;
    /**
     * Whether clients of a kind may be used, as last saved. Both default on.
     */
    allows(kind: ClientKind): boolean;
    clientsOf(kind: ClientKind): OAuthClient[];
    /**
     * One kind of client, and for applications the button to add one.
     */
    kindContent(kind: ClientKind): JSX.Element;
    emptyState(kind: ClientKind): Mithril.Children;
    clientsTable(clients: OAuthClient[]): Mithril.Children;
    /**
     * How many members connected the client, linking to them in the users
     * list, and when it was last used.
     */
    usage(client: OAuthClient): Mithril.Children;
    onCreated(client: OAuthClient, plainSecret: string | null): void;
    deleteClient(client: OAuthClient): void;
    rotateSecret(client: OAuthClient): void;
}
export {};
