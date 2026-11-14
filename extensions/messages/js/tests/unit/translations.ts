import app from 'flarum/forum/app';
import flatten from 'flat';
import jsYaml from 'js-yaml';
import fs from 'fs';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

/** The extension's own strings, as the forum would have them. Call after the app has booted. */
export default function loadTranslations(): void {
  const file = resolve(dirname(fileURLToPath(import.meta.url)), '../../../locale/en.yml');

  app.translator.addTranslations(flatten(jsYaml.load(fs.readFileSync(file, 'utf8')) as object));
}
