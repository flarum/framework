import ExtensionPage, { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import type { TabItem } from 'flarum/common/components/Tabs';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import { type Connection, type Connections, type FeatureInfo, type FeatureSettings } from '../connections';
interface UsageRow {
    name: string | null;
    calls: number;
    tokens: number;
}
interface Usage {
    month: string;
    connections: UsageRow[];
    features: UsageRow[];
    members: {
        id: number;
        username: string | null;
        calls: number;
        tokens: number;
    }[];
    days: {
        date: string;
        tokens: number;
    }[];
}
/**
 * The AI page: Connections, Features and Usage tabs, then core's Permissions
 * tab, which lists each feature's permission. One save covers every tab.
 */
export default class AiPage extends ExtensionPage {
    connections: Connections;
    features: Record<string, FeatureSettings>;
    usage: Usage | null;
    usageState: 'idle' | 'loading' | 'failed';
    oninit(vnode: Mithril.Vnode<ExtensionPageAttrs, this>): void;
    className(): string;
    pageTabItems(): ItemList<TabItem>;
    pageTabContent(tab: string, vnode: Mithril.VnodeDOM<ExtensionPageAttrs, this>): Mithril.Children;
    form(sections: Mithril.Children): Mithril.Children;
    connectionsSection(): Mithril.Children;
    connectionItem(name: string, connection: Connection, locked: boolean): Mithril.Children;
    defaultSection(): Mithril.Children;
    featureSections(): Mithril.Children;
    featureSection(feature: FeatureInfo): Mithril.Children;
    limitsSection(): Mithril.Children;
    usageSection(): Mithril.Children;
    daysChart(days: Usage['days']): Mithril.Children;
    usageTable(heading: string, rows: [Mithril.Children, number, number][]): Mithril.Children;
    loadUsage(): void;
    connectionNames(): string[];
    edit(name?: string): void;
    remove(name: string): void;
    updateFeature(key: string, changes: Partial<FeatureSettings>): void;
    sync(): void;
    onsaved(): void;
}
export {};
