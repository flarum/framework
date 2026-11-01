import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import app from 'flarum/admin/app';
import flatten from 'flat';
import { readFileSync } from 'fs';
import jsYaml from 'js-yaml';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

const testDir = dirname(fileURLToPath(import.meta.url));
const coreJsDir = resolve(testDir, '../../../../../../framework/core/js');

/**
 * The defaults extend.php registers, which the admin page always has.
 */
export const defaults = {
  'flarum-oauth-provider.allow_self_registered_clients': '1',
  'flarum-oauth-provider.allow_admin_clients': '1',
};

/**
 * Boots the admin app with this extension's strings and the given settings.
 */
export function bootAdmin(settings: Record<string, string> = {}) {
  const cwd = process.cwd();

  try {
    process.chdir(coreJsDir);
    bootstrapAdmin({
      settings: { ...defaults, ...settings },
      extensions: { 'flarum-oauth-provider': { id: 'flarum-oauth-provider', extra: { 'flarum-extension': { title: 'OAuth Provider' } } } },
    });
  } finally {
    process.chdir(cwd);
  }

  app.boot();

  // Only core's strings are loaded by the bootstrap.
  app.translator.addTranslations(flatten(jsYaml.load(readFileSync(resolve(testDir, '../../../../resources/locale/en.yml'), 'utf8'))) as any);
}
