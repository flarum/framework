export declare const CONNECTIONS_SETTING = "flarum-ai.connections";
export declare const DEFAULT_SETTING = "flarum-ai.default_connection";
export declare const FEATURES_SETTING = "flarum-ai.features";
export declare const MEMBER_DAILY_SETTING = "flarum-ai.member_daily_tokens";
export declare const GUEST_DAILY_SETTING = "flarum-ai.guest_daily_tokens";
/**
 * A connection as the admin page holds it. The server never sends a key, only
 * whether one is saved. A `key` is set here only between the admin typing a
 * new one (or `''` to remove it) and saving.
 */
export interface Connection {
    driver: string;
    url?: string;
    model?: string;
    monthly_tokens?: number;
    hasKey?: boolean;
    key?: string;
}
export type Connections = Record<string, Connection>;
export interface FeatureInfo {
    key: string;
    /** A translation key. */
    label: string;
    /** A translation key. */
    description: string | null;
    /** The ID of the extension that registered it. */
    extension: string | null;
    permission: string;
}
export interface FeatureSettings {
    enabled?: boolean;
    connection?: string;
    model?: string;
    daily_tokens?: number;
}
export interface AiPayload {
    connectionsWithKeys: string[];
    drivers: string[];
    keyOptional: string[];
    urlRequired: string[];
    lockedConnections: Connections;
    lockedDefault: string | null;
    features: FeatureInfo[];
    /** The checks the server makes on save, so forms can make them first. */
    rules: {
        namePattern: string;
        modelMaxLength: number;
    };
}
export declare function aiPayload(): AiPayload;
export declare function parseObject<T>(json: string | undefined): Record<string, T>;
/**
 * The stored connections, each told whether it has a saved key.
 */
export declare function parseConnections(json: string | undefined, withKeys?: string[]): Connections;
/**
 * What's sent to the server. A connection without a `key` keeps the one saved
 * for it, if its provider and API URL haven't changed.
 */
export declare function serializeConnections(connections: Connections): string;
/**
 * What the page keeps once saved: whether each connection has a key, never
 * the key itself.
 */
export declare function withoutKeys(connections: Connections): Connections;
/**
 * Settings as stored, without the page's `hasKey` flags, for comparing with
 * what the server sent.
 */
export declare function storedForm(connections: Connections): string;
/**
 * A token limit typed into a field: a whole number above zero, nothing for no
 * limit, or `false` when it's neither.
 */
export declare function parseTokens(value: string | number | undefined | null): number | null | false;
export declare function nameIsValid(name: string): boolean;
export declare function driverLabel(driver: string): string;
export declare function formatTokens(tokens: number): string;
