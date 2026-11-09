import ExtensionPage, { type ExtensionPageAttrs } from 'flarum/admin/components/ExtensionPage';
import type { TabItem } from 'flarum/common/components/Tabs';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import { type ModelIssue, ModelLists } from '../utils/models';
import { type Connection, type Connections, type FeatureInfo, type FeatureSettings } from '../utils/settings';
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
 * A feature's settings typed in as numbers.
 */
type NumberField = 'daily_tokens' | 'temperature' | 'max_tokens';
/**
 * The AI page: Connections, Features and Usage tabs, then core's Permissions
 * tab, which lists each feature's permission. One save covers every tab.
 */
export default class AiPage extends ExtensionPage {
    connections: Connections;
    features: Record<string, FeatureSettings>;
    /** Feature fields holding something that isn't a valid value, as `key field`. */
    invalidFeatureFields: Set<string>;
    usage: Usage | null;
    modelLists: ModelLists;
    checkingModels: boolean;
    /** Connections the last check couldn't ask for their models. */
    uncheckedConnections: string[];
    /** Connections whose providers don't list their models, so they can't be checked. */
    unlistedConnections: string[];
    usageState: 'idle' | 'loading' | 'loaded' | 'failed';
    oninit(vnode: Mithril.Vnode<ExtensionPageAttrs, this>): void;
    className(): string;
    pageTabItems(): ItemList<TabItem>;
    /**
     * The page's own tabs with unsaved changes. Its settings aren't in the
     * settings registry, so core can't work this out from it.
     */
    unsavedTabs(): Set<string>;
    pageTabContent(tab: string, vnode: Mithril.VnodeDOM<ExtensionPageAttrs, this>): Mithril.Children;
    form(sections: Mithril.Children): Mithril.Children;
    connectionsSection(): Mithril.Children;
    connectionItem(name: string, connection: Connection, locked: boolean): Mithril.Children;
    /**
     * The tokens a connection has used this month, against its monthly limit
     * if it has one.
     */
    connectionUsage(name: string, connection: Connection): Mithril.Children;
    /**
     * With no connection yet, the providers to start from.
     */
    providerPicker(): Mithril.Children;
    /**
     * The connection features use when they name none, if it still exists.
     */
    defaultConnection(): string | null;
    noDefaultAlert(): Mithril.Children;
    /**
     * Whether a saved connection has changes on the page not saved yet.
     */
    connectionChanged(name: string): boolean;
    serverSection(): Mithril.Children;
    featureSections(): Mithril.Children;
    /**
     * The models features use that are deprecated or gone, and a way to check
     * them now rather than at the daily check.
     */
    modelIssuesSection(): Mithril.Children;
    issueAlert(issue: ModelIssue): Mithril.Children;
    /**
     * Whether the admin's temperature for a feature is left out of its calls,
     * as its model refuses one. Known once a call has been refused.
     */
    temperatureIgnored(key: string, settings: FeatureSettings): boolean;
    featureIssues(key: string): ModelIssue[];
    checkModels(): Promise<void>;
    /**
     * The issues as they are once a save has changed which models are in use.
     */
    reloadModelIssues(): Promise<void>;
    /**
     * Whether guests can use a feature while they have no daily limit: any
     * guest could then spend the forum's tokens without end.
     */
    guestsUnlimited(): boolean;
    /**
     * @param key Set where the alert's siblings are keyed, as Mithril needs all
     *            or none of a list's items to be.
     */
    guestsUnlimitedAlert(key?: string): Mithril.Children;
    /**
     * The connections saved on the server, whose models can be listed. One
     * added or changed on the page is listed once it's saved.
     */
    savedConnection(name: string | null): string | null;
    /**
     * The connection a feature's calls go through: its own, or the default.
     */
    featureConnection(settings: FeatureSettings): string | null;
    featureSection(feature: FeatureInfo): Mithril.Children;
    featureNumberField(key: string, field: NumberField, id: string, label: string, value: number | undefined, options: {
        min: string;
        max?: string;
        step: string;
        inputmode: string;
        placeholder?: string;
        message: string;
        note?: string;
    }): Mithril.Children;
    limitsSection(): Mithril.Children;
    usageSection(): Mithril.Children;
    daysChart(days: Usage['days']): Mithril.Children;
    usageTable(heading: string, rows: [Mithril.Children, number, number][]): Mithril.Children;
    loadUsage(): Promise<void>;
    /**
     * The model a connection uses when a feature names none, if the admin set one.
     */
    connectionModel(name: string): string | null;
    connectionNames(): string[];
    edit(name?: string, driver?: string): void;
    remove(name: string): void;
    isInvalid(key: string, field: NumberField): boolean;
    updateFeatureNumber(key: string, field: NumberField, value: string): void;
    updateFeature(key: string, changes: Partial<FeatureSettings>): void;
    sync(): void;
    onsaved(): void;
}
export {};
