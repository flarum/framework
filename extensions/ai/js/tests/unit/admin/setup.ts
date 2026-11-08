import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import app from 'flarum/admin/app';
import dayjs from 'dayjs';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

const testDir = dirname(fileURLToPath(import.meta.url));
const coreJsDir = resolve(testDir, '../../../../../../framework/core/js');

export const payload = {
  connectionsWithKeys: ['claude'],
  drivers: ['anthropic', 'bedrock', 'ollama', 'openai-compatible'],
  keyOptional: ['ollama', 'openai-compatible'],
  urlRequired: ['openai-compatible'],
  regionRequired: ['bedrock'],
  awsSdk: true,
  temperatureIgnored: [] as { feature: string; connection: string; model: string }[],
  defaultModels: {} as Record<string, string>,
  lockedConnections: {},
  lockedDefault: null,
  features: [],
  modelIssues: [],
  rules: {
    namePattern: '^[a-z][a-z0-9_-]{0,49}$',
    modelMaxLength: 100,
    regionPattern: '^[a-z]{2}(-gov|-iso[a-z]?)?-[a-z]+-\\d{1,2}$',
    guardrailPattern: '^[A-Za-z0-9:\\/._-]{1,2048}$',
    guardrailVersionPattern: '^(DRAFT|[1-9][0-9]{0,7})$',
  },
};

// Core puts dayjs on the window in the browser.
dayjs.extend(localizedFormat);
(globalThis as any).dayjs = dayjs;

/**
 * The defaults extend.php registers, which the admin page always has.
 */
export const defaults = {
  'flarum-ai.connections': '{}',
  'flarum-ai.default_connection': '',
  'flarum-ai.features': '{}',
  'flarum-ai.member_daily_tokens': '',
  'flarum-ai.guest_daily_tokens': '',
};

/**
 * Boots the admin app with the AI page's payload and the given settings.
 */
export function bootAdmin(settings: Record<string, string> = {}) {
  const cwd = process.cwd();

  try {
    process.chdir(coreJsDir);
    bootstrapAdmin({ settings: { ...defaults, ...settings }, 'flarum-ai': JSON.parse(JSON.stringify(payload)) });
  } finally {
    process.chdir(cwd);
  }

  app.boot();
}
