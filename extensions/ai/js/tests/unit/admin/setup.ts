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
  drivers: ['anthropic', 'ollama', 'openai-compatible'],
  keyOptional: ['ollama', 'openai-compatible'],
  urlRequired: ['openai-compatible'],
  lockedConnections: {},
  lockedDefault: null,
  features: [],
  rules: { namePattern: '^[a-z][a-z0-9_-]{0,49}$', modelMaxLength: 100 },
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
