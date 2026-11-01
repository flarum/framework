import ExtensionPage, { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import type { TabItem } from 'flarum/common/components/Tabs';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
export interface McpCapability {
    id: string;
    attributes: {
        kind: 'tool' | 'resource' | 'template' | 'prompt';
        name: string;
        title: string | null;
        description: string | null;
        extension: string;
        extensionTitle: string;
        readOnly: boolean | null;
        destructive: boolean | null;
    };
}
/**
 * How to connect MCP clients to the forum, and what the server offers them.
 * There are no settings: who may connect is the `mcp.use` permission.
 */
export default class McpPage extends ExtensionPage {
    capabilities: McpCapability[] | null;
    loadFailed: boolean;
    oninit(vnode: Mithril.Vnode<ExtensionPageAttrs, this>): void;
    loadCapabilities(): void;
    baseUrl(): string;
    serverUrl(): string;
    metadataUrl(): string;
    className(): string;
    /**
     * Connecting first, then what the server offers, then core's Permissions tab.
     */
    pageTabItems(): ItemList<TabItem>;
    pageTabContent(tab: string, vnode: Mithril.VnodeDOM<ExtensionPageAttrs, this>): Mithril.Children;
    /**
     * Lays sections out like a sectioned settings form, without save controls.
     */
    sectionList(sections: Mithril.Children): Mithril.Children;
    serverSection(): Mithril.Children;
    urlField(label: Mithril.Children, url: string): Mithril.Children;
    /**
     * MCP clients look for the metadata documents at the root of the host,
     * where a forum installed under a path cannot answer.
     */
    subPathWarning(basePath: string): Mithril.Children;
    connectSection(): Mithril.Children;
    /**
     * A key for client config files: the forum's host, which is stable and
     * readable where the title may not be.
     */
    serverName(): string;
    capabilitiesSection(): Mithril.Children;
    capabilitiesTable(capabilities: McpCapability[]): Mithril.Children;
    accessLabel(attributes: McpCapability['attributes']): Mithril.Children;
    copy(text: string): void;
}
