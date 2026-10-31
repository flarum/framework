import app from 'flarum/admin/app';
import extractText from 'flarum/common/utils/extractText';

export const CONNECTIONS_SETTING = 'flarum-ai.connections';
export const DEFAULT_SETTING = 'flarum-ai.default_connection';
export const FEATURES_SETTING = 'flarum-ai.features';
export const MEMBER_DAILY_SETTING = 'flarum-ai.member_daily_tokens';

/**
 * A connection as the admin page holds it. The server never sends a `key`:
 * only whether one is saved. A `key` is set here only between the admin
 * typing a new one (or `''` to remove it) and saving.
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
  drivers: string[];
  keyOptional: string[];
  urlRequired: string[];
  lockedConnections: Connections;
  lockedDefault: string | null;
  features: FeatureInfo[];
}

export function aiPayload(): AiPayload {
  return (app.data as unknown as Record<string, AiPayload>)['flarum-ai'];
}

export function parseObject<T>(json: string | undefined): Record<string, T> {
  try {
    const value = JSON.parse(json || '{}');

    return value && typeof value === 'object' && !Array.isArray(value) ? value : {};
  } catch {
    return {};
  }
}

export function parseConnections(json: string | undefined): Connections {
  return parseObject<Connection>(json);
}

/**
 * What's sent to the server. A connection without a `key` keeps the one
 * already saved for it.
 */
export function serializeConnections(connections: Connections): string {
  return JSON.stringify(Object.fromEntries(Object.entries(connections).map(([name, { hasKey, ...connection }]) => [name, connection])));
}

/**
 * What the page keeps once saved: whether each connection has a key, never
 * the key itself.
 */
export function withoutKeys(connections: Connections): Connections {
  return Object.fromEntries(
    Object.entries(connections).map(([name, { key, hasKey, ...connection }]) => [
      name,
      { ...connection, hasKey: key === undefined ? !!hasKey : key !== '' },
    ])
  );
}

export function driverLabel(driver: string): string {
  const key = `flarum-ai.admin.drivers.${driver}`;
  const label = extractText(app.translator.trans(key));

  // Providers set up in config.php can be ones the admin page doesn't offer.
  return label === key ? driver : label;
}

export function formatTokens(tokens: number): string {
  return new Intl.NumberFormat(app.translator.getLocale() || undefined).format(tokens);
}
